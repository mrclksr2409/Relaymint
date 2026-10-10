<?php
/**
 * Central settings storage.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and writes the `relaymint_settings` option.
 */
class Relaymint_Options {

	const OPTION = 'relaymint_settings';

	/**
	 * Runtime cache of the merged settings.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default values for a single connection (SMTP or Microsoft 365 / Outlook).
	 *
	 * @return array
	 */
	public static function connection_defaults() {
		return array(
			'name'             => '',
			'mailer'           => 'smtp',
			'from_email'       => '',
			'from_name'        => '',
			'force_from_email' => true,
			'force_from_name'  => false,
			'return_path'      => false,
			'host'             => '',
			'port'             => 587,
			'encryption'       => 'tls',
			'autotls'          => true,
			'auth'             => true,
			'user'             => '',
			'pass'             => '',
			'ms_auth'          => 'delegated',
			'ms_tenant'        => 'common',
			'ms_client_id'     => '',
			'ms_client_secret' => '',
		);
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		$primary         = self::connection_defaults();
		$primary['name'] = 'Primary';

		return array(
			'connections' => array(
				'primary' => $primary,
			),
			'routing'     => array(
				'enabled' => false,
				'routes'  => array(),
			),
			'logs'        => array(
				'enabled'        => true,
				'log_content'    => false,
				'retention_days' => 0,
			),
			'misc'        => array(
				'domain_check'           => false,
				'domain_check_allowed'   => '',
				'domain_check_block_all' => false,
				'do_not_send'            => false,
				'allow_insecure_ssl'     => false,
				'debug_log'              => false,
				'optimize_sending'       => false,
				'rate_limit'             => false,
				'rate_limit_minute'      => 0,
				'rate_limit_hour'        => 0,
				'rate_limit_day'         => 0,
				'rate_limit_week'        => 0,
				'update_channel'         => 'stable',
			),
		);
	}

	/**
	 * Get all settings or a single group.
	 *
	 * @param string|null $group Optional group key (connections, routing, logs, misc).
	 * @return array
	 */
	public static function get( $group = null ) {
		if ( null === self::$cache ) {
			$stored   = get_option( self::OPTION, array() );
			$stored   = is_array( $stored ) ? $stored : array();
			$defaults = self::defaults();

			$settings = array();
			foreach ( $defaults as $key => $default ) {
				$value = isset( $stored[ $key ] ) && is_array( $stored[ $key ] ) ? $stored[ $key ] : array();
				if ( 'connections' === $key ) {
					$settings[ $key ] = array();
					foreach ( array_merge( $default, $value ) as $id => $connection ) {
						$settings[ $key ][ $id ] = array_merge( self::connection_defaults(), (array) $connection );
					}
				} else {
					$settings[ $key ] = array_merge( $default, $value );
				}
			}
			self::$cache = $settings;
		}

		if ( null === $group ) {
			return self::$cache;
		}

		return isset( self::$cache[ $group ] ) ? self::$cache[ $group ] : array();
	}

	/**
	 * Get a single misc/logs flag or value.
	 *
	 * @param string $group Group key.
	 * @param string $key   Setting key.
	 * @return mixed
	 */
	public static function value( $group, $key ) {
		$values = self::get( $group );
		return isset( $values[ $key ] ) ? $values[ $key ] : null;
	}

	/**
	 * Replace a settings group.
	 *
	 * @param string $group  Group key.
	 * @param array  $values Already sanitized values.
	 */
	public static function update( $group, array $values ) {
		$settings           = self::get();
		$settings[ $group ] = $values;
		update_option( self::OPTION, $settings, true );
		self::$cache = null;
	}

	/**
	 * Clear the runtime cache (e.g. in tests).
	 */
	public static function flush() {
		self::$cache = null;
	}
}
