<?php
/**
 * Main plugin class.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires all components together.
 */
final class Relaymint {

	const CLEANUP_HOOK = 'relaymint_daily_cleanup';

	/**
	 * Singleton instance.
	 *
	 * @var Relaymint|null
	 */
	private static $instance = null;

	/**
	 * Get the instance.
	 *
	 * @return Relaymint
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		Relaymint_Logger::init();
		Relaymint_Mailer::init();
		Relaymint_Interceptor::init();
		Relaymint_Queue::init();

		add_action( 'plugins_loaded', array( 'Relaymint_Installer', 'maybe_upgrade' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( self::CLEANUP_HOOK, array( $this, 'cleanup' ) );
		add_action( 'admin_init', array( $this, 'schedule_cleanup' ) );

		if ( is_admin() ) {
			require_once RELAYMINT_DIR . 'includes/admin/class-relaymint-admin.php';
			new Relaymint_Admin();
		}
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'relaymint', false, dirname( plugin_basename( RELAYMINT_FILE ) ) . '/languages' );
	}

	/**
	 * Make sure the daily housekeeping action exists.
	 */
	public function schedule_cleanup() {
		if ( get_transient( 'relaymint_cleanup_scheduled' ) || ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}
		if ( ! as_has_scheduled_action( self::CLEANUP_HOOK, null, Relaymint_Queue::GROUP ) ) {
			as_schedule_recurring_action( time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, self::CLEANUP_HOOK, array(), Relaymint_Queue::GROUP );
		}
		set_transient( 'relaymint_cleanup_scheduled', 1, DAY_IN_SECONDS );
	}

	/**
	 * Daily housekeeping.
	 */
	public function cleanup() {
		Relaymint_Logger::cleanup();
		Relaymint_Queue::cleanup();
	}
}
