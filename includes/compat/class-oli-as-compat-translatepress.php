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
 * Language keys stored in the index are URL slugs (fr, en) when slugs exist,
 * so search requests on /fr/ match the same rows as the current language.
 *
 * @since 0.1.0
 */
class Oli_AS_Compat_TranslatePress {

	/**
	 * Whether hooks were registered.
	 *
	 * @since 0.1.1
	 * @var bool
	 */
	protected static $booted = false;

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
	 * Registers frontend and indexing hooks.
	 *
	 * @since 0.1.1
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$booted || ! self::is_active() ) {
			return;
		}
		self::$booted = true;

		add_filter( 'trp_skip_selectors_from_dynamic_translation', array( __CLASS__, 'skip_dynamic_selectors' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'disable_trp_search_on_results' ), 1 );
	}

	/**
	 * Keeps already-translated suggestions from being rewritten in the browser.
	 *
	 * @since 0.1.1
	 *
	 * @param array<int, string> $selectors CSS selectors.
	 * @return array<int, string>
	 */
	public static function skip_dynamic_selectors( $selectors ) {
		if ( ! is_array( $selectors ) ) {
			$selectors = array();
		}
		$selectors[] = '.oli-as-panel';
		$selectors[] = '.oli-as-suggestions';
		$selectors[] = '.oli-as-details';
		$selectors[] = '.oli-as-overlay';
		return $selectors;
	}

	/**
	 * Stops TranslatePress from replacing our results-page query.
	 *
	 * @since 0.1.1
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public static function disable_trp_search_on_results( $query ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		if ( ! $query instanceof WP_Query || ! $query->is_main_query() ) {
			return;
		}

		$forced = isset( $_GET['oli_as'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['oli_as'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $forced && ! $query->is_search() ) {
			return;
		}
		if ( ! $forced && ! Oli_AS_Settings::enabled( 'use_on_results_page' ) ) {
			return;
		}

		$trp = self::instance();
		if ( ! $trp || ! method_exists( $trp, 'get_component' ) ) {
			return;
		}

		$search = $trp->get_component( 'search' );
		if ( ! is_object( $search ) ) {
			return;
		}

		remove_filter( 'pre_get_posts', array( $search, 'trp_search_filter' ), 10 );
		remove_filter( 'pre_get_posts', array( $search, 'trp_search_filter' ), 99999999 );
		remove_filter( 'get_search_query', array( $search, 'trp_search_query' ), 10 );
	}

	/**
	 * Makes language-prefixed permalinks available during cron and AJAX indexing.
	 *
	 * @since 0.1.1
	 *
	 * @return void
	 */
	public static function prepare_index_request() {
		if ( ! self::is_active() ) {
			return;
		}

		add_filter( 'trp_add_language_to_home_url_check_for_admin', '__return_false' );

		$trp = self::instance();
		if ( ! $trp || ! method_exists( $trp, 'get_component' ) ) {
			return;
		}

		if ( method_exists( $trp, 'init_machine_translation' ) ) {
			$trp->init_machine_translation();
		}

		$doing_cron = function_exists( 'wp_doing_cron' ) ? wp_doing_cron() : ( defined( 'DOING_CRON' ) && DOING_CRON );
		if ( $doing_cron ) {
			$converter = $trp->get_component( 'url_converter' );
			if ( is_object( $converter ) && method_exists( $converter, 'add_language_to_home_url' ) ) {
				add_filter( 'home_url', array( $converter, 'add_language_to_home_url' ), 1, 4 );
			}
		}
	}

	/**
	 * TranslatePress instance.
	 *
	 * @since 0.1.1
	 *
	 * @return object|null
	 */
	protected static function instance() {
		if ( ! self::is_active() || ! method_exists( 'TRP_Translate_Press', 'get_trp_instance' ) ) {
			return null;
		}
		$trp = TRP_Translate_Press::get_trp_instance();
		return is_object( $trp ) ? $trp : null;
	}

	/**
	 * TranslatePress settings array.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public static function settings() {
		$trp = self::instance();
		if ( ! $trp || ! method_exists( $trp, 'get_component' ) ) {
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
	 * Current language as a URL slug when available.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function current_language() {
		global $TRP_LANGUAGE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		if ( ! empty( $TRP_LANGUAGE ) && is_string( $TRP_LANGUAGE ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			return self::slug_for( $TRP_LANGUAGE ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		}

		return self::slug_for( self::default_locale() );
	}

	/**
	 * Published language slugs.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	public static function languages() {
		$settings = self::settings();
		$locales  = array();
		if ( ! empty( $settings['publish-languages'] ) && is_array( $settings['publish-languages'] ) ) {
			$locales = $settings['publish-languages'];
		} elseif ( function_exists( 'trp_get_languages' ) ) {
			$locales = array_keys( trp_get_languages() );
		} else {
			$locales = array( self::default_locale() );
		}

		$slugs = array();
		foreach ( $locales as $locale ) {
			$slug = self::slug_for( (string) $locale );
			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Default locale (en_US).
	 *
	 * @since 0.1.1
	 *
	 * @return string
	 */
	public static function default_locale() {
		$settings = self::settings();
		if ( ! empty( $settings['default-language'] ) ) {
			return (string) $settings['default-language'];
		}
		return get_locale();
	}

	/**
	 * Default language slug.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function default_language() {
		return self::slug_for( self::default_locale() );
	}

	/**
	 * URL slug for a locale or slug.
	 *
	 * @since 0.1.1
	 *
	 * @param string $lang Locale or slug.
	 * @return string
	 */
	public static function slug_for( $lang ) {
		$settings = self::settings();
		$slugs    = ( ! empty( $settings['url-slugs'] ) && is_array( $settings['url-slugs'] ) ) ? $settings['url-slugs'] : array();

		if ( isset( $slugs[ $lang ] ) ) {
			return (string) $slugs[ $lang ];
		}

		foreach ( $slugs as $locale => $slug ) {
			if ( 0 === strcasecmp( (string) $slug, $lang ) || 0 === strcasecmp( (string) $locale, $lang ) ) {
				return (string) $slug;
			}
			if ( Oli_AS_Language::instance()->same_language( (string) $locale, $lang ) || Oli_AS_Language::instance()->same_language( (string) $slug, $lang ) ) {
				return (string) $slug;
			}
		}

		$normalized = Oli_AS_Language::instance()->normalize_code( $lang );
		if ( false !== strpos( $normalized, '_' ) ) {
			return substr( $normalized, 0, 2 );
		}
		return $normalized;
	}

	/**
	 * Locale for a slug or locale.
	 *
	 * @since 0.1.1
	 *
	 * @param string $lang Slug or locale.
	 * @return string
	 */
	public static function locale_for( $lang ) {
		$settings = self::settings();
		$slugs    = ( ! empty( $settings['url-slugs'] ) && is_array( $settings['url-slugs'] ) ) ? $settings['url-slugs'] : array();

		if ( isset( $slugs[ $lang ] ) ) {
			return $lang;
		}

		foreach ( $slugs as $locale => $slug ) {
			if ( 0 === strcasecmp( (string) $slug, $lang ) || 0 === strcasecmp( (string) $locale, $lang ) ) {
				return (string) $locale;
			}
			if ( Oli_AS_Language::instance()->same_language( (string) $slug, $lang ) || Oli_AS_Language::instance()->same_language( (string) $locale, $lang ) ) {
				return (string) $locale;
			}
		}

		return self::match_language( $lang );
	}

	/**
	 * Translates a short phrase (title, term name, SKU-like string).
	 *
	 * @since 0.1.0
	 *
	 * @param string $text Source text.
	 * @param string $lang Target slug or locale.
	 * @return string
	 */
	public static function translate( $text, $lang ) {
		$text = (string) $text;
		if ( '' === $text ) {
			return $text;
		}

		$locale = self::locale_for( $lang );
		if ( Oli_AS_Language::instance()->same_language( $locale, self::default_locale() ) ) {
			return $text;
		}

		$from_query = self::query_lookup( $text, $locale );
		if ( '' !== $from_query ) {
			return $from_query;
		}

		$from_dictionary = self::dictionary_lookup( $text, self::default_locale(), $locale );
		if ( '' !== $from_dictionary ) {
			return $from_dictionary;
		}

		if ( function_exists( 'trp_translate' ) ) {
			$translated = trp_translate( $text, $locale, false );
			if ( is_string( $translated ) && '' !== $translated ) {
				$translated = wp_strip_all_tags( $translated );
				if ( $translated !== $text ) {
					return $translated;
				}
			}
		}

		return $text;
	}

	/**
	 * Translates a longer HTML/text block by walking phrases.
	 *
	 * Product descriptions are often stored as several dictionary rows, not one string.
	 *
	 * @since 0.1.1
	 *
	 * @param string $text Source HTML or text.
	 * @param string $lang Target slug or locale.
	 * @return string
	 */
	public static function translate_html( $text, $lang ) {
		$text = (string) $text;
		if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
			return $text;
		}

		$locale = self::locale_for( $lang );
		if ( Oli_AS_Language::instance()->same_language( $locale, self::default_locale() ) ) {
			return $text;
		}

		$trp = self::instance();
		if ( $trp && method_exists( $trp, 'get_component' ) && function_exists( 'trp_switch_language' ) ) {
			$render = $trp->get_component( 'translation_render' );
			if ( is_object( $render ) && method_exists( $render, 'translate_page' ) ) {
				global $TRP_LANGUAGE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
				$backup = isset( $TRP_LANGUAGE ) ? $TRP_LANGUAGE : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
				trp_switch_language( $locale );
				$translated = $render->translate_page( $text );
				if ( null !== $backup ) {
					trp_switch_language( $backup );
				}
				if ( is_string( $translated ) && '' !== $translated ) {
					return $translated;
				}
			}
		}

		return self::translate( wp_strip_all_tags( $text ), $lang );
	}

	/**
	 * Looks up an exact string via the TranslatePress query component.
	 *
	 * @since 0.1.1
	 *
	 * @param string $original Original string.
	 * @param string $locale   Target locale.
	 * @return string
	 */
	public static function query_lookup( $original, $locale ) {
		$trp = self::instance();
		if ( ! $trp || ! method_exists( $trp, 'get_component' ) ) {
			return '';
		}

		$query = $trp->get_component( 'query' );
		if ( ! is_object( $query ) || ! method_exists( $query, 'get_existing_translations' ) ) {
			return '';
		}

		$result = $query->get_existing_translations( array( $original ), $locale );
		if ( is_array( $result ) && isset( $result[ $original ] ) && is_object( $result[ $original ] ) && ! empty( $result[ $original ]->translated ) ) {
			return (string) $result[ $original ]->translated;
		}

		return '';
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
	 * @param string $lang Language slug or locale.
	 * @return string
	 */
	public static function url( $url, $lang ) {
		if ( ! self::is_active() || '' === $url ) {
			return $url;
		}

		$locale = self::locale_for( $lang );
		$trp    = self::instance();
		if ( ! $trp || ! method_exists( $trp, 'get_component' ) ) {
			return $url;
		}

		$converter = $trp->get_component( 'url_converter' );
		if ( is_object( $converter ) && method_exists( $converter, 'get_url_for_language' ) ) {
			$converted = $converter->get_url_for_language( $locale, $url, '' );
			if ( is_string( $converted ) && '' !== $converted ) {
				return $converted;
			}
		}

		return $url;
	}

	/**
	 * Maps a code back to a TranslatePress locale.
	 *
	 * @since 0.1.0
	 *
	 * @param string $lang Normalized or raw language.
	 * @return string
	 */
	public static function match_language( $lang ) {
		$settings = self::settings();
		$list     = array();
		if ( ! empty( $settings['publish-languages'] ) && is_array( $settings['publish-languages'] ) ) {
			$list = $settings['publish-languages'];
		}
		foreach ( $list as $published ) {
			if ( 0 === strcasecmp( $published, $lang ) ) {
				return $published;
			}
			if ( Oli_AS_Language::instance()->same_language( $published, $lang ) ) {
				return $published;
			}
			if ( Oli_AS_Language::instance()->same_language( self::slug_for( $published ), $lang ) ) {
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
		return self::slug_for( $lang );
	}
}
