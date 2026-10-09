<?php
/**
 * PHPMailer configuration.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configures PHPMailer for SMTP with the connection chosen by the interceptor.
 */
class Relaymint_Mailer {

	/**
	 * Connection used for the current email (null = leave PHPMailer untouched).
	 *
	 * @var string|null
	 */
	public static $connection_id = null;

	/**
	 * Capture the SMTP transcript even when the debug log is disabled (test emails).
	 *
	 * @var bool
	 */
	public static $force_debug = false;

	/**
	 * SMTP transcript of the current email.
	 *
	 * @var string
	 */
	public static $transcript = '';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'phpmailer_init', array( __CLASS__, 'configure' ), PHP_INT_MAX );
		add_filter( 'wp_mail_from', array( __CLASS__, 'filter_from_email' ), PHP_INT_MAX );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'filter_from_name' ), PHP_INT_MAX );
		add_action( 'wp_mail_succeeded', array( __CLASS__, 'after_send' ), 5, 0 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'after_failed' ), 5, 0 );
	}

	/**
	 * Configure PHPMailer.
	 *
	 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
	 */
	public static function configure( $phpmailer ) {
		self::$transcript = '';

		// PHPMailer is reused between wp_mail() calls; reset what we may have set before.
		$phpmailer->SMTPDebug   = 0; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->SMTPOptions = array(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if ( null === self::$connection_id ) {
			return;
		}

		$connection = Relaymint_Connections::get( self::$connection_id );
		if ( ! Relaymint_Connections::is_configured( $connection ) ) {
			return;
		}

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->isSMTP();
		$phpmailer->Host        = $connection['host'];
		$phpmailer->Port        = $connection['port'];
		$phpmailer->SMTPSecure  = in_array( $connection['encryption'], array( 'ssl', 'tls' ), true ) ? $connection['encryption'] : '';
		$phpmailer->SMTPAutoTLS = 'none' === $connection['encryption'] ? (bool) $connection['autotls'] : true;
		$phpmailer->SMTPAuth    = (bool) $connection['auth'];

		if ( $connection['auth'] ) {
			$phpmailer->Username = $connection['user'];
			$phpmailer->Password = $connection['pass'];
		}

		if ( $connection['return_path'] ) {
			$phpmailer->Sender = $phpmailer->From;
		}

		if ( Relaymint_Options::value( 'misc', 'allow_insecure_ssl' ) ) {
			$phpmailer->SMTPOptions = array(
				'ssl' => array(
					'verify_peer'       => false,
					'verify_peer_name'  => false,
					'allow_self_signed' => true,
				),
			);
		}

		if ( self::$force_debug || Relaymint_Debug_Log::is_enabled() ) {
			$phpmailer->SMTPDebug   = 3;
			$phpmailer->Debugoutput = static function ( $line ) {
				Relaymint_Mailer::$transcript .= rtrim( (string) $line ) . "\n";
			};
		}

		if ( Relaymint_Logger::$current_id ) {
			Relaymint_Logger::update(
				Relaymint_Logger::$current_id,
				array(
					'from_email' => $phpmailer->FromName ? sprintf( '%s <%s>', $phpmailer->FromName, $phpmailer->From ) : $phpmailer->From,
				)
			);
		}
		// phpcs:enable
	}

	/**
	 * Connection of the current email, if it is sent via Relaymint.
	 *
	 * @return array|null
	 */
	private static function current_connection() {
		if ( null === self::$connection_id ) {
			return null;
		}
		$connection = Relaymint_Connections::get( self::$connection_id );
		return Relaymint_Connections::is_configured( $connection ) ? $connection : null;
	}

	/**
	 * Filter the sender address (runs before PHPMailer validates it).
	 *
	 * @param string $from_email Sender address.
	 * @return string
	 */
	public static function filter_from_email( $from_email ) {
		$connection = self::current_connection();
		if ( ! $connection || '' === $connection['from_email'] || ! is_email( $connection['from_email'] ) ) {
			return $from_email;
		}
		$is_default = 0 === strpos( (string) $from_email, 'wordpress@' );
		return $connection['force_from_email'] || $is_default ? $connection['from_email'] : $from_email;
	}

	/**
	 * Filter the sender name.
	 *
	 * @param string $from_name Sender name.
	 * @return string
	 */
	public static function filter_from_name( $from_name ) {
		$connection = self::current_connection();
		if ( ! $connection || '' === $connection['from_name'] ) {
			return $from_name;
		}
		return $connection['force_from_name'] || 'WordPress' === $from_name ? $connection['from_name'] : $from_name;
	}

	/**
	 * Write the transcript after a successful send.
	 */
	public static function after_send() {
		if ( '' !== self::$transcript && Relaymint_Debug_Log::is_enabled() ) {
			Relaymint_Debug_Log::add( "Email sent via connection '" . self::$connection_id . "'.\n" . self::$transcript );
		}
		self::$connection_id = null;
	}

	/**
	 * Write the transcript after a failed send.
	 */
	public static function after_failed() {
		if ( '' !== self::$transcript ) {
			Relaymint_Debug_Log::add( "SMTP transcript (connection '" . self::$connection_id . "'):\n" . self::$transcript, 'error' );
		}
		self::$connection_id = null;
	}
}
