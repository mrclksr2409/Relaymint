<?php
/**
 * Uninstall Relaymint: remove options, tables, scheduled actions and queued attachments.
 *
 * @package Relaymint
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'relaymint_settings' );
delete_option( 'relaymint_db_version' );
delete_option( 'relaymint_debug_log' );
delete_transient( 'relaymint_cleanup_scheduled' );

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}relaymint_email_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}relaymint_queue" );
// phpcs:enable

// Action Scheduler is not loaded during uninstall, so remove pending actions directly.
$relaymint_as_table = $wpdb->prefix . 'actionscheduler_actions';
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $relaymint_as_table ) ) === $relaymint_as_table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( "DELETE FROM {$relaymint_as_table} WHERE hook IN ('relaymint_process_queue','relaymint_daily_cleanup') AND status = 'pending'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$relaymint_uploads = wp_upload_dir( null, false );
$relaymint_dir     = trailingslashit( $relaymint_uploads['basedir'] ) . 'relaymint-queue';
if ( is_dir( $relaymint_dir ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	if ( WP_Filesystem() ) {
		global $wp_filesystem;
		$wp_filesystem->delete( $relaymint_dir, true );
	}
}
