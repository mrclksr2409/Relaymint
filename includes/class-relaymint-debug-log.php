<?php
/**
 * Debug log stored as a capped option.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps the most recent debug entries (errors are always kept, details only when enabled).
 */
class Relaymint_Debug_Log {

	const OPTION      = 'relaymint_debug_log';
	const MAX_ENTRIES = 200;

	/**
	 * Whether verbose debug logging is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) Relaymint_Options::value( 'misc', 'debug_log' );
	}

	/**
	 * Add an entry. Errors are always written; info entries only with debug logging enabled.
	 *
	 * @param string $message Message (may contain multiple lines).
	 * @param string $level   info|error.
	 */
	public static function add( $message, $level = 'info' ) {
		if ( 'error' !== $level && ! self::is_enabled() ) {
			return;
		}

		$entries   = self::get();
		$entries[] = array(
			'time'    => time(),
			'level'   => 'error' === $level ? 'error' : 'info',
			'message' => self::mask( (string) $message ),
		);

		if ( count( $entries ) > self::MAX_ENTRIES ) {
			$entries = array_slice( $entries, -self::MAX_ENTRIES );
		}

		update_option( self::OPTION, $entries, false );
	}

	/**
	 * All entries, oldest first.
	 *
	 * @return array
	 */
	public static function get() {
		$entries = get_option( self::OPTION, array() );
		return is_array( $entries ) ? $entries : array();
	}

	/**
	 * Remove all entries.
	 */
	public static function clear() {
		delete_option( self::OPTION );
	}

	/**
	 * Mask credentials in SMTP transcripts.
	 *
	 * PHPMailer prints the base64 encoded username and password after "AUTH LOGIN"
	 * and the whole credential blob after "AUTH PLAIN".
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	public static function mask( $text ) {
		$lines     = explode( "\n", $text );
		$mask_next = 0;

		foreach ( $lines as $i => $line ) {
			if ( preg_match( '/CLIENT -> SERVER:\s*AUTH\s+(PLAIN|XOAUTH2)\s+\S+/i', $line ) ) {
				$lines[ $i ] = preg_replace( '/(AUTH\s+(?:PLAIN|XOAUTH2)\s+)\S+/i', '$1********', $line );
				continue;
			}
			if ( preg_match( '/CLIENT -> SERVER:\s*AUTH\s+(LOGIN|CRAM-MD5)/i', $line ) ) {
				$mask_next = 2;
				continue;
			}
			if ( $mask_next > 0 && false !== strpos( $line, 'CLIENT -> SERVER:' ) ) {
				$lines[ $i ] = preg_replace( '/(CLIENT -> SERVER:\s*).*/', '$1********', $line );
				--$mask_next;
			}
		}

		return implode( "\n", $lines );
	}
}
