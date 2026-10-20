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

		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lang  = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$min   = (int) Oli_AS_Settings::get( 'min_chars', 2 );

		if ( mb_strlen( $query, 'UTF-8' ) < $min ) {
			wp_send_json_success(
				array(
					'query'       => $query,
					'language'    => Oli_AS_Language::instance()->from_request( $lang ),
					'groups'      => array(),
					'total'       => 0,
					'results_url' => '',
					'took_ms'     => 0,
				)
			);
		}

		$search = new Oli_AS_Search();
		$data   = $search->suggest( $query, $lang );
		Oli_AS_Analytics::instance()->record( $query, $data['language'], (int) $data['total'] );

		wp_send_json_success( $data );
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
}
