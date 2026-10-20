<?php
/**
 * Language detection and translation helpers.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Facade over TranslatePress, WPML, and Polylang.
 *
 * @since 0.1.0
 */
class Oli_AS_Language {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Language|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Language
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers language-related hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'oli_as_current_language', array( $this, 'filter_current_language' ) );
		add_filter( 'oli_as_languages', array( $this, 'filter_languages' ) );
		add_filter( 'oli_as_translate_text', array( $this, 'filter_translate_text' ), 10, 3 );
		add_filter( 'oli_as_translate_html', array( $this, 'filter_translate_html' ), 10, 3 );
		add_filter( 'oli_as_translate_url', array( $this, 'filter_translate_url' ), 10, 2 );
		Oli_AS_Compat_TranslatePress::init();
	}

	/**
	 * Current language code.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function current() {
		$lang = apply_filters( 'oli_as_current_language', $this->detect_current() );
		return $this->normalize_code( is_string( $lang ) ? $lang : 'default' );
	}

	/**
	 * Published language codes for indexing.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	public function languages() {
		$languages = apply_filters( 'oli_as_languages', $this->detect_languages() );
		if ( ! is_array( $languages ) || empty( $languages ) ) {
			return array( 'default' );
		}

		$normalized = array();
		foreach ( $languages as $code ) {
			$code = $this->normalize_code( (string) $code );
			if ( '' !== $code ) {
				$normalized[] = $code;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Translates a string into a language.
	 *
	 * @since 0.1.0
	 *
	 * @param string $text Source text.
	 * @param string $lang Target language.
	 * @return string
	 */
	public function translate( $text, $lang ) {
		$translated = apply_filters( 'oli_as_translate_text', $text, $lang, $text );
		return is_string( $translated ) ? $translated : (string) $text;
	}

	/**
	 * Translates a longer HTML or multi-phrase block.
	 *
	 * @since 0.1.1
	 *
	 * @param string $text Source text.
	 * @param string $lang Target language.
	 * @return string
	 */
	public function translate_html( $text, $lang ) {
		$translated = apply_filters( 'oli_as_translate_html', $text, $lang, $text );
		return is_string( $translated ) ? $translated : (string) $text;
	}

	/**
	 * Converts a URL to a language-prefixed permalink.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url  Default-language URL.
	 * @param string $lang Language.
	 * @return string
	 */
	public function url( $url, $lang ) {
		$converted = apply_filters( 'oli_as_translate_url', $url, $lang );
		return is_string( $converted ) ? $converted : (string) $url;
	}

	/**
	 * Default current-language detector.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	protected function detect_current() {
		if ( Oli_AS_Compat_TranslatePress::is_active() ) {
			return Oli_AS_Compat_TranslatePress::current_language();
		}
		if ( Oli_AS_Compat_WPML::is_active() ) {
			return Oli_AS_Compat_WPML::current_language();
		}
		if ( Oli_AS_Compat_Polylang::is_active() ) {
			return Oli_AS_Compat_Polylang::current_language();
		}
		if ( Oli_AS_Compat_Qtranslate::is_active() ) {
			return Oli_AS_Compat_Qtranslate::current_language();
		}

		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return $this->normalize_code( $locale );
	}

	/**
	 * Default language list.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	protected function detect_languages() {
		if ( Oli_AS_Compat_TranslatePress::is_active() ) {
			return Oli_AS_Compat_TranslatePress::languages();
		}
		if ( Oli_AS_Compat_WPML::is_active() ) {
			return Oli_AS_Compat_WPML::languages();
		}
		if ( Oli_AS_Compat_Polylang::is_active() ) {
			return Oli_AS_Compat_Polylang::languages();
		}
		if ( Oli_AS_Compat_Qtranslate::is_active() ) {
			return Oli_AS_Compat_Qtranslate::languages();
		}

		return array( $this->detect_current() );
	}

	/**
	 * Filter wrapper for current language.
	 *
	 * @since 0.1.0
	 *
	 * @param string $lang Language.
	 * @return string
	 */
	public function filter_current_language( $lang ) {
		return $this->normalize_code( (string) $lang );
	}

	/**
	 * Filter wrapper for language list.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, string> $languages Languages.
	 * @return array<int, string>
	 */
	public function filter_languages( $languages ) {
		return is_array( $languages ) ? $languages : array( 'default' );
	}

	/**
	 * Filter wrapper for text translation.
	 *
	 * @since 0.1.0
	 *
	 * @param string $text     Translated or original text.
	 * @param string $lang     Target language.
	 * @param string $original Original text.
	 * @return string
	 */
	public function filter_translate_text( $text, $lang, $original ) {
		if ( Oli_AS_Compat_TranslatePress::is_active() ) {
			return Oli_AS_Compat_TranslatePress::translate( $original, $lang );
		}
		if ( Oli_AS_Compat_WPML::is_active() ) {
			return Oli_AS_Compat_WPML::translate( $original, $lang );
		}
		return (string) $text;
	}

	/**
	 * Filter wrapper for HTML translation.
	 *
	 * @since 0.1.1
	 *
	 * @param string $text     Translated or original text.
	 * @param string $lang     Target language.
	 * @param string $original Original text.
	 * @return string
	 */
	public function filter_translate_html( $text, $lang, $original ) {
		if ( Oli_AS_Compat_TranslatePress::is_active() ) {
			return Oli_AS_Compat_TranslatePress::translate_html( $original, $lang );
		}
		return $this->translate( $original, $lang );
	}

	/**
	 * Filter wrapper for URL translation.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url  URL.
	 * @param string $lang Language.
	 * @return string
	 */
	public function filter_translate_url( $url, $lang ) {
		if ( Oli_AS_Compat_TranslatePress::is_active() ) {
			return Oli_AS_Compat_TranslatePress::url( $url, $lang );
		}
		if ( Oli_AS_Compat_WPML::is_active() ) {
			return Oli_AS_Compat_WPML::url( $url, $lang );
		}
		if ( Oli_AS_Compat_Polylang::is_active() ) {
			return Oli_AS_Compat_Polylang::url( $url, $lang );
		}
		return (string) $url;
	}

	/**
	 * Normalizes a language code for index keys.
	 *
	 * @since 0.1.0
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public function normalize_code( $code ) {
		$code = strtolower( str_replace( '-', '_', trim( (string) $code ) ) );
		$code = preg_replace( '/[^a-z0-9_]/', '', $code );
		if ( ! is_string( $code ) || '' === $code ) {
			return 'default';
		}
		return substr( $code, 0, 20 );
	}

	/**
	 * Resolves a language from a request parameter or current context.
	 *
	 * @since 0.1.0
	 *
	 * @param string $requested Requested language.
	 * @return string
	 */
	public function from_request( $requested = '' ) {
		$requested = $this->normalize_code( $requested );
		$languages = $this->languages();
		foreach ( $languages as $lang ) {
			if ( $this->same_language( $requested, $lang ) ) {
				return $lang;
			}
		}

		$current = $this->current();
		foreach ( $languages as $lang ) {
			if ( $this->same_language( $current, $lang ) ) {
				return $lang;
			}
		}

		return $current;
	}

	/**
	 * Whether two language codes refer to the same language.
	 *
	 * Accepts both URL slugs (fr) and locales (fr_FR / fr_fr).
	 *
	 * @since 0.1.1
	 *
	 * @param string $left  First code.
	 * @param string $right Second code.
	 * @return bool
	 */
	public function same_language( $left, $right ) {
		$left  = $this->normalize_code( $left );
		$right = $this->normalize_code( $right );
		if ( $left === $right ) {
			return true;
		}
		if ( 'default' === $left || 'default' === $right ) {
			return false;
		}
		if ( 0 === strpos( $left, $right . '_' ) || 0 === strpos( $right, $left . '_' ) ) {
			return true;
		}
		if ( strlen( $left ) >= 2 && strlen( $right ) >= 2 && substr( $left, 0, 2 ) === substr( $right, 0, 2 ) ) {
			return true;
		}
		return false;
	}
}
