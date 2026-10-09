<?php
/**
 * Database schema and lifecycle hooks.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates/updates tables and schedules housekeeping.
 */
class Relaymint_Installer {

	const DB_VERSION_OPTION = 'relaymint_db_version';

	/**
	 * Email log table name.
	 *
	 * @return string
	 */
	public static function log_table() {
		global $wpdb;
		return $wpdb->prefix . 'relaymint_email_log';
	}

	/**
	 * Queue table name.
	 *
	 * @return string
	 */
	public static function queue_table() {
		global $wpdb;
		return $wpdb->prefix . 'relaymint_queue';
	}

	/**
	 * Activation hook.
	 */
	public static function activate() {
		self::install();
	}

	/**
	 * Deactivation hook: remove scheduled actions of this plugin.
	 */
	public static function deactivate() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( '', array(), 'relaymint' );
		}
	}

	/**
	 * Run the installer when the stored DB version is outdated.
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_OPTION ) !== RELAYMINT_DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Create or update the database tables.
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$log_table       = self::log_table();
		$queue_table     = self::queue_table();

		$sql = "CREATE TABLE {$log_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			status varchar(20) NOT NULL DEFAULT '',
			subject text NOT NULL,
			from_email varchar(255) NOT NULL DEFAULT '',
			to_email text NOT NULL,
			cc text NOT NULL,
			bcc text NOT NULL,
			reply_to text NOT NULL,
			headers text NOT NULL,
			message longtext NOT NULL,
			content_type varchar(50) NOT NULL DEFAULT '',
			attachments text NOT NULL,
			connection varchar(64) NOT NULL DEFAULT '',
			initiator_type varchar(20) NOT NULL DEFAULT '',
			initiator_name varchar(191) NOT NULL DEFAULT '',
			initiator_file text NOT NULL,
			error text NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};
		CREATE TABLE {$queue_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			log_id bigint(20) unsigned NOT NULL DEFAULT 0,
			data longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'queued',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY status_sent (status,sent_at)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, RELAYMINT_DB_VERSION, true );

		if ( false === get_option( Relaymint_Options::OPTION ) ) {
			add_option( Relaymint_Options::OPTION, Relaymint_Options::defaults(), '', true );
		}
	}
}
