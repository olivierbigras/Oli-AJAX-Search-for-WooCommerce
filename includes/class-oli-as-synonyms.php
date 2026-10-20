<?php
/**
 * Synonym expansion.
 *
 * @package Oli_Ajax_Search
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'OLI_AS_TEST_BOOTSTRAP' ) ) {
	exit;
}

/**
 * Expands query tokens using configured synonym groups.
 *
 * @since 0.1.0
 */
class Oli_AS_Synonyms {

	/**
	 * Parses a textarea of comma-separated synonym groups.
	 *
	 * @since 0.1.0
	 *
	 * @param string $raw Raw settings text.
	 * @return array<int, array<int, string>>
	 */
	public static function parse( $raw ) {
		$groups = array();
		$lines  = preg_split( '/\r\n|\r|\n/', (string) $raw );
		if ( ! is_array( $lines ) ) {
			return $groups;
		}

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || 0 === strpos( $line, '#' ) ) {
				continue;
			}

			$parts = array_map( 'trim', explode( ',', $line ) );
			$terms = array();
			foreach ( $parts as $part ) {
				$tokens = Oli_AS_Tokenizer::tokenize( $part, 1 );
				foreach ( $tokens as $token ) {
					$terms[] = $token;
				}
			}

			$terms = array_values( array_unique( $terms ) );
			if ( count( $terms ) > 1 ) {
				$groups[] = $terms;
			}
		}

		return $groups;
	}

	/**
	 * Expands tokens with their synonym groups.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, string>             $tokens Query tokens.
	 * @param array<int, array<int, string>> $groups Synonym groups.
	 * @return array<int, string>
	 */
	public static function expand( $tokens, $groups ) {
		if ( empty( $groups ) || empty( $tokens ) ) {
			return $tokens;
		}

		$expanded = $tokens;
		foreach ( $tokens as $token ) {
			foreach ( $groups as $group ) {
				if ( in_array( $token, $group, true ) ) {
					foreach ( $group as $synonym ) {
						$expanded[] = $synonym;
					}
				}
			}
		}

		return array_values( array_unique( $expanded ) );
	}
}
