<?php
/**
 * Admin screens.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers menus, renders settings pages and handles form submissions.
 */
class Relaymint_Admin {

	const CAPABILITY = 'manage_options';
	const SLUG       = 'relaymint';

	/**
	 * Hook suffix of the email log page.
	 *
	 * @var string
	 */
	private $log_hook = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( RELAYMINT_FILE ), array( $this, 'action_links' ) );

		add_action( 'admin_post_relaymint_save', array( $this, 'handle_save' ) );
		add_action( 'admin_post_relaymint_delete_connection', array( $this, 'handle_delete_connection' ) );
		add_action( 'admin_post_relaymint_test_email', array( $this, 'handle_test_email' ) );
		add_action( 'admin_post_relaymint_clear_debug_log', array( $this, 'handle_clear_debug_log' ) );
		add_action( 'admin_post_relaymint_resend', array( $this, 'handle_resend' ) );
		add_action( 'admin_post_relaymint_delete_logs', array( $this, 'handle_delete_logs' ) );
		add_action( 'admin_post_relaymint_run_queue', array( $this, 'handle_run_queue' ) );

		add_action( 'admin_notices', array( $this, 'notices' ) );

		// Shared admin design (bundled WP-Backend UI) on all Relaymint screens.
		wpb_admin_ui_register(
			array(
				'pages' => array( self::SLUG, self::SLUG . '-log', self::SLUG . '-tools' ),
			)
		);
	}

	/**
	 * Admin menu.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Relaymint', 'relaymint' ),
			__( 'Relaymint', 'relaymint' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render_settings' ),
			'dashicons-email-alt',
			81
		);
		add_submenu_page( self::SLUG, __( 'Settings', 'relaymint' ), __( 'Settings', 'relaymint' ), self::CAPABILITY, self::SLUG, array( $this, 'render_settings' ) );
		$this->log_hook = add_submenu_page( self::SLUG, __( 'Email Log', 'relaymint' ), __( 'Email Log', 'relaymint' ), self::CAPABILITY, self::SLUG . '-log', array( $this, 'render_log' ) );
		add_submenu_page( self::SLUG, __( 'Tools', 'relaymint' ), __( 'Tools', 'relaymint' ), self::CAPABILITY, self::SLUG . '-tools', array( $this, 'render_tools' ) );

		add_action( 'load-' . $this->log_hook, array( $this, 'load_log_page' ) );
	}

	/**
	 * Settings link on the plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'relaymint' ) . '</a>' );
		return $links;
	}

	/**
	 * Enqueue assets on our pages only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, self::SLUG ) ) {
			return;
		}
		// Registers the 'wpb-admin-ui' handles (no-op if the library already enqueued them).
		WPB_Admin_UI::enqueue_assets();
		wp_enqueue_style( 'relaymint-admin', RELAYMINT_URL . 'assets/css/admin.css', array( WPB_Admin_UI::HANDLE ), RELAYMINT_VERSION );
		// Depends on the library script for data-wpb-confirm on destructive links.
		wp_enqueue_script( 'relaymint-admin', RELAYMINT_URL . 'assets/js/admin.js', array( WPB_Admin_UI::HANDLE ), RELAYMINT_VERSION, true );
		wp_localize_script(
			'relaymint-admin',
			'relaymintAdmin',
			array(
				'confirmDelete' => __( 'Are you sure?', 'relaymint' ),
			)
		);
	}

	/**
	 * URL of a Relaymint admin page.
	 *
	 * @param string $page Page slug suffix ('' = settings, 'log', 'tools').
	 * @param array  $args Query args.
	 * @return string
	 */
	public static function url( $page = '', array $args = array() ) {
		$slug = '' === $page ? self::SLUG : self::SLUG . '-' . $page;
		return add_query_arg( array_merge( array( 'page' => $slug ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * URL of the edit screen of a connection.
	 *
	 * @param string $id Connection ID or 'new'.
	 * @return string
	 */
	public static function connection_url( $id ) {
		return self::url(
			'',
			array(
				'tab'  => 'connections',
				'edit' => $id,
			)
		);
	}

	/**
	 * Print the shared page header (WP-Backend UI).
	 *
	 * @param string $title   Page title.
	 * @param array  $actions Header buttons, see WPB_Admin_UI::header().
	 */
	public static function header( $title, array $actions = array() ) {
		WPB_Admin_UI::header(
			array(
				'title'    => $title,
				'subtitle' => __( 'SMTP delivery, smart routing and email log', 'relaymint' ),
				'icon'     => 'dashicons-email-alt',
				'version'  => RELAYMINT_VERSION,
				'actions'  => $actions,
			)
		);
	}

	/**
	 * Status badge of a log entry.
	 *
	 * @param string $status Log status.
	 * @return string Escaped HTML.
	 */
	public static function status_badge( $status ) {
		$statuses = Relaymint_Logger::statuses();
		$variants = array(
			Relaymint_Logger::STATUS_SENT    => 'success',
			Relaymint_Logger::STATUS_FAILED  => 'error',
			Relaymint_Logger::STATUS_QUEUED  => 'warning',
			Relaymint_Logger::STATUS_SENDING => 'warning',
			Relaymint_Logger::STATUS_BLOCKED => 'neutral',
		);
		return WPB_Admin_UI::badge(
			isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status,
			isset( $variants[ $status ] ) ? $variants[ $status ] : 'neutral',
			true
		);
	}

	/**
	 * Settings tabs.
	 *
	 * @return array<string,string>
	 */
	public static function tabs() {
		return array(
			'general'     => __( 'General', 'relaymint' ),
			'connections' => __( 'Additional Connections', 'relaymint' ),
			'routing'     => __( 'Smart Routing', 'relaymint' ),
			'logs'        => __( 'Email Log', 'relaymint' ),
			'misc'        => __( 'Misc', 'relaymint' ),
		);
	}

	/**
	 * Abort unless the current user may manage the plugin and the nonce is valid.
	 *
	 * @param string $action Nonce action.
	 */
	private function verify( $action ) {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'relaymint' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Redirect after a form submission, carrying settings errors/messages.
	 *
	 * @param string $url Target URL.
	 */
	private function redirect( $url ) {
		set_transient( 'relaymint_notices_' . get_current_user_id(), get_settings_errors(), 60 );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Print notices stored before a redirect.
	 */
	public function notices() {
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, self::SLUG ) ) {
			return;
		}

		$key     = 'relaymint_notices_' . get_current_user_id();
		$notices = get_transient( $key );
		if ( is_array( $notices ) ) {
			delete_transient( $key );
			foreach ( $notices as $notice ) {
				printf(
					'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
					esc_attr( 'updated' === $notice['type'] || 'success' === $notice['type'] ? 'success' : $notice['type'] ),
					esc_html( $notice['message'] )
				);
			}
		}

		if ( Relaymint_Options::value( 'misc', 'do_not_send' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( '"Do Not Send" is enabled: no emails are sent from this site.', 'relaymint' ) . '</p></div>';
		}
		if ( ! Relaymint_Domain_Check::is_allowed() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html(
				sprintf(
					/* translators: %s: site domain */
					__( 'Domain check: %s is not an allowed domain, the SMTP settings are not used.', 'relaymint' ),
					Relaymint_Domain_Check::site_domain()
				)
			) . '</p></div>';
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Rendering
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Render the settings page.
	 */
	public function render_settings() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		$tabs = self::tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'general';
		}

		echo '<div class="wrap relaymint-wrap">';
		self::header( __( 'Relaymint Settings', 'relaymint' ) );
		WPB_Admin_UI::tabs(
			$tabs,
			$tab,
			array(
				'base_url'  => self::url(),
				'query_arg' => 'tab',
			)
		);

		include RELAYMINT_DIR . 'includes/admin/views/settings-' . $tab . '.php';

		echo '</div>';
	}

	/**
	 * Prepare the log page (bulk actions are processed before output).
	 */
	public function load_log_page() {
		require_once RELAYMINT_DIR . 'includes/admin/class-relaymint-log-list-table.php';

		// phpcs:disable WordPress.Security.NonceVerification -- verified via $this->verify() before deleting.
		$table  = new Relaymint_Log_List_Table();
		$action = $table->current_action();
		if ( 'delete' === $action && ! empty( $_REQUEST['log'] ) ) {
			$this->verify( 'bulk-relaymint_logs' );
			$deleted = Relaymint_Logger::delete( array_map( 'absint', (array) wp_unslash( $_REQUEST['log'] ) ) );
			/* translators: %d: number of entries */
			add_settings_error( 'relaymint', 'deleted', sprintf( _n( '%d log entry deleted.', '%d log entries deleted.', $deleted, 'relaymint' ), $deleted ), 'success' );
			$this->redirect( self::url( 'log' ) );
		}
		// phpcs:enable
	}

	/**
	 * Render the email log or a single entry.
	 */
	public function render_log() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view.
		$view_id = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		if ( $view_id ) {
			$entry = Relaymint_Logger::get( $view_id );
			include RELAYMINT_DIR . 'includes/admin/views/log-entry.php';
			return;
		}

		$table = new Relaymint_Log_List_Table();
		$table->prepare_items();
		include RELAYMINT_DIR . 'includes/admin/views/log-list.php';
	}

	/**
	 * Render the tools page.
	 */
	public function render_tools() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$test_result = get_transient( 'relaymint_test_result_' . get_current_user_id() );
		delete_transient( 'relaymint_test_result_' . get_current_user_id() );
		include RELAYMINT_DIR . 'includes/admin/views/tools.php';
	}

	/**
	 * Render the form fields of a connection.
	 *
	 * @param string $id         Connection ID ('' for a new connection).
	 * @param array  $connection Stored connection values.
	 */
	public static function render_connection_fields( $id, array $connection ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- used by the included view.
		include RELAYMINT_DIR . 'includes/admin/views/connection-fields.php';
	}

	/*
	 * ---------------------------------------------------------------------
	 * Form handlers
	 *
	 * Every handler calls $this->verify() (capability + check_admin_referer)
	 * before acting on input, which PHPCS cannot detect through the helper.
	 * ---------------------------------------------------------------------
	 */

	// phpcs:disable WordPress.Security.NonceVerification

	/**
	 * Save a settings tab.
	 */
	public function handle_save() {
		$this->verify( 'relaymint_save' );

		$tab  = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
		$args = array( 'tab' => $tab );

		switch ( $tab ) {
			case 'general':
				$input    = isset( $_POST['connection'] ) && is_array( $_POST['connection'] ) ? wp_unslash( $_POST['connection'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in Relaymint_Connections::sanitize().
				$existing = Relaymint_Connections::all();
				$clean    = Relaymint_Connections::sanitize( $input, $existing[ Relaymint_Connections::PRIMARY ] );

				$clean['name'] = 'Primary';
				Relaymint_Connections::save( Relaymint_Connections::PRIMARY, $clean );
				break;

			case 'connections':
				$input = isset( $_POST['connection'] ) && is_array( $_POST['connection'] ) ? wp_unslash( $_POST['connection'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in Relaymint_Connections::sanitize().
				$id    = isset( $_POST['connection_id'] ) ? sanitize_key( wp_unslash( $_POST['connection_id'] ) ) : '';

				if ( '' === $id || Relaymint_Connections::PRIMARY === $id || ! Relaymint_Connections::exists( $id ) ) {
					$id = Relaymint_Connections::generate_id();
				}
				$existing = Relaymint_Connections::all();
				$clean    = Relaymint_Connections::sanitize( $input, isset( $existing[ $id ] ) ? $existing[ $id ] : null );
				if ( '' === $clean['name'] ) {
					$clean['name'] = $clean['host'] ? $clean['host'] : $id;
				}
				Relaymint_Connections::save( $id, $clean );
				$args['edit'] = $id;
				break;

			case 'routing':
				$routes = isset( $_POST['routes'] ) ? wp_unslash( $_POST['routes'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in Relaymint_Router::sanitize_routes().
				Relaymint_Options::update(
					'routing',
					array(
						'enabled' => ! empty( $_POST['routing_enabled'] ),
						'routes'  => Relaymint_Router::sanitize_routes( $routes ),
					)
				);
				break;

			case 'logs':
				Relaymint_Options::update(
					'logs',
					array(
						'enabled'        => ! empty( $_POST['logs']['enabled'] ),
						'log_content'    => ! empty( $_POST['logs']['log_content'] ),
						'retention_days' => isset( $_POST['logs']['retention_days'] ) ? absint( $_POST['logs']['retention_days'] ) : 0,
					)
				);
				break;

			case 'misc':
				$misc  = isset( $_POST['misc'] ) && is_array( $_POST['misc'] ) ? wp_unslash( $_POST['misc'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
				$clean = array(
					'domain_check'           => ! empty( $misc['domain_check'] ),
					'domain_check_allowed'   => isset( $misc['domain_check_allowed'] ) ? implode( ', ', array_filter( array_map( array( 'Relaymint_Domain_Check', 'normalize' ), preg_split( '/[\s,]+/', sanitize_textarea_field( $misc['domain_check_allowed'] ) ) ) ) ) : '',
					'domain_check_block_all' => ! empty( $misc['domain_check_block_all'] ),
					'do_not_send'            => ! empty( $misc['do_not_send'] ),
					'allow_insecure_ssl'     => ! empty( $misc['allow_insecure_ssl'] ),
					'debug_log'              => ! empty( $misc['debug_log'] ),
					'optimize_sending'       => ! empty( $misc['optimize_sending'] ),
					'rate_limit'             => ! empty( $misc['rate_limit'] ) && ! empty( $misc['optimize_sending'] ),
					'update_channel'         => isset( $misc['update_channel'] ) && 'beta' === $misc['update_channel'] ? 'beta' : 'stable',
				);
				foreach ( array( 'minute', 'hour', 'day', 'week' ) as $period ) {
					$clean[ 'rate_limit_' . $period ] = isset( $misc[ 'rate_limit_' . $period ] ) ? absint( $misc[ 'rate_limit_' . $period ] ) : 0;
				}
				if ( $clean['domain_check'] && '' === $clean['domain_check_allowed'] ) {
					$clean['domain_check_allowed'] = Relaymint_Domain_Check::site_domain();
				}
				if ( ! empty( $misc['rate_limit'] ) && ! $clean['optimize_sending'] ) {
					add_settings_error( 'relaymint', 'rate_limit', __( 'Email rate limiting requires "Optimize Email Sending" and was not enabled.', 'relaymint' ), 'warning' );
				}
				$channel_changed = Relaymint_Options::value( 'misc', 'update_channel' ) !== $clean['update_channel'];
				Relaymint_Options::update( 'misc', $clean );

				// Update channel switched: drop cached update data so the next check uses the new source.
				if ( $channel_changed ) {
					global $relaymint_update_checker;
					if ( $relaymint_update_checker ) {
						$relaymint_update_checker->resetUpdateState();
					}
					delete_site_transient( 'update_plugins' );
				}

				// Queue disabled while emails are waiting: send them in the background anyway.
				if ( ! $clean['optimize_sending'] && Relaymint_Queue::count_pending() > 0 ) {
					Relaymint_Queue::schedule();
				}
				break;

			default:
				wp_die( esc_html__( 'Unknown settings tab.', 'relaymint' ) );
		}

		add_settings_error( 'relaymint', 'saved', __( 'Settings saved.', 'relaymint' ), 'success' );
		$this->redirect( self::url( '', $args ) );
	}

	/**
	 * Delete an additional connection.
	 */
	public function handle_delete_connection() {
		$id = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';
		$this->verify( 'relaymint_delete_connection_' . $id );

		if ( Relaymint_Connections::delete( $id ) ) {
			add_settings_error( 'relaymint', 'deleted', __( 'Connection deleted.', 'relaymint' ), 'success' );
		}
		$this->redirect( self::url( '', array( 'tab' => 'connections' ) ) );
	}

	/**
	 * Send a test email.
	 */
	public function handle_test_email() {
		$this->verify( 'relaymint_test_email' );

		$to         = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		$connection = isset( $_POST['connection'] ) ? sanitize_key( wp_unslash( $_POST['connection'] ) ) : Relaymint_Connections::PRIMARY;
		$html       = ! empty( $_POST['html'] );

		if ( ! is_email( $to ) ) {
			add_settings_error( 'relaymint', 'invalid', __( 'Please enter a valid email address.', 'relaymint' ) );
			$this->redirect( self::url( 'tools' ) );
		}
		if ( ! Relaymint_Connections::exists( $connection ) ) {
			$connection = Relaymint_Connections::PRIMARY;
		}

		$error = '';
		$catch = static function ( $wp_error ) use ( &$error ) {
			$error = $wp_error->get_error_message();
		};
		add_action( 'wp_mail_failed', $catch );

		Relaymint_Interceptor::$skip_queue       = true;
		Relaymint_Interceptor::$force_connection = $connection;
		Relaymint_Mailer::$force_debug           = true;

		/* translators: %s: site name */
		$subject = sprintf( __( 'Relaymint: Test email from %s', 'relaymint' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$body    = __( 'Congratulations, Relaymint is configured correctly and this test email was delivered.', 'relaymint' );
		/* translators: %s: connection name */
		$body   .= "\n\n" . sprintf( __( 'Connection: %s', 'relaymint' ), Relaymint_Connections::label( $connection ) );
		$headers = array();
		if ( $html ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
			$body      = '<html><body style="font-family:sans-serif"><h2>' . esc_html__( 'Relaymint test email', 'relaymint' ) . '</h2><p>' . nl2br( esc_html( $body ) ) . '</p></body></html>';
		}

		$sent = wp_mail( $to, $subject, $body, $headers );

		Relaymint_Interceptor::$skip_queue       = false;
		Relaymint_Interceptor::$force_connection = null;
		Relaymint_Mailer::$force_debug           = false;
		remove_action( 'wp_mail_failed', $catch );

		set_transient(
			'relaymint_test_result_' . get_current_user_id(),
			array(
				'success'    => (bool) $sent,
				'to'         => $to,
				'error'      => $error,
				'transcript' => Relaymint_Debug_Log::mask( Relaymint_Mailer::$transcript ),
			),
			5 * MINUTE_IN_SECONDS
		);
		$this->redirect( self::url( 'tools' ) );
	}

	/**
	 * Clear the debug log.
	 */
	public function handle_clear_debug_log() {
		$this->verify( 'relaymint_clear_debug_log' );
		Relaymint_Debug_Log::clear();
		add_settings_error( 'relaymint', 'cleared', __( 'Debug log cleared.', 'relaymint' ), 'success' );
		$this->redirect( self::url( 'tools' ) );
	}

	/**
	 * Resend a logged email.
	 */
	public function handle_resend() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$this->verify( 'relaymint_resend_' . $id );

		$entry = Relaymint_Logger::get( $id );
		if ( ! $entry || '' === $entry->message ) {
			add_settings_error( 'relaymint', 'resend', __( 'This email cannot be resent because its content was not logged.', 'relaymint' ) );
			$this->redirect( self::url( 'log' ) );
		}

		// Recipients from Cc/Bcc headers are part of the stored headers.
		$headers = Relaymint_Mail_Data::parse_headers( $entry->headers );
		$headers = array_map(
			static function ( $header ) {
				return $header[0] . ': ' . $header[1];
			},
			$headers
		);

		Relaymint_Interceptor::$skip_queue = true;
		$sent                              = wp_mail( $entry->to_email, $entry->subject, $entry->message, $headers );
		Relaymint_Interceptor::$skip_queue = false;

		if ( $sent ) {
			add_settings_error( 'relaymint', 'resend', __( 'Email resent.', 'relaymint' ), 'success' );
		} else {
			add_settings_error( 'relaymint', 'resend', __( 'The email could not be resent. Check the email log for details.', 'relaymint' ) );
		}
		$this->redirect( self::url( 'log' ) );
	}

	/**
	 * Delete all log entries.
	 */
	public function handle_delete_logs() {
		$this->verify( 'relaymint_delete_logs' );
		Relaymint_Logger::delete_all();
		add_settings_error( 'relaymint', 'deleted', __( 'All log entries deleted.', 'relaymint' ), 'success' );
		$this->redirect( self::url( 'log' ) );
	}

	/**
	 * Process the queue right now.
	 */
	public function handle_run_queue() {
		$this->verify( 'relaymint_run_queue' );
		Relaymint_Queue::process();
		add_settings_error( 'relaymint', 'queue', __( 'Queue processed.', 'relaymint' ), 'success' );
		$this->redirect( self::url( '', array( 'tab' => 'misc' ) ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification
}
