<?php
/**
 * WooCommerce search results page integration.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Applies the same index relevance to product search archives.
 *
 * @since 0.1.0
 */
class Oli_AS_Results_Page {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Results_Page|null
	 */
	protected static $instance = null;

	/**
	 * Cached product IDs for the current request.
	 *
	 * @since 0.1.0
	 * @var array<int, int>|null
	 */
	protected $ids = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Results_Page
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers query hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'pre_get_posts', array( $this, 'apply_index' ), 20 );
	}

	/**
	 * Replaces the native product search with index IDs.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public function apply_index( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		if ( ! Oli_AS_Settings::enabled( 'use_on_results_page' ) ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$forced    = isset( $_GET['oli_as'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['oli_as'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $forced && 'product' !== $post_type && ! ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) ) {
			return;
		}

		$search = $query->get( 's' );
		if ( ! is_string( $search ) || '' === $search ) {
			return;
		}

		$engine    = new Oli_AS_Search();
		$ids       = $engine->product_ids( $search, Oli_AS_Language::instance()->current(), 200 );
		$this->ids = $ids;

		if ( empty( $ids ) ) {
			$query->set( 'post__in', array( 0 ) );
			$query->set( 's', '' );
			return;
		}

		$query->set( 'post_type', 'product' );
		$query->set( 'post__in', $ids );
		$query->set( 'orderby', 'post__in' );
		$query->set( 's', '' );
	}
}
