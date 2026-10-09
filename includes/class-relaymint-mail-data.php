<?php
/**
 * Helpers to normalize wp_mail() arguments.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalized representation of a wp_mail() call.
 */
class Relaymint_Mail_Data {

	/**
	 * Raw wp_mail() attributes (to, subject, message, headers, attachments).
	 *
	 * @var array
	 */
	public $atts;

	/**
	 * Parsed headers as list of [ name, value ] pairs.
	 *
	 * @var array<int,array{0:string,1:string}>
	 */
	public $headers = array();

	/**
	 * Constructor.
	 *
	 * @param array $atts wp_mail() attributes.
	 */
	public function __construct( array $atts ) {
		$this->atts = wp_parse_args(
			$atts,
			array(
				'to'          => array(),
				'subject'     => '',
				'message'     => '',
				'headers'     => array(),
				'attachments' => array(),
			)
		);

		$this->headers = self::parse_headers( $this->atts['headers'] );
	}

	/**
	 * Parse headers the same way wp_mail() does.
	 *
	 * @param string|array $headers Headers.
	 * @return array<int,array{0:string,1:string}>
	 */
	public static function parse_headers( $headers ) {
		if ( empty( $headers ) ) {
			return array();
		}
		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
		}

		$parsed = array();
		foreach ( $headers as $header ) {
			if ( ! is_string( $header ) || false === strpos( $header, ':' ) ) {
				continue;
			}
			list( $name, $value ) = explode( ':', trim( $header ), 2 );
			$parsed[]             = array( trim( $name ), trim( $value ) );
		}
		return $parsed;
	}

	/**
	 * All values of a header (case-insensitive).
	 *
	 * @param string $name Header name.
	 * @return string[]
	 */
	public function header_values( $name ) {
		$values = array();
		foreach ( $this->headers as $header ) {
			if ( 0 === strcasecmp( $header[0], $name ) ) {
				$values[] = $header[1];
			}
		}
		return $values;
	}

	/**
	 * Recipient addresses from the "to" argument.
	 *
	 * @return string[]
	 */
	public function to() {
		return self::split_addresses( $this->atts['to'] );
	}

	/**
	 * Addresses from a header such as Cc, Bcc or Reply-To.
	 *
	 * @param string $name Header name.
	 * @return string[]
	 */
	public function header_addresses( $name ) {
		$addresses = array();
		foreach ( $this->header_values( $name ) as $value ) {
			$addresses = array_merge( $addresses, self::split_addresses( $value ) );
		}
		return $addresses;
	}

	/**
	 * The From header as given to wp_mail(), or an empty string.
	 *
	 * @return string
	 */
	public function from() {
		$values = $this->header_values( 'From' );
		return $values ? (string) reset( $values ) : '';
	}

	/**
	 * Whether the message is sent as HTML.
	 *
	 * @return bool
	 */
	public function is_html() {
		foreach ( $this->header_values( 'Content-Type' ) as $value ) {
			if ( false !== stripos( $value, 'text/html' ) ) {
				return true;
			}
		}
		return 'text/html' === apply_filters( 'wp_mail_content_type', 'text/plain' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Headers as a single string (one per line).
	 *
	 * @return string
	 */
	public function headers_string() {
		$lines = array();
		foreach ( $this->headers as $header ) {
			$lines[] = $header[0] . ': ' . $header[1];
		}
		return implode( "\n", $lines );
	}

	/**
	 * Attachment file paths.
	 *
	 * @return string[]
	 */
	public function attachments() {
		$attachments = $this->atts['attachments'];
		if ( empty( $attachments ) ) {
			return array();
		}
		if ( ! is_array( $attachments ) ) {
			$attachments = explode( "\n", str_replace( "\r\n", "\n", (string) $attachments ) );
		}
		return array_values( array_filter( array_map( 'strval', $attachments ) ) );
	}

	/**
	 * Split a comma separated address list (string or array) into individual entries.
	 *
	 * @param string|array $addresses Address list.
	 * @return string[]
	 */
	public static function split_addresses( $addresses ) {
		if ( empty( $addresses ) ) {
			return array();
		}
		if ( ! is_array( $addresses ) ) {
			$addresses = explode( ',', (string) $addresses );
		}
		return array_values( array_filter( array_map( 'trim', array_map( 'strval', $addresses ) ) ) );
	}

	/**
	 * Determine which plugin, theme or core component triggered wp_mail().
	 *
	 * @return array{type:string,name:string,file:string}
	 */
	public static function detect_initiator() {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$file  = '';

		foreach ( $trace as $frame ) {
			if ( isset( $frame['function'] ) && 'wp_mail' === $frame['function'] && ! empty( $frame['file'] ) ) {
				$file = wp_normalize_path( $frame['file'] );
			}
		}

		return self::classify_file( $file );
	}

	/**
	 * Map a file path to a plugin/theme/core component.
	 *
	 * @param string $file Normalized file path.
	 * @return array{type:string,name:string,file:string}
	 */
	public static function classify_file( $file ) {
		$result = array(
			'type' => 'unknown',
			'name' => '',
			'file' => $file,
		);
		if ( '' === $file ) {
			return $result;
		}

		$locations = array(
			'plugin'    => wp_normalize_path( WP_PLUGIN_DIR ),
			'mu-plugin' => wp_normalize_path( WPMU_PLUGIN_DIR ),
			'theme'     => wp_normalize_path( get_theme_root() ),
		);

		foreach ( $locations as $type => $dir ) {
			if ( 0 === strpos( $file, trailingslashit( $dir ) ) ) {
				$relative       = substr( $file, strlen( trailingslashit( $dir ) ) );
				$parts          = explode( '/', $relative );
				$result['type'] = $type;
				$result['name'] = self::component_name( $type, $parts[0] );
				return $result;
			}
		}

		if ( false !== strpos( $file, '/wp-includes/' ) || false !== strpos( $file, '/wp-admin/' ) ) {
			$result['type'] = 'core';
			$result['name'] = 'WordPress';
		}

		return $result;
	}

	/**
	 * Readable name of a plugin or theme directory.
	 *
	 * @param string $type Component type.
	 * @param string $slug Directory or file name.
	 * @return string
	 */
	private static function component_name( $type, $slug ) {
		if ( 'theme' === $type ) {
			$theme = wp_get_theme( $slug );
			return $theme->exists() ? $theme->get( 'Name' ) : $slug;
		}

		if ( 'plugin' === $type ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins = get_plugins();
			foreach ( $plugins as $plugin_file => $data ) {
				if ( 0 === strpos( $plugin_file, $slug . '/' ) || $plugin_file === $slug ) {
					return $data['Name'];
				}
			}
		}

		return preg_replace( '/\.php$/', '', $slug );
	}
}
