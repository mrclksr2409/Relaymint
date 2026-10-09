<?php
/**
 * Smart routing.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Picks the SMTP connection for an email based on conditional rules.
 *
 * A route contains condition groups. Conditions inside a group are combined with AND,
 * groups are combined with OR. The first matching enabled route wins.
 */
class Relaymint_Router {

	/**
	 * Fields a condition can test.
	 *
	 * @return array<string,string>
	 */
	public static function fields() {
		return array(
			'subject'   => __( 'Subject', 'relaymint' ),
			'message'   => __( 'Message', 'relaymint' ),
			'from'      => __( 'From', 'relaymint' ),
			'to'        => __( 'To', 'relaymint' ),
			'cc'        => __( 'CC', 'relaymint' ),
			'bcc'       => __( 'BCC', 'relaymint' ),
			'reply_to'  => __( 'Reply-To', 'relaymint' ),
			'header'    => __( 'Header', 'relaymint' ),
			'initiator' => __( 'Initiator (plugin/theme)', 'relaymint' ),
		);
	}

	/**
	 * Supported operators.
	 *
	 * @return array<string,string>
	 */
	public static function operators() {
		return array(
			'contains'     => __( 'contains', 'relaymint' ),
			'not_contains' => __( 'does not contain', 'relaymint' ),
			'is'           => __( 'is', 'relaymint' ),
			'is_not'       => __( 'is not', 'relaymint' ),
			'starts_with'  => __( 'starts with', 'relaymint' ),
			'ends_with'    => __( 'ends with', 'relaymint' ),
			'regex'        => __( 'matches regex', 'relaymint' ),
			'not_regex'    => __( 'does not match regex', 'relaymint' ),
		);
	}

	/**
	 * Resolve the connection ID for an email.
	 *
	 * @param Relaymint_Mail_Data $mail      Mail data.
	 * @param array               $initiator Initiator info.
	 * @return string Connection ID.
	 */
	public static function resolve( Relaymint_Mail_Data $mail, array $initiator ) {
		$connection = Relaymint_Connections::PRIMARY;
		$routing    = Relaymint_Options::get( 'routing' );

		if ( ! empty( $routing['enabled'] ) && ! empty( $routing['routes'] ) ) {
			foreach ( $routing['routes'] as $route ) {
				if ( empty( $route['enabled'] ) || empty( $route['connection'] ) ) {
					continue;
				}
				if ( ! Relaymint_Connections::is_configured( Relaymint_Connections::get( $route['connection'] ) ) ) {
					continue;
				}
				if ( self::route_matches( $route, $mail, $initiator ) ) {
					$connection = $route['connection'];
					break;
				}
			}
		}

		/**
		 * Filter the connection used for an email.
		 *
		 * @param string              $connection Connection ID.
		 * @param Relaymint_Mail_Data $mail       Mail data.
		 * @param array               $initiator  Initiator info.
		 */
		return (string) apply_filters( 'relaymint_route_connection', $connection, $mail, $initiator );
	}

	/**
	 * Whether a route matches.
	 *
	 * @param array               $route     Route.
	 * @param Relaymint_Mail_Data $mail      Mail data.
	 * @param array               $initiator Initiator info.
	 * @return bool
	 */
	public static function route_matches( array $route, Relaymint_Mail_Data $mail, array $initiator ) {
		if ( empty( $route['groups'] ) ) {
			return false;
		}
		foreach ( $route['groups'] as $group ) {
			if ( empty( $group ) ) {
				continue;
			}
			$all = true;
			foreach ( $group as $condition ) {
				if ( ! self::condition_matches( $condition, $mail, $initiator ) ) {
					$all = false;
					break;
				}
			}
			if ( $all ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Evaluate a single condition.
	 *
	 * @param array               $condition Condition (field, operator, value).
	 * @param Relaymint_Mail_Data $mail      Mail data.
	 * @param array               $initiator Initiator info.
	 * @return bool
	 */
	public static function condition_matches( array $condition, Relaymint_Mail_Data $mail, array $initiator ) {
		$field    = isset( $condition['field'] ) ? $condition['field'] : '';
		$operator = isset( $condition['operator'] ) ? $condition['operator'] : '';
		$expected = isset( $condition['value'] ) ? (string) $condition['value'] : '';
		$values   = self::field_values( $field, $mail, $initiator );

		$negated = in_array( $operator, array( 'not_contains', 'is_not', 'not_regex' ), true );
		$base_op = array(
			'not_contains' => 'contains',
			'is_not'       => 'is',
			'not_regex'    => 'regex',
		);
		$test_op = $negated ? $base_op[ $operator ] : $operator;

		// An empty field (e.g. no CC) is treated as a single empty value.
		if ( ! $values ) {
			$values = array( '' );
		}

		$any = false;
		foreach ( $values as $value ) {
			if ( self::compare( (string) $value, $test_op, $expected ) ) {
				$any = true;
				break;
			}
		}

		return $negated ? ! $any : $any;
	}

	/**
	 * Values of a field for comparison.
	 *
	 * @param string              $field     Field key.
	 * @param Relaymint_Mail_Data $mail      Mail data.
	 * @param array               $initiator Initiator info.
	 * @return string[]
	 */
	private static function field_values( $field, Relaymint_Mail_Data $mail, array $initiator ) {
		switch ( $field ) {
			case 'subject':
				return array( (string) $mail->atts['subject'] );
			case 'message':
				return array( (string) $mail->atts['message'] );
			case 'from':
				return array( $mail->from() );
			case 'to':
				return $mail->to();
			case 'cc':
				return $mail->header_addresses( 'Cc' );
			case 'bcc':
				return $mail->header_addresses( 'Bcc' );
			case 'reply_to':
				return $mail->header_addresses( 'Reply-To' );
			case 'header':
				return array_map(
					static function ( $header ) {
						return $header[0] . ': ' . $header[1];
					},
					$mail->headers
				);
			case 'initiator':
				return array_filter( array( isset( $initiator['name'] ) ? $initiator['name'] : '', isset( $initiator['file'] ) ? $initiator['file'] : '' ) );
		}
		return array();
	}

	/**
	 * Compare a value (case-insensitive except for regex).
	 *
	 * @param string $value    Actual value.
	 * @param string $operator Positive operator.
	 * @param string $expected Expected value.
	 * @return bool
	 */
	private static function compare( $value, $operator, $expected ) {
		$haystack = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );
		$needle   = function_exists( 'mb_strtolower' ) ? mb_strtolower( $expected ) : strtolower( $expected );

		switch ( $operator ) {
			case 'contains':
				return '' !== $needle && false !== strpos( $haystack, $needle );
			case 'is':
				return $haystack === $needle;
			case 'starts_with':
				return '' !== $needle && 0 === strpos( $haystack, $needle );
			case 'ends_with':
				return '' !== $needle && substr( $haystack, -strlen( $needle ) ) === $needle;
			case 'regex':
				return self::is_valid_regex( $expected ) && 1 === preg_match( $expected, $value );
		}
		return false;
	}

	/**
	 * Whether a string is a valid PCRE pattern including delimiters.
	 *
	 * @param string $pattern Pattern.
	 * @return bool
	 */
	public static function is_valid_regex( $pattern ) {
		if ( '' === $pattern ) {
			return false;
		}
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- invalid patterns emit warnings by design.
		return false !== @preg_match( $pattern, '' );
	}

	/**
	 * Sanitize routes coming from the admin form.
	 *
	 * @param mixed $routes Raw routes.
	 * @return array
	 */
	public static function sanitize_routes( $routes ) {
		$clean      = array();
		$fields     = array_keys( self::fields() );
		$operators  = array_keys( self::operators() );
		$connection = Relaymint_Connections::additional();

		if ( ! is_array( $routes ) ) {
			return $clean;
		}

		foreach ( $routes as $route ) {
			if ( ! is_array( $route ) || empty( $route['connection'] ) || ! isset( $connection[ $route['connection'] ] ) ) {
				continue;
			}

			$groups = array();
			if ( ! empty( $route['groups'] ) && is_array( $route['groups'] ) ) {
				foreach ( $route['groups'] as $group ) {
					$conditions = array();
					foreach ( (array) $group as $condition ) {
						if ( ! is_array( $condition ) ) {
							continue;
						}
						$field    = isset( $condition['field'] ) ? sanitize_key( $condition['field'] ) : '';
						$operator = isset( $condition['operator'] ) ? sanitize_key( $condition['operator'] ) : '';
						// Values are compared as plain strings; keep regex characters intact.
						$value = isset( $condition['value'] ) ? trim( wp_check_invalid_utf8( (string) $condition['value'] ) ) : '';

						if ( ! in_array( $field, $fields, true ) || ! in_array( $operator, $operators, true ) ) {
							continue;
						}
						if ( in_array( $operator, array( 'regex', 'not_regex' ), true ) && ! self::is_valid_regex( $value ) ) {
							add_settings_error(
								'relaymint',
								'invalid_regex',
								/* translators: %s: regular expression */
								sprintf( __( 'The regular expression %s is invalid and was skipped.', 'relaymint' ), $value )
							);
							continue;
						}
						$conditions[] = array(
							'field'    => $field,
							'operator' => $operator,
							'value'    => $value,
						);
					}
					if ( $conditions ) {
						$groups[] = $conditions;
					}
				}
			}

			$clean[] = array(
				'enabled'    => ! empty( $route['enabled'] ),
				'connection' => (string) $route['connection'],
				'groups'     => $groups,
			);
		}

		return $clean;
	}
}
