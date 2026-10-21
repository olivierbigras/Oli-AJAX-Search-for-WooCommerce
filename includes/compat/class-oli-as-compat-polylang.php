<?php
/**
 * Polylang compatibility.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads Polylang languages and permalinks.
 *
 * @since 0.1.0
 */
class Oli_AS_Compat_Polylang {

	/**
	 * Whether Polylang is available.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public static function is_active() {
		return function_exists( 'pll_current_language' );
	}

	/**
	 * Current Polylang language.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function current_language() {
		$lang = pll_current_language( 'slug' );
		return is_string( $lang ) && '' !== $lang ? $lang : get_locale();
	}

	/**
	 * Active languages.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	public static function languages() {
		if ( function_exists( 'pll_languages_list' ) ) {
			$list = pll_languages_list( array( 'fields' => 'slug' ) );
			if ( is_array( $list ) && ! empty( $list ) ) {
				return array_map( 'strval', $list );
			}
		}
		return array( self::current_language() );
	}

	/**
	 * Translated post ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $id   Post ID.
	 * @param string $lang Language.
	 * @return int
	 */
	public static function object_id( $id, $lang ) {
		if ( function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $id, $lang );
			return $translated ? absint( $translated ) : 0;
		}
		return 0;
	}

	/**
	 * Language-specific URL.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url  URL.
	 * @param string $lang Language.
	 * @return string
	 */
	public static function url( $url, $lang ) {
		if ( function_exists( 'PLL' ) ) {
			$pll = PLL();
			if ( is_object( $pll ) && isset( $pll->links ) && method_exists( $pll->links, 'switch_language_in_link' ) ) {
				$converted = $pll->links->switch_language_in_link( $url, $lang );
				if ( is_string( $converted ) && '' !== $converted ) {
					return $converted;
				}
			}
		}
		return $url;
	}
}
