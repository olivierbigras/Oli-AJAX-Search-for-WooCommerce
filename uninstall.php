<?php
/**
 * Uninstall cleanup.
 *
 * @package Oli_Ajax_Search
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$tables = array(
	$wpdb->prefix . 'oli_as_docs',
	$wpdb->prefix . 'oli_as_inv',
	$wpdb->prefix . 'oli_as_terms',
	$wpdb->prefix . 'oli_as_stats',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

delete_option( 'oli_as_settings' );
delete_option( 'oli_as_db_version' );
delete_option( 'oli_as_index_status' );

wp_clear_scheduled_hook( 'oli_as_cron_maintenance' );
wp_clear_scheduled_hook( 'oli_as_index_batch' );
