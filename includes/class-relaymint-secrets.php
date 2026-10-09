<?php
/**
 * Authenticated encryption for credentials stored in the database.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encrypts/decrypts secrets with AES-256-GCM using a key derived from the WordPress salts.
 */
class Relaymint_Secrets {

	const CIPHER = 'aes-256-gcm';

	/**
	 * Prefix that marks a value as encrypted by this class.
	 */
	const PREFIX = 'rmenc:';

	/**
	 * Derive a stable key from WordPress salts.
	 *
	 * @return string|null Binary key or null when no salts are defined.
	 */
	private static function get_key() {
		$material = '';
		foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY' ) as $constant ) {
			if ( defined( $constant ) ) {
				$material .= constant( $constant );
			}
		}

		if ( '' === $material ) {
			return null;
		}

		return hash( 'sha256', 'relaymint|' . $material, true );
	}

	/**
	 * Encrypt a plain text value.
	 *
	 * @param string $plain_text Value to encrypt.
	 * @return string Encrypted, base64 encoded value, or empty string on failure.
	 */
	public static function encrypt( $plain_text ) {
		$plain_text = (string) $plain_text;
		if ( '' === $plain_text ) {
			return '';
		}

		$key = self::get_key();
		if ( null === $key || ! function_exists( 'openssl_encrypt' ) ) {
			Relaymint_Debug_Log::add( 'Could not encrypt the SMTP password: OpenSSL or WordPress salts are missing.', 'error' );
			return '';
		}

		$iv     = random_bytes( 12 );
		$tag    = '';
		$cipher = openssl_encrypt( $plain_text, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag );
		if ( false === $cipher ) {
			return '';
		}

		return self::PREFIX . base64_encode( $iv . $tag . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a value produced by encrypt().
	 *
	 * @param string $encrypted Encrypted value.
	 * @return string Plain text or empty string when decryption fails.
	 */
	public static function decrypt( $encrypted ) {
		$encrypted = (string) $encrypted;
		if ( '' === $encrypted || 0 !== strpos( $encrypted, self::PREFIX ) ) {
			return '';
		}

		$key = self::get_key();
		if ( null === $key || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$raw = base64_decode( substr( $encrypted, strlen( self::PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $raw || strlen( $raw ) < 29 ) {
			return '';
		}

		$iv     = substr( $raw, 0, 12 );
		$tag    = substr( $raw, 12, 16 );
		$cipher = substr( $raw, 28 );
		$plain  = openssl_decrypt( $cipher, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag );

		return false === $plain ? '' : $plain;
	}
}
