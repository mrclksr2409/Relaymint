<?php
/**
 * Microsoft 365 / Outlook mailer (Microsoft Graph API).
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OAuth 2.0 against Microsoft Entra ID and sending via Microsoft Graph.
 *
 * Two authentication modes are supported:
 * - delegated:   an administrator signs in with the mailbox account once (authorization code
 *                flow with PKCE); the refresh token is stored encrypted and renewed automatically.
 *                Works for Microsoft 365 work/school accounts and personal Outlook.com accounts.
 * - application: client credentials flow with the application permission Mail.Send
 *                (Microsoft 365 only), emails are sent from the From Email mailbox.
 */
class Relaymint_Microsoft {

	/**
	 * Option holding the tokens per connection.
	 */
	const OPTION = 'relaymint_oauth';

	const AUTH_DELEGATED   = 'delegated';
	const AUTH_APPLICATION = 'application';

	const LOGIN_URL = 'https://login.microsoftonline.com/';
	const GRAPH_URL = 'https://graph.microsoft.com/v1.0/';

	/**
	 * Scopes requested in the delegated flow.
	 */
	const SCOPES = 'offline_access https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/User.Read';

	/**
	 * Transient prefix of pending authorization requests.
	 */
	const STATE_PREFIX = 'relaymint_ms_state_';

	/**
	 * Redirect URI that has to be registered in the Entra app.
	 *
	 * Microsoft does not allow query strings for every account type, so the plain
	 * admin-post.php URL is used and the callback is recognised by its state parameter.
	 *
	 * @return string
	 */
	public static function redirect_uri() {
		return admin_url( 'admin-post.php' );
	}

	/**
	 * Tenant values that address several tenants (not allowed for the application flow).
	 *
	 * @return string[]
	 */
	public static function multi_tenants() {
		return array( 'common', 'organizations', 'consumers' );
	}

	/**
	 * Sanitize a tenant ID or domain.
	 *
	 * @param string $tenant Raw tenant.
	 * @return string
	 */
	public static function sanitize_tenant( $tenant ) {
		$tenant = strtolower( trim( (string) $tenant ) );
		$tenant = preg_replace( '/[^a-z0-9.\-]/', '', $tenant );
		return '' !== $tenant ? $tenant : 'common';
	}

	/*
	 * ---------------------------------------------------------------------
	 * Token storage
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Fingerprint of the settings a token belongs to; tokens are discarded when they change.
	 *
	 * @param array $connection Resolved connection.
	 * @return string
	 */
	private static function fingerprint( array $connection ) {
		return md5( $connection['ms_auth'] . '|' . $connection['ms_tenant'] . '|' . $connection['ms_client_id'] );
	}

	/**
	 * Stored token data of a connection (still encrypted), if it matches the current settings.
	 *
	 * @param array $connection Resolved connection.
	 * @return array|null
	 */
	private static function stored( array $connection ) {
		$all = get_option( self::OPTION, array() );
		if ( ! is_array( $all ) || empty( $all[ $connection['id'] ] ) || ! is_array( $all[ $connection['id'] ] ) ) {
			return null;
		}
		$data = $all[ $connection['id'] ];
		if ( ! isset( $data['fingerprint'] ) || self::fingerprint( $connection ) !== $data['fingerprint'] ) {
			return null;
		}
		return $data;
	}

	/**
	 * Store token data of a connection.
	 *
	 * @param array $connection Resolved connection.
	 * @param array $data       Token data (access_token, refresh_token, expires, account).
	 */
	private static function store( array $connection, array $data ) {
		$all = get_option( self::OPTION, array() );
		$all = is_array( $all ) ? $all : array();

		$all[ $connection['id'] ] = array(
			'fingerprint'   => self::fingerprint( $connection ),
			'access_token'  => Relaymint_Secrets::encrypt( isset( $data['access_token'] ) ? $data['access_token'] : '' ),
			'refresh_token' => Relaymint_Secrets::encrypt( isset( $data['refresh_token'] ) ? $data['refresh_token'] : '' ),
			'expires'       => isset( $data['expires'] ) ? (int) $data['expires'] : 0,
			'account'       => isset( $data['account'] ) ? sanitize_text_field( $data['account'] ) : '',
		);
		update_option( self::OPTION, $all, false );
	}

	/**
	 * Remove the tokens of a connection.
	 *
	 * @param string $id Connection ID.
	 */
	public static function forget( $id ) {
		$all = get_option( self::OPTION, array() );
		if ( is_array( $all ) && isset( $all[ $id ] ) ) {
			unset( $all[ $id ] );
			update_option( self::OPTION, $all, false );
		}
	}

	/**
	 * Whether a delegated connection has been authorized (a refresh token exists).
	 *
	 * @param array $connection Resolved connection.
	 * @return bool
	 */
	public static function is_authorized( array $connection ) {
		$data = self::stored( $connection );
		return $data && '' !== Relaymint_Secrets::decrypt( $data['refresh_token'] );
	}

	/**
	 * Mailbox the delegated connection was authorized with.
	 *
	 * @param array $connection Resolved connection.
	 * @return string
	 */
	public static function account( array $connection ) {
		$data = self::stored( $connection );
		return $data ? (string) $data['account'] : '';
	}

	/*
	 * ---------------------------------------------------------------------
	 * Authorization code flow
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Build the Microsoft sign-in URL for a connection and remember the request.
	 *
	 * @param array $connection Resolved connection.
	 * @return string
	 */
	public static function authorize_url( array $connection ) {
		$state    = wp_generate_password( 32, false, false );
		$verifier = wp_generate_password( 64, false, false );

		set_transient(
			self::STATE_PREFIX . $state,
			array(
				'user'       => get_current_user_id(),
				'connection' => $connection['id'],
				'verifier'   => $verifier,
			),
			15 * MINUTE_IN_SECONDS
		);

		$challenge = rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		return add_query_arg(
			array_map(
				'rawurlencode',
				array(
					'client_id'             => $connection['ms_client_id'],
					'response_type'         => 'code',
					'redirect_uri'          => self::redirect_uri(),
					'response_mode'         => 'query',
					'scope'                 => self::SCOPES,
					'state'                 => $state,
					'code_challenge'        => $challenge,
					'code_challenge_method' => 'S256',
					'prompt'                => 'select_account',
				)
			),
			self::LOGIN_URL . rawurlencode( $connection['ms_tenant'] ) . '/oauth2/v2.0/authorize'
		);
	}

	/**
	 * Pending authorization request for a state value (consumed on read).
	 *
	 * @param string $state State parameter returned by Microsoft.
	 * @return array|null
	 */
	public static function consume_state( $state ) {
		$state = preg_replace( '/[^A-Za-z0-9]/', '', (string) $state );
		if ( '' === $state ) {
			return null;
		}
		$request = get_transient( self::STATE_PREFIX . $state );
		if ( ! is_array( $request ) ) {
			return null;
		}
		delete_transient( self::STATE_PREFIX . $state );
		return $request;
	}

	/**
	 * Exchange an authorization code for tokens and store them.
	 *
	 * @param array  $connection Resolved connection.
	 * @param string $code       Authorization code.
	 * @param string $verifier   PKCE code verifier.
	 * @return true|WP_Error
	 */
	public static function handle_code( array $connection, $code, $verifier ) {
		$tokens = self::token_request(
			$connection,
			array(
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'redirect_uri'  => self::redirect_uri(),
				'scope'         => self::SCOPES,
				'code_verifier' => $verifier,
			)
		);
		if ( is_wp_error( $tokens ) ) {
			return $tokens;
		}
		if ( empty( $tokens['refresh_token'] ) ) {
			return new WP_Error( 'relaymint_ms_no_refresh_token', __( 'Microsoft did not return a refresh token. Make sure the app may request the offline_access permission.', 'relaymint' ) );
		}

		$tokens['account'] = self::fetch_account( $tokens['access_token'] );
		self::store( $connection, $tokens );

		Relaymint_Debug_Log::add( sprintf( "Microsoft connection '%s' authorized for %s.", $connection['id'], $tokens['account'] ) );
		return true;
	}

	/**
	 * Mailbox address of the signed-in account.
	 *
	 * @param string $access_token Access token.
	 * @return string
	 */
	private static function fetch_account( $access_token ) {
		$response = wp_remote_get(
			self::GRAPH_URL . 'me?$select=mail,userPrincipalName',
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}
		$me = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $me['mail'] ) ) {
			return (string) $me['mail'];
		}
		return ! empty( $me['userPrincipalName'] ) ? (string) $me['userPrincipalName'] : '';
	}

	/*
	 * ---------------------------------------------------------------------
	 * Access tokens
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Call the token endpoint.
	 *
	 * @param array $connection Resolved connection.
	 * @param array $params     Grant specific parameters.
	 * @return array|WP_Error Token data with an absolute 'expires' timestamp.
	 */
	private static function token_request( array $connection, array $params ) {
		$response = wp_remote_post(
			self::LOGIN_URL . rawurlencode( $connection['ms_tenant'] ) . '/oauth2/v2.0/token',
			array(
				'timeout' => 20,
				'body'    => array_merge(
					array(
						'client_id'     => $connection['ms_client_id'],
						'client_secret' => $connection['ms_client_secret'],
					),
					$params
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['access_token'] ) ) {
			$message = is_array( $body ) && ! empty( $body['error_description'] ) ? $body['error_description'] : wp_remote_retrieve_response_message( $response );
			$code    = is_array( $body ) && ! empty( $body['error'] ) ? $body['error'] : (string) wp_remote_retrieve_response_code( $response );
			/* translators: 1: error code, 2: error message */
			return new WP_Error( 'relaymint_ms_token', sprintf( __( 'Microsoft sign-in failed (%1$s): %2$s', 'relaymint' ), $code, trim( strtok( (string) $message, "\r\n" ) ) ) );
		}

		return array(
			'access_token'  => (string) $body['access_token'],
			'refresh_token' => isset( $body['refresh_token'] ) ? (string) $body['refresh_token'] : '',
			'expires'       => time() + ( isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600 ),
		);
	}

	/**
	 * A valid access token for a connection, refreshed when needed.
	 *
	 * @param array $connection Resolved connection.
	 * @param bool  $force      Ignore a cached token.
	 * @return string|WP_Error
	 */
	public static function access_token( array $connection, $force = false ) {
		$data = self::stored( $connection );

		if ( ! $force && $data && $data['expires'] > time() + 60 ) {
			$token = Relaymint_Secrets::decrypt( $data['access_token'] );
			if ( '' !== $token ) {
				return $token;
			}
		}

		if ( self::AUTH_APPLICATION === $connection['ms_auth'] ) {
			$tokens = self::token_request(
				$connection,
				array(
					'grant_type' => 'client_credentials',
					'scope'      => 'https://graph.microsoft.com/.default',
				)
			);
			if ( is_wp_error( $tokens ) ) {
				return $tokens;
			}
			$tokens['refresh_token'] = '';
			$tokens['account']       = $connection['from_email'];
		} else {
			$refresh = $data ? Relaymint_Secrets::decrypt( $data['refresh_token'] ) : '';
			if ( '' === $refresh ) {
				return new WP_Error( 'relaymint_ms_not_authorized', __( 'The Microsoft connection is not authorized. Open the connection settings and click "Connect with Microsoft".', 'relaymint' ) );
			}
			$tokens = self::token_request(
				$connection,
				array(
					'grant_type'    => 'refresh_token',
					'refresh_token' => $refresh,
					'scope'         => self::SCOPES,
				)
			);
			if ( is_wp_error( $tokens ) ) {
				Relaymint_Debug_Log::add( sprintf( "Could not refresh the token of Microsoft connection '%s': %s", $connection['id'], $tokens->get_error_message() ), 'error' );
				return $tokens;
			}
			// Microsoft may rotate the refresh token; keep the old one otherwise.
			if ( '' === $tokens['refresh_token'] ) {
				$tokens['refresh_token'] = $refresh;
			}
			$tokens['account'] = $data['account'];
		}

		self::store( $connection, $tokens );
		return $tokens['access_token'];
	}

	/*
	 * ---------------------------------------------------------------------
	 * Sending
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Send a complete MIME message via Microsoft Graph.
	 *
	 * @param array  $connection Resolved connection.
	 * @param string $mime       MIME message including To/Cc/Bcc/Subject headers.
	 * @param string $from       Sender address (mailbox used in the application flow).
	 * @return true|WP_Error
	 */
	public static function send( array $connection, $mime, $from ) {
		if ( self::AUTH_APPLICATION === $connection['ms_auth'] ) {
			if ( ! is_email( $from ) ) {
				return new WP_Error( 'relaymint_ms_from', __( 'A valid From Email is required to send via the Microsoft Graph application permission.', 'relaymint' ) );
			}
			$url = self::GRAPH_URL . 'users/' . rawurlencode( $from ) . '/sendMail';
		} else {
			$url = self::GRAPH_URL . 'me/sendMail';
		}

		$payload = base64_encode( $mime ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Graph expects base64 encoded MIME.

		for ( $attempt = 0; $attempt < 2; $attempt++ ) {
			$token = self::access_token( $connection, $attempt > 0 );
			if ( is_wp_error( $token ) ) {
				return $token;
			}

			Relaymint_Mailer::trace( sprintf( 'POST %s (%s bytes MIME)', $url, number_format_i18n( strlen( $mime ) ) ) );

			$response = wp_remote_post(
				$url,
				array(
					'timeout' => 30,
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
						'Content-Type'  => 'text/plain',
					),
					'body'    => $payload,
				)
			);
			if ( is_wp_error( $response ) ) {
				Relaymint_Mailer::trace( 'HTTP error: ' . $response->get_error_message() );
				return $response;
			}

			$status = (int) wp_remote_retrieve_response_code( $response );
			Relaymint_Mailer::trace( sprintf( 'Microsoft Graph responded with HTTP %d.', $status ) );

			if ( 202 === $status || 200 === $status ) {
				return true;
			}
			// An expired or revoked access token: get a fresh one and retry once.
			if ( 401 !== $status ) {
				break;
			}
		}

		$body    = json_decode( wp_remote_retrieve_body( $response ), true );
		$code    = isset( $body['error']['code'] ) ? (string) $body['error']['code'] : (string) $status;
		$message = isset( $body['error']['message'] ) ? (string) $body['error']['message'] : wp_remote_retrieve_response_message( $response );
		Relaymint_Mailer::trace( 'Error: ' . $code . ' ' . $message );

		/* translators: 1: error code, 2: error message */
		return new WP_Error( 'relaymint_ms_send', sprintf( __( 'Microsoft Graph rejected the email (%1$s): %2$s', 'relaymint' ), $code, $message ) );
	}
}
