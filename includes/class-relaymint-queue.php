<?php
/**
 * Background sending queue ("Optimize Email Sending").
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores emails in a queue table and sends them in the background via Action Scheduler.
 */
class Relaymint_Queue {

	const HOOK  = 'relaymint_process_queue';
	const GROUP = 'relaymint';

	/**
	 * True while the worker sends queued emails.
	 *
	 * @var bool
	 */
	public static $processing = false;

	/**
	 * Queue item currently being sent.
	 *
	 * @var array|null
	 */
	public static $current = null;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'process' ) );
	}

	/**
	 * Whether background sending is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) Relaymint_Options::value( 'misc', 'optimize_sending' );
	}

	/**
	 * Add an email to the queue.
	 *
	 * @param Relaymint_Mail_Data $mail      Mail data.
	 * @param array               $initiator Initiator info.
	 * @return bool
	 */
	public static function enqueue( Relaymint_Mail_Data $mail, array $initiator ) {
		global $wpdb;

		$atts                = $mail->atts;
		$atts['attachments'] = array();
		$attachment_dir      = '';

		// Attachments are often temporary files that are gone once the request ends, so keep a copy.
		$attachments = $mail->attachments();
		if ( $attachments ) {
			$attachment_dir = self::create_attachment_dir();
			foreach ( $attachments as $file ) {
				if ( '' !== $attachment_dir && is_readable( $file ) ) {
					$target = $attachment_dir . '/' . wp_unique_filename( $attachment_dir, wp_basename( $file ) );
					if ( copy( $file, $target ) ) {
						$atts['attachments'][] = $target;
						continue;
					}
				}
				$atts['attachments'][] = $file;
			}
		}

		$log_id = Relaymint_Logger::create( $mail, Relaymint_Logger::STATUS_QUEUED, '', $initiator );

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			Relaymint_Installer::queue_table(),
			array(
				'log_id'     => $log_id,
				'data'       => wp_json_encode(
					array(
						'atts'           => $atts,
						'initiator'      => $initiator,
						'attachment_dir' => $attachment_dir,
					)
				),
				'status'     => 'queued',
				'attempts'   => 0,
				'created_at' => current_time( 'mysql', true ),
			)
		);

		if ( ! $inserted ) {
			Relaymint_Debug_Log::add( 'Could not add email to the queue: ' . $wpdb->last_error, 'error' );
			Relaymint_Logger::update(
				$log_id,
				array(
					'status' => Relaymint_Logger::STATUS_FAILED,
					'error'  => __( 'Could not add email to the queue.', 'relaymint' ),
				)
			);
			return false;
		}

		self::schedule();
		return true;
	}

	/**
	 * Make sure a worker run is scheduled.
	 *
	 * @param int  $timestamp Run at this time (0 = as soon as possible).
	 * @param bool $force     Schedule even if another run is pending (used from inside the worker).
	 */
	public static function schedule( $timestamp = 0, $force = false ) {
		if ( ! did_action( 'action_scheduler_init' ) ) {
			add_action(
				'action_scheduler_init',
				static function () use ( $timestamp, $force ) {
					Relaymint_Queue::schedule( $timestamp, $force );
				}
			);
			return;
		}

		if ( ! $force && as_has_scheduled_action( self::HOOK, null, self::GROUP ) ) {
			return;
		}

		if ( $timestamp > time() ) {
			as_schedule_single_action( $timestamp, self::HOOK, array(), self::GROUP );
		} else {
			as_enqueue_async_action( self::HOOK, array(), self::GROUP );
		}
	}

	/**
	 * Worker: send queued emails while respecting rate limits.
	 */
	public static function process() {
		global $wpdb;

		$table      = Relaymint_Installer::queue_table();
		$started    = time();
		$time_limit = (int) apply_filters( 'relaymint_queue_time_limit', 20 );

		self::$processing = true;

		while ( true ) {
			$next_slot = Relaymint_Rate_Limiter::next_slot();
			if ( $next_slot ) {
				Relaymint_Debug_Log::add( sprintf( 'Rate limit reached, queue continues at %s UTC.', gmdate( 'Y-m-d H:i:s', $next_slot ) ) );
				self::schedule( $next_slot, true );
				break;
			}

			if ( time() - $started >= $time_limit ) {
				self::schedule( 0, true );
				break;
			}

			$item = $wpdb->get_row( "SELECT * FROM {$table} WHERE status = 'queued' ORDER BY id ASC LIMIT 1", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $item ) {
				break;
			}

			// Claim the item so a parallel worker does not send it twice.
			$claimed = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$table,
				array( 'status' => 'sending' ),
				array(
					'id'     => $item['id'],
					'status' => 'queued',
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $claimed ) {
				continue;
			}

			self::send_item( $item );
		}

		self::$processing = false;
		self::$current    = null;
	}

	/**
	 * Send one queue item.
	 *
	 * @param array $item Queue row.
	 */
	private static function send_item( array $item ) {
		global $wpdb;

		$data = json_decode( $item['data'], true );
		$atts = is_array( $data ) && isset( $data['atts'] ) ? (array) $data['atts'] : array();

		self::$current = array(
			'log_id'    => (int) $item['log_id'],
			'initiator' => isset( $data['initiator'] ) ? (array) $data['initiator'] : array(),
		);

		$sent = false;
		if ( $atts ) {
			$sent = wp_mail(
				isset( $atts['to'] ) ? $atts['to'] : '',
				isset( $atts['subject'] ) ? $atts['subject'] : '',
				isset( $atts['message'] ) ? $atts['message'] : '',
				isset( $atts['headers'] ) ? $atts['headers'] : array(),
				isset( $atts['attachments'] ) ? $atts['attachments'] : array()
			);
		}

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			Relaymint_Installer::queue_table(),
			array(
				'status'   => $sent ? 'sent' : 'failed',
				'attempts' => (int) $item['attempts'] + 1,
				'sent_at'  => current_time( 'mysql', true ),
				'data'     => '',
			),
			array( 'id' => $item['id'] )
		);

		if ( ! empty( $data['attachment_dir'] ) ) {
			self::delete_dir( $data['attachment_dir'] );
		}

		self::$current = null;
	}

	/**
	 * Number of emails waiting in the queue.
	 *
	 * @return int
	 */
	public static function count_pending() {
		global $wpdb;
		$table = Relaymint_Installer::queue_table();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status IN ('queued','sending')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Remove processed rows that are no longer needed for rate limiting, and recover stuck items.
	 */
	public static function cleanup() {
		global $wpdb;
		$table = Relaymint_Installer::queue_table();

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE status IN ('sent','failed') AND sent_at < %s", gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS - DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Items stuck in "sending" (e.g. fatal error during send) are retried.
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = 'queued' WHERE status = 'sending' AND created_at < %s", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( self::count_pending() > 0 ) {
			self::schedule();
		}
	}

	/**
	 * Create a protected directory for attachment copies.
	 *
	 * @return string Directory path or empty string on failure.
	 */
	private static function create_attachment_dir() {
		$uploads = wp_upload_dir( null, false );
		if ( ! empty( $uploads['error'] ) ) {
			return '';
		}
		$base = trailingslashit( $uploads['basedir'] ) . 'relaymint-queue';
		if ( ! wp_mkdir_p( $base ) ) {
			return '';
		}
		if ( ! file_exists( $base . '/index.php' ) ) {
			file_put_contents( $base . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $base . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		$dir = $base . '/' . wp_generate_password( 20, false, false );
		return wp_mkdir_p( $dir ) ? $dir : '';
	}

	/**
	 * Delete an attachment directory created by this class.
	 *
	 * @param string $dir Directory.
	 */
	private static function delete_dir( $dir ) {
		$uploads = wp_upload_dir( null, false );
		$base    = wp_normalize_path( trailingslashit( $uploads['basedir'] ) . 'relaymint-queue/' );
		$dir     = wp_normalize_path( $dir );

		// Never delete anything outside our own queue directory.
		if ( 0 !== strpos( $dir, $base ) || ! is_dir( $dir ) || false !== strpos( substr( $dir, strlen( $base ) ), '..' ) ) {
			return;
		}
		foreach ( (array) glob( $dir . '/*' ) as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
		rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	}
}
