<?php
/**
 * Email log.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes and reads email log entries.
 */
class Relaymint_Logger {

	const STATUS_QUEUED  = 'queued';
	const STATUS_SENDING = 'sending';
	const STATUS_SENT    = 'sent';
	const STATUS_FAILED  = 'failed';
	const STATUS_BLOCKED = 'blocked';

	/**
	 * Log entry of the email currently being sent.
	 *
	 * @var int
	 */
	public static $current_id = 0;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_mail_succeeded', array( __CLASS__, 'on_succeeded' ), 10, 0 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'on_failed' ), 10, 1 );
	}

	/**
	 * Whether the email log is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) Relaymint_Options::value( 'logs', 'enabled' );
	}

	/**
	 * Translated status labels.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			self::STATUS_SENT    => __( 'Sent', 'relaymint' ),
			self::STATUS_FAILED  => __( 'Failed', 'relaymint' ),
			self::STATUS_QUEUED  => __( 'Queued', 'relaymint' ),
			self::STATUS_SENDING => __( 'Sending', 'relaymint' ),
			self::STATUS_BLOCKED => __( 'Blocked', 'relaymint' ),
		);
	}

	/**
	 * Create a log entry.
	 *
	 * @param Relaymint_Mail_Data $mail       Mail data.
	 * @param string              $status     Status.
	 * @param string              $connection Connection ID ('' = not sent via Relaymint SMTP).
	 * @param array               $initiator  Initiator from Relaymint_Mail_Data::detect_initiator().
	 * @param string              $error      Error or reason.
	 * @return int Log ID or 0 when logging is disabled.
	 */
	public static function create( Relaymint_Mail_Data $mail, $status, $connection, array $initiator, $error = '' ) {
		global $wpdb;

		if ( ! self::is_enabled() ) {
			return 0;
		}

		$log_content = (bool) Relaymint_Options::value( 'logs', 'log_content' );
		$now         = current_time( 'mysql', true );

		$data = array(
			'status'         => $status,
			'subject'        => (string) $mail->atts['subject'],
			'from_email'     => $mail->from(),
			'to_email'       => implode( ', ', $mail->to() ),
			'cc'             => implode( ', ', $mail->header_addresses( 'Cc' ) ),
			'bcc'            => implode( ', ', $mail->header_addresses( 'Bcc' ) ),
			'reply_to'       => implode( ', ', $mail->header_addresses( 'Reply-To' ) ),
			'headers'        => $log_content ? $mail->headers_string() : '',
			'message'        => $log_content ? (string) $mail->atts['message'] : '',
			'content_type'   => $mail->is_html() ? 'text/html' : 'text/plain',
			'attachments'    => wp_json_encode( array_map( 'wp_basename', $mail->attachments() ) ),
			'connection'     => (string) $connection,
			'initiator_type' => isset( $initiator['type'] ) ? $initiator['type'] : '',
			'initiator_name' => isset( $initiator['name'] ) ? $initiator['name'] : '',
			'initiator_file' => isset( $initiator['file'] ) ? $initiator['file'] : '',
			'error'          => (string) $error,
			'created_at'     => $now,
			'updated_at'     => $now,
		);

		/**
		 * Filter the data of a new log entry before it is written.
		 *
		 * @param array               $data Row data.
		 * @param Relaymint_Mail_Data $mail Mail data.
		 */
		$data = apply_filters( 'relaymint_log_entry_data', $data, $mail );

		$inserted = $wpdb->insert( Relaymint_Installer::log_table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Update a log entry.
	 *
	 * @param int   $id     Log ID.
	 * @param array $fields Fields to update.
	 */
	public static function update( $id, array $fields ) {
		global $wpdb;

		if ( ! $id ) {
			return;
		}
		$fields['updated_at'] = current_time( 'mysql', true );
		$wpdb->update( Relaymint_Installer::log_table(), $fields, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Mark the current email as sent.
	 */
	public static function on_succeeded() {
		if ( self::$current_id ) {
			self::update(
				self::$current_id,
				array(
					'status' => self::STATUS_SENT,
					'error'  => '',
				)
			);
		}
		self::$current_id = 0;
	}

	/**
	 * Mark the current email as failed.
	 *
	 * @param WP_Error $error Error from wp_mail().
	 */
	public static function on_failed( $error ) {
		$message = is_wp_error( $error ) ? $error->get_error_message() : __( 'Unknown error', 'relaymint' );

		Relaymint_Debug_Log::add( 'Email failed: ' . $message, 'error' );

		if ( self::$current_id ) {
			self::update(
				self::$current_id,
				array(
					'status' => self::STATUS_FAILED,
					'error'  => $message,
				)
			);
		}
		self::$current_id = 0;
	}

	/**
	 * Fetch a single log entry.
	 *
	 * @param int $id Log ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = Relaymint_Installer::log_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Delete log entries.
	 *
	 * @param int[] $ids Log IDs.
	 * @return int Number of deleted rows.
	 */
	public static function delete( array $ids ) {
		global $wpdb;

		$ids = array_filter( array_map( 'absint', $ids ) );
		if ( ! $ids ) {
			return 0;
		}
		$table        = Relaymint_Installer::log_table();
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}

	/**
	 * Delete all log entries.
	 */
	public static function delete_all() {
		global $wpdb;
		$table = Relaymint_Installer::log_table();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Remove entries older than the configured retention period.
	 */
	public static function cleanup() {
		global $wpdb;

		$days = (int) Relaymint_Options::value( 'logs', 'retention_days' );
		if ( $days <= 0 ) {
			return;
		}
		$table  = Relaymint_Installer::log_table();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Count entries per status.
	 *
	 * @return array<string,int>
	 */
	public static function count_by_status() {
		global $wpdb;
		$table  = Relaymint_Installer::log_table();
		$rows   = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$counts = array();
		foreach ( (array) $rows as $row ) {
			$counts[ $row->status ] = (int) $row->total;
		}
		return $counts;
	}
}
