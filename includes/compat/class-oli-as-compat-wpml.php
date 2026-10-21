<?php
/**
 * WPML compatibility.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads WPML languages, translated posts, and permalinks.
 *
 * @since 0.1.0
 */
class Oli_AS_Compat_WPML {

	/**
	 * Whether WPML is available.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public static function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || has_filter( 'wpml_current_language' );
	}

	/**
	 * Current WPML language.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function current_language() {
		$lang = apply_filters( 'wpml_current_language', null );
		if ( is_string( $lang ) && '' !== $lang ) {
			return $lang;
		}
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			return (string) ICL_LANGUAGE_CODE;
		}
		return get_locale();
	}

	/**
	 * Active languages.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	public static function languages() {
		$items = apply_filters( 'wpml_active_languages', null );
		if ( ! is_array( $items ) ) {
			return array( self::current_language() );
		}

		$codes = array();
		foreach ( $items as $code => $data ) {
			$codes[] = is_array( $data ) && ! empty( $data['code'] ) ? (string) $data['code'] : (string) $code;
		}

		return empty( $codes ) ? array( self::current_language() ) : $codes;
	}

	/**
	 * Translated object ID for a language.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $id        Object ID.
	 * @param string $type      Element type.
	 * @param string $lang      Language.
	 * @return int
	 */
	public static function object_id( $id, $type, $lang ) {
		$translated = apply_filters( 'wpml_object_id', $id, $type, false, $lang );
		return $translated ? absint( $translated ) : 0;
	}

	/**
	 * WPML does not store string translations of post titles the same way as TRP.
	 *
	 * @since 0.1.0
	 *
	 * @param string $text Original text.
	 * @param string $lang Language.
	 * @return string
	 */
	public static function translate( $text, $lang ) {
		unset( $lang );
		return $text;
	}

	/**
	 * Language-specific permalink.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url  URL.
	 * @param string $lang Language.
	 * @return string
	 */
	public static function url( $url, $lang ) {
		$converted = apply_filters( 'wpml_permalink', $url, $lang, true );
		return is_string( $converted ) && '' !== $converted ? $converted : $url;
	}
}
