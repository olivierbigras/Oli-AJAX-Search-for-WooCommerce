<?php
/**
 * Admin-ajax fallback for suggestions and admin tools.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * AJAX endpoints that stay on the current language host.
 *
 * @since 0.1.0
 */
class Oli_AS_Ajax {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Ajax|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Ajax
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers AJAX actions.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_oli_as_suggest', array( $this, 'suggest' ) );
		add_action( 'wp_ajax_nopriv_oli_as_suggest', array( $this, 'suggest' ) );
		add_action( 'wp_ajax_oli_as_rebuild_index', array( $this, 'rebuild_index' ) );
		add_action( 'wp_ajax_oli_as_index_status', array( $this, 'index_status' ) );
		add_action( 'wp_ajax_oli_as_clear_analytics', array( $this, 'clear_analytics' ) );
		add_action( 'wp_ajax_oli_as_toggle_advanced', array( $this, 'toggle_advanced' ) );
		add_action( 'admin_post_oli_as_export_analytics', array( $this, 'export_analytics' ) );
	}

	/**
	 * Public suggestion action.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function suggest() {
		check_ajax_referer( 'oli_as_search', 'nonce' );

		$query  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lang   = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$result = Oli_AS_Rest::instance()->run_suggest( $query, $lang );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 429 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Starts a rebuild.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function rebuild_index() {
		check_ajax_referer( 'oli_as_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to rebuild the index.', 'oli-ajax-search' ) ), 403 );
		}

		$status = Oli_AS_Indexer::instance()->start_rebuild();
		wp_send_json_success( $status );
	}

	/**
	 * Returns index progress.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function index_status() {
		check_ajax_referer( 'oli_as_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to view the index.', 'oli-ajax-search' ) ), 403 );
		}

		$status                  = Oli_AS_Indexer::instance()->get_status();
		$status['indexed_count'] = Oli_AS_Indexer::instance()->indexed_count();
		wp_send_json_success( $status );
	}

	/**
	 * Clears analytics rows.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function clear_analytics() {
		check_ajax_referer( 'oli_as_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to clear analytics.', 'oli-ajax-search' ) ), 403 );
		}

		Oli_AS_Analytics::instance()->clear();
		wp_send_json_success( array( 'message' => __( 'Analytics cleared.', 'oli-ajax-search' ) ) );
	}

	/**
	 * Persists the advanced settings toggle.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function toggle_advanced() {
		check_ajax_referer( 'oli_as_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to change this setting.', 'oli-ajax-search' ) ), 403 );
		}

		$on                       = ! empty( $_POST['on'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$current                  = Oli_AS_Settings::get_all();
		$current['show_advanced'] = $on ? '1' : '0';
		update_option( Oli_AS_Settings::OPTION, $current, false );
		Oli_AS_Settings::flush();

		wp_send_json_success( array( 'on' => $on ) );
	}

	/**
	 * Exports analytics as CSV.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function export_analytics() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to export analytics.', 'oli-ajax-search' ) );
		}

		check_admin_referer( 'oli_as_export_analytics' );

		$rows     = Oli_AS_Analytics::instance()->export_rows();
		$filename = 'oli-ajax-search-analytics-' . gmdate( 'Y-m-d' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			wp_die( esc_html__( 'Unable to write the CSV file.', 'oli-ajax-search' ) );
		}

		fputcsv( $out, array( 'query', 'language', 'hits', 'result_count', 'last_hit' ) );
		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				array(
					$row->query_text,
					$row->language,
					$row->hits,
					$row->result_count,
					$row->last_hit,
				)
			);
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $out );
		exit;
	}
}
