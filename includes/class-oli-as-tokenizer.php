<?php
/**
 * Query and document tokenizer.
 *
 * @package Oli_Ajax_Search
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'OLI_AS_TEST_BOOTSTRAP' ) ) {
	exit;
}

/**
 * Normalizes text and splits it into searchable tokens.
 *
 * @since 0.1.0
 */
class Oli_AS_Tokenizer {

	/**
	 * Default minimum token length.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	const MIN_LENGTH = 2;

	/**
	 * Lowercases text, strips HTML, and removes diacritics.
	 *
	 * @since 0.1.0
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	public static function normalize( $text ) {
		$text = (string) $text;
		$text = self::strip_tags_keep_space( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = mb_strtolower( $text, 'UTF-8' );
		$text = self::strip_accents( $text );
		$text = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $text );
		$text = preg_replace( '/\s+/u', ' ', (string) $text );

		return trim( (string) $text );
	}

	/**
	 * Tokenizes text into unique normalized terms.
	 *
	 * @since 0.1.0
	 *
	 * @param string $text       Raw text.
	 * @param int    $min_length Minimum token length.
	 * @return array<int, string>
	 */
	public static function tokenize( $text, $min_length = self::MIN_LENGTH ) {
		$normalized = self::normalize( $text );
		if ( '' === $normalized ) {
			return array();
		}

		$parts  = explode( ' ', $normalized );
		$tokens = array();

		foreach ( $parts as $part ) {
			if ( mb_strlen( $part, 'UTF-8' ) < $min_length ) {
				continue;
			}
			$tokens[ $part ] = $part;
		}

		return array_values( $tokens );
	}

	/**
	 * Tokenizes a SKU while keeping compact alphanumeric variants.
	 *
	 * @since 0.1.0
	 *
	 * @param string $sku SKU value.
	 * @return array<int, string>
	 */
	public static function tokenize_sku( $sku ) {
		$sku = trim( (string) $sku );
		if ( '' === $sku ) {
			return array();
		}

		$tokens   = self::tokenize( $sku, 1 );
		$compact  = preg_replace( '/[^a-z0-9]+/i', '', mb_strtolower( self::strip_accents( $sku ), 'UTF-8' ) );
		$compact  = is_string( $compact ) ? $compact : '';
		$original = mb_strtolower( self::strip_accents( $sku ), 'UTF-8' );

		if ( '' !== $compact ) {
			$tokens[] = $compact;
		}
		if ( '' !== $original ) {
			$tokens[] = $original;
		}

		return array_values( array_unique( $tokens ) );
	}

	/**
	 * Removes diacritics for language-agnostic matching.
	 *
	 * @since 0.1.0
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function strip_accents( $text ) {
		if ( function_exists( 'iconv' ) ) {
			$transliterated = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $text ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( is_string( $transliterated ) && '' !== $transliterated ) {
				$text = $transliterated;
			}
		}

		$map = array(
			'À' => 'A',
			'Á' => 'A',
			'Â' => 'A',
			'Ã' => 'A',
			'Ä' => 'A',
			'Å' => 'A',
			'à' => 'a',
			'á' => 'a',
			'â' => 'a',
			'ã' => 'a',
			'ä' => 'a',
			'å' => 'a',
			'Ç' => 'C',
			'ç' => 'c',
			'È' => 'E',
			'É' => 'E',
			'Ê' => 'E',
			'Ë' => 'E',
			'è' => 'e',
			'é' => 'e',
			'ê' => 'e',
			'ë' => 'e',
			'Ì' => 'I',
			'Í' => 'I',
			'Î' => 'I',
			'Ï' => 'I',
			'ì' => 'i',
			'í' => 'i',
			'î' => 'i',
			'ï' => 'i',
			'Ñ' => 'N',
			'ñ' => 'n',
			'Ò' => 'O',
			'Ó' => 'O',
			'Ô' => 'O',
			'Õ' => 'O',
			'Ö' => 'O',
			'ò' => 'o',
			'ó' => 'o',
			'ô' => 'o',
			'õ' => 'o',
			'ö' => 'o',
			'Ù' => 'U',
			'Ú' => 'U',
			'Û' => 'U',
			'Ü' => 'U',
			'ù' => 'u',
			'ú' => 'u',
			'û' => 'u',
			'ü' => 'u',
			'Ý' => 'Y',
			'ý' => 'y',
			'ÿ' => 'y',
			'Æ' => 'AE',
			'æ' => 'ae',
			'Œ' => 'OE',
			'œ' => 'oe',
			'ß' => 'ss',
		);

		return strtr( $text, $map );
	}

	/**
	 * Replaces HTML tags with spaces so words are not joined.
	 *
	 * @since 0.1.0
	 *
	 * @param string $text HTML or plain text.
	 * @return string
	 */
	public static function strip_tags_keep_space( $text ) {
		$text = preg_replace( '/<[^>]+>/', ' ', (string) $text );
		return is_string( $text ) ? $text : '';
	}

	/**
	 * Removes stopwords from a token list.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, string> $tokens    Tokens.
	 * @param array<int, string> $stopwords Normalized stopwords.
	 * @return array<int, string>
	 */
	public static function remove_stopwords( $tokens, $stopwords ) {
		if ( empty( $stopwords ) ) {
			return $tokens;
		}

		$lookup = array();
		foreach ( $stopwords as $word ) {
			$lookup[ $word ] = true;
		}

		$kept = array();
		foreach ( $tokens as $token ) {
			if ( ! isset( $lookup[ $token ] ) ) {
				$kept[] = $token;
			}
		}

		return $kept;
	}
}
