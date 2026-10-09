<?php
/**
 * Domain check.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Makes sure the SMTP settings are only used on allowed site domains.
 *
 * Useful when a site is cloned to staging: the copy will not send through the
 * production SMTP account (or, optionally, not send at all).
 */
class Relaymint_Domain_Check {

	/**
	 * Whether the domain check is active.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) Relaymint_Options::value( 'misc', 'domain_check' );
	}

	/**
	 * Allowed domains, normalized.
	 *
	 * @return string[]
	 */
	public static function allowed_domains() {
		$raw     = (string) Relaymint_Options::value( 'misc', 'domain_check_allowed' );
		$domains = array_filter( array_map( array( __CLASS__, 'normalize' ), preg_split( '/[\s,]+/', $raw ) ) );

		/**
		 * Filter the list of allowed site domains.
		 *
		 * @param string[] $domains Normalized domains.
		 */
		return array_values( array_unique( (array) apply_filters( 'relaymint_allowed_domains', $domains ) ) );
	}

	/**
	 * Current site domain, normalized.
	 *
	 * @return string
	 */
	public static function site_domain() {
		return self::normalize( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	/**
	 * Whether the current site domain is allowed (true when the check is disabled).
	 *
	 * @return bool
	 */
	public static function is_allowed() {
		if ( ! self::is_enabled() ) {
			return true;
		}
		return in_array( self::site_domain(), self::allowed_domains(), true );
	}

	/**
	 * Whether all emails must be blocked on a domain mismatch.
	 *
	 * @return bool
	 */
	public static function block_all() {
		return (bool) Relaymint_Options::value( 'misc', 'domain_check_block_all' );
	}

	/**
	 * Normalize a domain or URL (lowercase, no scheme, no "www.", no path or port).
	 *
	 * @param string $domain Domain or URL.
	 * @return string
	 */
	public static function normalize( $domain ) {
		$domain = strtolower( trim( (string) $domain ) );
		if ( '' === $domain ) {
			return '';
		}
		if ( false !== strpos( $domain, '://' ) ) {
			$domain = (string) wp_parse_url( $domain, PHP_URL_HOST );
		}
		$domain = preg_replace( '#[/:].*$#', '', $domain );
		return preg_replace( '/^www\./', '', $domain );
	}
}
