<?php
/**
 * TranslatePress compatibility.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads TranslatePress languages, translations, and prefixed URLs.
 *
 * @since 0.1.0
 */
class Oli_AS_Compat_TranslatePress {

	/**
	 * Whether TranslatePress is available.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public static function is_active() {
		return class_exists( 'TRP_Translate_Press' );
	}

	/**
	 * TranslatePress settings array.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public static function settings() {
		if ( ! self::is_active() ) {
			return array();
		}

		$trp = TRP_Translate_Press::get_trp_instance();
		if ( ! is_object( $trp ) || ! method_exists( $trp, 'get_component' ) ) {
			return array();
		}

		$component = $trp->get_component( 'settings' );
		if ( is_object( $component ) && method_exists( $component, 'get_settings' ) ) {
			$settings = $component->get_settings();
			return is_array( $settings ) ? $settings : array();
		}

		return array();
	}

	/**
	 * Current TranslatePress language.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function current_language() {
		global $TRP_LANGUAGE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		if ( ! empty( $TRP_LANGUAGE ) && is_string( $TRP_LANGUAGE ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			return $TRP_LANGUAGE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		}

		$settings = self::settings();
		if ( ! empty( $settings['default-language'] ) ) {
			return (string) $settings['default-language'];
		}

		return get_locale();
	}

	/**
	 * Published languages.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	public static function languages() {
		$settings = self::settings();
		if ( ! empty( $settings['publish-languages'] ) && is_array( $settings['publish-languages'] ) ) {
			return array_values( $settings['publish-languages'] );
		}
		if ( ! empty( $settings['default-language'] ) ) {
			return array( (string) $settings['default-language'] );
		}
		return array( get_locale() );
	}

	/**
	 * Default language.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function default_language() {
		$settings = self::settings();
		if ( ! empty( $settings['default-language'] ) ) {
			return (string) $settings['default-language'];
		}
		$languages = self::languages();
		return isset( $languages[0] ) ? $languages[0] : get_locale();
	}

	/**
	 * Translates a string with TranslatePress.
	 *
	 * @since 0.1.0
	 *
	 * @param string $text Source text.
	 * @param string $lang Target language.
	 * @return string
	 */
	public static function translate( $text, $lang ) {
		$text = (string) $text;
		if ( '' === $text ) {
			return $text;
		}

		$lang         = self::match_language( $lang );
		$default      = self::default_language();
		$default_norm = Oli_AS_Language::instance()->normalize_code( $default );
		$target_norm  = Oli_AS_Language::instance()->normalize_code( $lang );
		if ( $default_norm === $target_norm ) {
			return $text;
		}

		if ( function_exists( 'trp_translate' ) ) {
			$translated = trp_translate( $text, $lang, false );
			if ( is_string( $translated ) && '' !== $translated ) {
				return wp_strip_all_tags( $translated );
			}
		}

		$from_dictionary = self::dictionary_lookup( $text, $default, $lang );
		return ( '' !== $from_dictionary ) ? $from_dictionary : $text;
	}

	/**
	 * Looks up a string in TranslatePress dictionary tables.
	 *
	 * @since 0.1.0
	 *
	 * @param string $original Original string.
	 * @param string $from     Default language.
	 * @param string $to       Target language.
	 * @return string
	 */
	public static function dictionary_lookup( $original, $from, $to ) {
		global $wpdb;

		$from  = strtolower( str_replace( '-', '_', $from ) );
		$to    = strtolower( str_replace( '-', '_', $to ) );
		$table = $wpdb->prefix . 'trp_dictionary_' . $from . '_' . $to;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $found !== $table ) {
			return '';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$translated = $wpdb->get_var( $wpdb->prepare( "SELECT translated FROM {$table} WHERE original = %s AND translated <> '' LIMIT 1", $original ) );

		return is_string( $translated ) ? $translated : '';
	}

	/**
	 * Converts a URL to the requested language.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url  URL.
	 * @param string $lang Language.
	 * @return string
	 */
	public static function url( $url, $lang ) {
		if ( ! self::is_active() || '' === $url ) {
			return $url;
		}

		$lang = self::match_language( $lang );
		$trp  = TRP_Translate_Press::get_trp_instance();
		if ( ! is_object( $trp ) || ! method_exists( $trp, 'get_component' ) ) {
			return $url;
		}

		$converter = $trp->get_component( 'url_converter' );
		if ( is_object( $converter ) && method_exists( $converter, 'get_url_for_language' ) ) {
			$converted = $converter->get_url_for_language( $lang, $url, '' );
			if ( is_string( $converted ) && '' !== $converted ) {
				return $converted;
			}
		}

		return $url;
	}

	/**
	 * Maps a normalized code back to a TranslatePress language.
	 *
	 * @since 0.1.0
	 *
	 * @param string $lang Normalized or raw language.
	 * @return string
	 */
	public static function match_language( $lang ) {
		$languages = self::languages();
		foreach ( $languages as $published ) {
			if ( 0 === strcasecmp( $published, $lang ) ) {
				return $published;
			}
			if ( Oli_AS_Language::instance()->normalize_code( $published ) === Oli_AS_Language::instance()->normalize_code( $lang ) ) {
				return $published;
			}
		}
		return $lang;
	}

	/**
	 * Language slug used in URLs.
	 *
	 * @since 0.1.0
	 *
	 * @param string $lang Language.
	 * @return string
	 */
	public static function url_slug( $lang ) {
		$settings = self::settings();
		$lang     = self::match_language( $lang );
		if ( ! empty( $settings['url-slugs'][ $lang ] ) ) {
			return (string) $settings['url-slugs'][ $lang ];
		}
		return strtolower( substr( $lang, 0, 2 ) );
	}
}
