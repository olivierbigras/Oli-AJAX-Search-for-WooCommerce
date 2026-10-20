<?php
/**
 * Multicurrency price rendering.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formats product prices through WooCommerce filters.
 *
 * @since 0.1.0
 */
class Oli_AS_Compat_Multicurrency {

	/**
	 * Returns HTML price for a product using WooCommerce price APIs.
	 *
	 * Currency plugins that hook `woocommerce_product_get_price`,
	 * `wc_price`, or `woocommerce_get_price_html` remain in control.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public static function price_html( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$html = $product->get_price_html();
		return is_string( $html ) ? $html : '';
	}

	/**
	 * Numeric display price after WooCommerce filters.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product Product.
	 * @return float
	 */
	public static function display_price( $product ) {
		if ( ! $product instanceof WC_Product || ! function_exists( 'wc_get_price_to_display' ) ) {
			return 0.0;
		}

		return (float) wc_get_price_to_display( $product );
	}
}
