<?php
/**
 * QTranslate-XT compatibility.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads qTranslate-XT languages and split multilingual strings.
 *
 * @since 0.2.0
 */
class Oli_AS_Compat_Qtranslate {

	/**
	 * Whether qTranslate-XT is available.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public static function is_active() {
		return function_exists( 'qtranxf_getLanguage' ) || function_exists( 'qtrans_getLanguage' );
	}

	/**
	 * Registers language filters.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! self::is_active() ) {
			return;
		}

		add_filter( 'oli_as_current_language', array( __CLASS__, 'filter_current' ), 5 );
		add_filter( 'oli_as_languages', array( __CLASS__, 'filter_languages' ), 5 );
		add_filter( 'oli_as_translate_text', array( __CLASS__, 'filter_text' ), 5, 3 );
		add_filter( 'oli_as_translate_html', array( __CLASS__, 'filter_text' ), 5, 3 );
	}

	/**
	 * Current language slug.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public static function current_language() {
		if ( function_exists( 'qtranxf_getLanguage' ) ) {
			$lang = qtranxf_getLanguage();
			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}
		if ( function_exists( 'qtrans_getLanguage' ) ) {
			$lang = qtrans_getLanguage();
			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}
		return get_locale();
	}

	/**
	 * Enabled language slugs.
	 *
	 * @since 0.2.0
	 *
	 * @return array<int, string>
	 */
	public static function languages() {
		global $q_config;

		if ( isset( $q_config['enabled_languages'] ) && is_array( $q_config['enabled_languages'] ) ) {
			return array_values( array_filter( array_map( 'strval', $q_config['enabled_languages'] ) ) );
		}

		return array( self::current_language() );
	}

	/**
	 * Extracts a language slice from a qTranslate string.
	 *
	 * @since 0.2.0
	 *
	 * @param string $text Source.
	 * @param string $lang Language.
	 * @return string
	 */
	public static function use_language( $text, $lang ) {
		if ( function_exists( 'qtranxf_use' ) ) {
			$out = qtranxf_use( $lang, $text, false, true );
			return is_string( $out ) ? $out : (string) $text;
		}
		if ( function_exists( 'qtrans_use' ) ) {
			$out = qtrans_use( $lang, $text, false );
			return is_string( $out ) ? $out : (string) $text;
		}
		return (string) $text;
	}

	/**
	 * Filter: current language.
	 *
	 * @since 0.2.0
	 *
	 * @param string $lang Language.
	 * @return string
	 */
	public static function filter_current( $lang ) {
		unset( $lang );
		return self::current_language();
	}

	/**
	 * Filter: language list.
	 *
	 * @since 0.2.0
	 *
	 * @param array<int, string> $languages Languages.
	 * @return array<int, string>
	 */
	public static function filter_languages( $languages ) {
		unset( $languages );
		return self::languages();
	}

	/**
	 * Filter: translate text/HTML.
	 *
	 * @since 0.2.0
	 *
	 * @param string $text    Text.
	 * @param string $lang    Language.
	 * @param string $original Original.
	 * @return string
	 */
	public static function filter_text( $text, $lang, $original ) {
		unset( $original );
		return self::use_language( $text, $lang );
	}
}
