<?php
/**
 * Connections (primary + additional), sent via SMTP or Microsoft 365 / Outlook.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Access, sanitize and persist connections.
 */
class Relaymint_Connections {

	const PRIMARY = 'primary';

	const MAILER_SMTP      = 'smtp';
	const MAILER_MICROSOFT = 'microsoft';

	/**
	 * Fields of the primary connection that can be defined via wp-config.php constants.
	 *
	 * @var array<string,string> Field => constant name.
	 */
	private static $primary_constants = array(
		'host'             => 'RELAYMINT_SMTP_HOST',
		'port'             => 'RELAYMINT_SMTP_PORT',
		'encryption'       => 'RELAYMINT_SMTP_ENCRYPTION',
		'user'             => 'RELAYMINT_SMTP_USER',
		'pass'             => 'RELAYMINT_SMTP_PASS',
		'from_email'       => 'RELAYMINT_FROM_EMAIL',
		'from_name'        => 'RELAYMINT_FROM_NAME',
		'mailer'           => 'RELAYMINT_MAILER',
		'ms_tenant'        => 'RELAYMINT_MS_TENANT',
		'ms_client_id'     => 'RELAYMINT_MS_CLIENT_ID',
		'ms_client_secret' => 'RELAYMINT_MS_CLIENT_SECRET',
	);

	/**
	 * Available mailers.
	 *
	 * @return array<string,string>
	 */
	public static function mailers() {
		return array(
			self::MAILER_SMTP      => __( 'SMTP', 'relaymint' ),
			self::MAILER_MICROSOFT => __( 'Microsoft 365 / Outlook', 'relaymint' ),
		);
	}

	/**
	 * Whether a value is a known mailer (no translations involved, safe before init).
	 *
	 * @param mixed $mailer Mailer key.
	 * @return bool
	 */
	public static function is_mailer( $mailer ) {
		return in_array( $mailer, array( self::MAILER_SMTP, self::MAILER_MICROSOFT ), true );
	}

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
		$suffix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '_', $id ) );
		if ( 'pass' === $field ) {
			return 'RELAYMINT_SMTP_PASS_' . $suffix;
		}
		if ( 'ms_client_secret' === $field ) {
			return 'RELAYMINT_MS_CLIENT_SECRET_' . $suffix;
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

		$connection                     = $all[ $id ];
		$connection['id']               = $id;
		$connection['pass']             = Relaymint_Secrets::decrypt( $connection['pass'] );
		$connection['ms_client_secret'] = Relaymint_Secrets::decrypt( $connection['ms_client_secret'] );

		foreach ( array_keys( $connection ) as $field ) {
			if ( self::is_overridden( $id, $field ) ) {
				$connection[ $field ] = constant( self::constant_name( $id, $field ) );
			}
		}
		$connection['port']      = (int) $connection['port'];
		$connection['mailer']    = self::is_mailer( $connection['mailer'] ) ? $connection['mailer'] : self::MAILER_SMTP;
		$connection['ms_tenant'] = Relaymint_Microsoft::sanitize_tenant( $connection['ms_tenant'] );

		// Delegated Microsoft connections send as the signed-in mailbox unless a From Email is set.
		if ( self::MAILER_MICROSOFT === $connection['mailer'] && Relaymint_Microsoft::AUTH_DELEGATED === $connection['ms_auth'] && '' === trim( (string) $connection['from_email'] ) ) {
			$connection['from_email'] = Relaymint_Microsoft::account( $connection );
		}

		return $connection;
	}

	/**
	 * Whether a connection has enough data to be used.
	 *
	 * @param array|null $connection Resolved connection.
	 * @return bool
	 */
	public static function is_configured( $connection ) {
		if ( ! is_array( $connection ) ) {
			return false;
		}
		if ( self::MAILER_MICROSOFT === $connection['mailer'] ) {
			if ( '' === trim( (string) $connection['ms_client_id'] ) || '' === (string) $connection['ms_client_secret'] ) {
				return false;
			}
			if ( Relaymint_Microsoft::AUTH_APPLICATION === $connection['ms_auth'] ) {
				return is_email( $connection['from_email'] ) && ! in_array( $connection['ms_tenant'], Relaymint_Microsoft::multi_tenants(), true );
			}
			return Relaymint_Microsoft::is_authorized( $connection );
		}
		return '' !== trim( (string) $connection['host'] );
	}

	/**
	 * Short description of where a connection sends to (for lists).
	 *
	 * @param array $connection Stored connection.
	 * @return string
	 */
	public static function summary( array $connection ) {
		if ( self::MAILER_MICROSOFT === $connection['mailer'] ) {
			return __( 'Microsoft 365 / Outlook', 'relaymint' );
		}
		return '' !== $connection['host'] ? $connection['host'] . ':' . $connection['port'] : '—';
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
		$clean['mailer']           = isset( $input['mailer'] ) && self::is_mailer( $input['mailer'] ) ? $input['mailer'] : ( is_array( $existing ) && isset( $existing['mailer'] ) ? $existing['mailer'] : self::MAILER_SMTP );
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
		$clean['ms_auth']          = isset( $input['ms_auth'] ) && Relaymint_Microsoft::AUTH_APPLICATION === $input['ms_auth'] ? Relaymint_Microsoft::AUTH_APPLICATION : Relaymint_Microsoft::AUTH_DELEGATED;
		$clean['ms_tenant']        = Relaymint_Microsoft::sanitize_tenant( isset( $input['ms_tenant'] ) ? $input['ms_tenant'] : ( is_array( $existing ) && isset( $existing['ms_tenant'] ) ? $existing['ms_tenant'] : '' ) );
		$clean['ms_client_id']     = isset( $input['ms_client_id'] ) ? sanitize_text_field( $input['ms_client_id'] ) : ( is_array( $existing ) && isset( $existing['ms_client_id'] ) ? $existing['ms_client_id'] : '' );

		if ( $clean['port'] < 1 || $clean['port'] > 65535 ) {
			$clean['port'] = $defaults['port'];
		}

		$clean['pass']             = self::sanitize_secret( $input, $existing, 'pass' );
		$clean['ms_client_secret'] = self::sanitize_secret( $input, $existing, 'ms_client_secret' );

		return $clean;
	}

	/**
	 * Encrypt a submitted secret, keep the stored one when left empty, or clear it on request.
	 *
	 * @param array      $input    Unslashed form input.
	 * @param array|null $existing Stored connection.
	 * @param string     $field    Field name (pass, ms_client_secret).
	 * @return string Encrypted value.
	 */
	private static function sanitize_secret( array $input, $existing, $field ) {
		// Secrets are not sanitized as text (special characters must survive), only trimmed of line breaks.
		$value = isset( $input[ $field ] ) ? str_replace( array( "\r", "\n" ), '', (string) $input[ $field ] ) : '';
		if ( '' !== $value ) {
			return Relaymint_Secrets::encrypt( $value );
		}
		if ( ! empty( $input[ 'clear_' . $field ] ) ) {
			return '';
		}
		return is_array( $existing ) && isset( $existing[ $field ] ) ? $existing[ $field ] : '';
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
		Relaymint_Microsoft::forget( $id );

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
