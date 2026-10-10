<?php
/**
 * Interception of wp_mail().
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decides per email whether it is blocked, queued or sent, and via which connection.
 */
class Relaymint_Interceptor {

	/**
	 * Send immediately even if background sending is enabled (test emails, resends).
	 *
	 * @var bool
	 */
	public static $skip_queue = false;

	/**
	 * Force a specific connection (test emails).
	 *
	 * @var string|null
	 */
	public static $force_connection = null;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'pre_wp_mail', array( __CLASS__, 'pre_wp_mail' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Filter callback for pre_wp_mail.
	 *
	 * @param null|bool $result Short-circuit result from other plugins.
	 * @param array     $atts   wp_mail() attributes.
	 * @return null|bool
	 */
	public static function pre_wp_mail( $result, $atts ) {
		Relaymint_Mailer::$connection_id = null;
		Relaymint_Logger::$current_id    = 0;

		if ( null !== $result ) {
			return $result;
		}

		$mail = new Relaymint_Mail_Data( (array) $atts );

		if ( Relaymint_Queue::$processing && Relaymint_Queue::$current ) {
			$initiator = Relaymint_Queue::$current['initiator'];
			$log_id    = Relaymint_Queue::$current['log_id'];
		} else {
			$initiator = Relaymint_Mail_Data::detect_initiator();
			$log_id    = 0;
		}

		// 1. "Do Not Send" blocks every email.
		if ( Relaymint_Options::value( 'misc', 'do_not_send' ) ) {
			self::block( $mail, $initiator, $log_id, __( 'Blocked by the "Do Not Send" setting.', 'relaymint' ) );

			/**
			 * Return value of wp_mail() for emails blocked by "Do Not Send".
			 *
			 * @param bool $result Default true, so callers do not show errors.
			 */
			return (bool) apply_filters( 'relaymint_do_not_send_result', true );
		}

		// 2. Domain check: skip SMTP (or block) when the site domain is not allowed.
		$use_smtp = true;
		if ( ! Relaymint_Domain_Check::is_allowed() ) {
			if ( Relaymint_Domain_Check::block_all() ) {
				self::block(
					$mail,
					$initiator,
					$log_id,
					/* translators: %s: site domain */
					sprintf( __( 'Blocked by the domain check: %s is not an allowed domain.', 'relaymint' ), Relaymint_Domain_Check::site_domain() )
				);
				return false;
			}
			$use_smtp = false;
		}

		// 3. Background sending.
		if ( $use_smtp && Relaymint_Queue::is_enabled() && ! Relaymint_Queue::$processing && ! self::$skip_queue ) {
			return Relaymint_Queue::enqueue( $mail, $initiator );
		}

		// 4. Pick the connection.
		$connection = '';
		if ( $use_smtp ) {
			$connection = null !== self::$force_connection ? self::$force_connection : Relaymint_Router::resolve( $mail, $initiator );
			if ( ! Relaymint_Connections::is_configured( Relaymint_Connections::get( $connection ) ) ) {
				$connection = '';
			}
		}

		/**
		 * Fires before Relaymint hands an email to PHPMailer.
		 *
		 * @param Relaymint_Mail_Data $mail       Mail data.
		 * @param string              $connection Connection ID, empty when the default PHP mailer is used.
		 */
		do_action( 'relaymint_before_send', $mail, $connection );

		Relaymint_Mailer::prepare( '' !== $connection ? $connection : null );

		if ( $log_id ) {
			Relaymint_Logger::update(
				$log_id,
				array(
					'status'     => Relaymint_Logger::STATUS_SENDING,
					'connection' => $connection,
				)
			);
		} else {
			$log_id = Relaymint_Logger::create( $mail, Relaymint_Logger::STATUS_SENDING, $connection, $initiator );
		}
		Relaymint_Logger::$current_id = $log_id;

		return null;
	}

	/**
	 * Log a blocked email.
	 *
	 * @param Relaymint_Mail_Data $mail      Mail data.
	 * @param array               $initiator Initiator info.
	 * @param int                 $log_id    Existing log entry (queued emails) or 0.
	 * @param string              $reason    Reason.
	 */
	private static function block( Relaymint_Mail_Data $mail, array $initiator, $log_id, $reason ) {
		Relaymint_Debug_Log::add( sprintf( 'Email "%s" not sent: %s', $mail->atts['subject'], $reason ) );

		if ( $log_id ) {
			Relaymint_Logger::update(
				$log_id,
				array(
					'status' => Relaymint_Logger::STATUS_BLOCKED,
					'error'  => $reason,
				)
			);
			return;
		}
		Relaymint_Logger::create( $mail, Relaymint_Logger::STATUS_BLOCKED, '', $initiator, $reason );
	}
}
