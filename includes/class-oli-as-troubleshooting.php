<?php
/**
 * Local troubleshooting checks.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reports index, cron, and endpoint health without remote calls.
 *
 * @since 0.2.0
 */
class Oli_AS_Troubleshooting {

	/**
	 * Singleton.
	 *
	 * @since 0.2.0
	 * @var Oli_AS_Troubleshooting|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.2.0
	 *
	 * @return Oli_AS_Troubleshooting
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers hooks.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function init() {}

	/**
	 * Runs local checks.
	 *
	 * @since 0.2.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function run() {
		global $wpdb;

		$docs   = Oli_AS_Install::docs_table();
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $docs ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count  = 0;
		if ( $exists ) {
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$docs}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$status = Oli_AS_Indexer::instance()->get_status();
		$cron   = wp_next_scheduled( 'oli_as_cron_maintenance' );

		return array(
			array(
				'label'  => __( 'WooCommerce', 'oli-ajax-search' ),
				'ok'     => class_exists( 'WooCommerce' ),
				'detail' => class_exists( 'WooCommerce' ) ? __( 'Active', 'oli-ajax-search' ) : __( 'WooCommerce is required.', 'oli-ajax-search' ),
			),
			array(
				'label'  => __( 'Index tables', 'oli-ajax-search' ),
				'ok'     => (bool) $exists,
				'detail' => $exists ? __( 'Present', 'oli-ajax-search' ) : __( 'Missing. Deactivate and reactivate the plugin.', 'oli-ajax-search' ),
			),
			array(
				'label'  => __( 'Indexed documents', 'oli-ajax-search' ),
				'ok'     => $count > 0,
				'detail' => $count > 0 ? (string) $count : __( 'The index is empty. Rebuild it from the Indexer tab.', 'oli-ajax-search' ),
			),
			array(
				'label'  => __( 'Indexer state', 'oli-ajax-search' ),
				'ok'     => ! in_array( $status['state'], array( 'error' ), true ),
				'detail' => (string) $status['state'],
			),
			array(
				'label'  => __( 'WP-Cron maintenance', 'oli-ajax-search' ),
				'ok'     => (bool) $cron,
				'detail' => $cron ? wp_date( 'Y-m-d H:i', (int) $cron ) : __( 'Not scheduled.', 'oli-ajax-search' ),
			),
			array(
				'label'  => __( 'Search endpoint', 'oli-ajax-search' ),
				'ok'     => true,
				'detail' => home_url( '/oli-as/suggest' ),
			),
			array(
				'label'  => __( 'PHP version', 'oli-ajax-search' ),
				'ok'     => version_compare( PHP_VERSION, '7.4', '>=' ),
				'detail' => PHP_VERSION,
			),
		);
	}
}
