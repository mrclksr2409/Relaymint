<?php
/**
 * SMTP connections (primary + additional).
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Access, sanitize and persist SMTP connections.
 */
class Relaymint_Connections {

	const PRIMARY = 'primary';

	/**
	 * Fields of the primary connection that can be defined via wp-config.php constants.
	 *
	 * @var array<string,string> Field => constant name.
	 */
	private static $primary_constants = array(
		'host'       => 'RELAYMINT_SMTP_HOST',
		'port'       => 'RELAYMINT_SMTP_PORT',
		'encryption' => 'RELAYMINT_SMTP_ENCRYPTION',
		'user'       => 'RELAYMINT_SMTP_USER',
		'pass'       => 'RELAYMINT_SMTP_PASS',
		'from_email' => 'RELAYMINT_FROM_EMAIL',
		'from_name'  => 'RELAYMINT_FROM_NAME',
	);

	/**
	 * All stored connections (raw, password encrypted).
	 *
	 * @return array<string,array>
	 */
	public static function all() {
		return Relaymint_Options::get( 'connections' );
	}

	/**
	 * Additional connections only.
	 *
	 * @return array<string,array>
	 */
	public static function additional() {
		$all = self::all();
		unset( $all[ self::PRIMARY ] );
		return $all;
	}

	/**
	 * Whether a connection exists.
	 *
	 * @param string $id Connection ID.
	 * @return bool
	 */
	public static function exists( $id ) {
		$all = self::all();
		return isset( $all[ $id ] );
	}

	/**
	 * Name of the constant that overrides a field, if any.
	 *
	 * @param string $id    Connection ID.
	 * @param string $field Field name.
	 * @return string|null
	 */
	public static function constant_name( $id, $field ) {
		if ( self::PRIMARY === $id ) {
			return isset( self::$primary_constants[ $field ] ) ? self::$primary_constants[ $field ] : null;
		}
		if ( 'pass' === $field ) {
			return 'RELAYMINT_SMTP_PASS_' . strtoupper( preg_replace( '/[^A-Za-z0-9]/', '_', $id ) );
		}
		return null;
	}

	/**
	 * Whether a field is overridden by a constant in wp-config.php.
	 *
	 * @param string $id    Connection ID.
	 * @param string $field Field name.
	 * @return bool
	 */
	public static function is_overridden( $id, $field ) {
		$constant = self::constant_name( $id, $field );
		return null !== $constant && defined( $constant );
	}

	/**
	 * Resolved connection, ready to use (constants applied, password decrypted).
	 *
	 * @param string $id Connection ID.
	 * @return array|null
	 */
	public static function get( $id ) {
		$all = self::all();
		if ( ! isset( $all[ $id ] ) ) {
			return null;
		}

		$connection         = $all[ $id ];
		$connection['id']   = $id;
		$connection['pass'] = Relaymint_Secrets::decrypt( $connection['pass'] );

		foreach ( array_keys( $connection ) as $field ) {
			if ( self::is_overridden( $id, $field ) ) {
				$connection[ $field ] = constant( self::constant_name( $id, $field ) );
			}
		}
		$connection['port'] = (int) $connection['port'];

		return $connection;
	}

	/**
	 * Whether a connection has enough data to be used.
	 *
	 * @param array|null $connection Resolved connection.
	 * @return bool
	 */
	public static function is_configured( $connection ) {
		return is_array( $connection ) && '' !== trim( (string) $connection['host'] );
	}

	/**
	 * Human readable label.
	 *
	 * @param string $id Connection ID.
	 * @return string
	 */
	public static function label( $id ) {
		$all = self::all();
		if ( ! isset( $all[ $id ] ) ) {
			return $id;
		}
		if ( self::PRIMARY === $id ) {
			return __( 'Primary connection', 'relaymint' );
		}
		return '' !== $all[ $id ]['name'] ? $all[ $id ]['name'] : $id;
	}

	/**
	 * Sanitize raw form input for a connection.
	 *
	 * @param array      $input    Unslashed form input.
	 * @param array|null $existing Stored connection (to keep the password when left empty).
	 * @return array
	 */
	public static function sanitize( array $input, $existing = null ) {
		$defaults = Relaymint_Options::connection_defaults();
		$clean    = array();

		$clean['name']             = isset( $input['name'] ) ? sanitize_text_field( $input['name'] ) : '';
		$clean['from_email']       = isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : '';
		$clean['from_name']        = isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : '';
		$clean['force_from_email'] = ! empty( $input['force_from_email'] );
		$clean['force_from_name']  = ! empty( $input['force_from_name'] );
		$clean['return_path']      = ! empty( $input['return_path'] );
		$clean['host']             = isset( $input['host'] ) ? sanitize_text_field( $input['host'] ) : '';
		$clean['port']             = isset( $input['port'] ) ? absint( $input['port'] ) : $defaults['port'];
		$clean['encryption']       = isset( $input['encryption'] ) && in_array( $input['encryption'], array( 'none', 'ssl', 'tls' ), true ) ? $input['encryption'] : 'tls';
		$clean['autotls']          = ! empty( $input['autotls'] );
		$clean['auth']             = ! empty( $input['auth'] );
		$clean['user']             = isset( $input['user'] ) ? sanitize_text_field( $input['user'] ) : '';

		if ( $clean['port'] < 1 || $clean['port'] > 65535 ) {
			$clean['port'] = $defaults['port'];
		}

		// Passwords are not sanitized as text (special characters must survive), only trimmed of line breaks.
		$pass = isset( $input['pass'] ) ? str_replace( array( "\r", "\n" ), '', (string) $input['pass'] ) : '';
		if ( '' !== $pass ) {
			$clean['pass'] = Relaymint_Secrets::encrypt( $pass );
		} elseif ( ! empty( $input['clear_pass'] ) ) {
			$clean['pass'] = '';
		} else {
			$clean['pass'] = is_array( $existing ) && isset( $existing['pass'] ) ? $existing['pass'] : '';
		}

		return $clean;
	}

	/**
	 * Save a connection.
	 *
	 * @param string $id         Connection ID.
	 * @param array  $connection Sanitized connection.
	 */
	public static function save( $id, array $connection ) {
		$all        = self::all();
		$all[ $id ] = $connection;
		Relaymint_Options::update( 'connections', $all );
	}

	/**
	 * Delete an additional connection (the primary connection cannot be deleted).
	 *
	 * @param string $id Connection ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		if ( self::PRIMARY === $id ) {
			return false;
		}
		$all = self::all();
		if ( ! isset( $all[ $id ] ) ) {
			return false;
		}
		unset( $all[ $id ] );
		Relaymint_Options::update( 'connections', $all );

		// Drop routes that point to the deleted connection.
		$routing = Relaymint_Options::get( 'routing' );
		if ( ! empty( $routing['routes'] ) ) {
			$routing['routes'] = array_values(
				array_filter(
					$routing['routes'],
					static function ( $route ) use ( $id ) {
						return isset( $route['connection'] ) && $route['connection'] !== $id;
					}
				)
			);
			Relaymint_Options::update( 'routing', $routing );
		}

		return true;
	}

	/**
	 * Generate a new unique connection ID.
	 *
	 * @return string
	 */
	public static function generate_id() {
		do {
			$id = 'c' . strtolower( wp_generate_password( 8, false, false ) );
		} while ( self::exists( $id ) );
		return $id;
	}
}
