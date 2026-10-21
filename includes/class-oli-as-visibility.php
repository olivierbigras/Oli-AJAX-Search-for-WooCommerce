<?php
/**
 * Product and content visibility rules for search.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keeps private, draft, hidden, and catalog-only products out of results.
 *
 * @since 0.2.0
 */
class Oli_AS_Visibility {

	/**
	 * Whether a WooCommerce product may appear in live search.
	 *
	 * @since 0.2.0
	 *
	 * @param WC_Product|false|null $product Product.
	 * @return bool
	 */
	public static function is_searchable_product( $product ) {
		if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_status' ) ) {
			return false;
		}

		if ( 'publish' !== $product->get_status() ) {
			return false;
		}

		if ( method_exists( $product, 'get_catalog_visibility' ) ) {
			$visibility = $product->get_catalog_visibility();
			if ( in_array( $visibility, array( 'hidden', 'catalog' ), true ) ) {
				return false;
			}
		} elseif ( method_exists( $product, 'is_visible' ) && ! $product->is_visible() ) {
			return false;
		}

		if ( method_exists( $product, 'get_id' ) ) {
			$post = get_post( $product->get_id() );
			if ( $post && ! empty( $post->post_password ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether a WordPress post may appear in live search.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post|null $post Post.
	 * @return bool
	 */
	public static function is_searchable_post( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( 'publish' !== $post->post_status ) {
			return false;
		}

		if ( ! empty( $post->post_password ) ) {
			return false;
		}

		return true;
	}
}
