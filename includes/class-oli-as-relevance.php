<?php
/**
 * Relevance scoring helpers.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Scores documents against a tokenized query.
 *
 * @since 0.1.0
 */
class Oli_AS_Relevance {

	/**
	 * Default field weights.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, int>
	 */
	public static function default_weights() {
		return array(
			'title'     => 10,
			'sku'       => 12,
			'excerpt'   => 4,
			'content'   => 2,
			'attribute' => 6,
			'meta'      => 5,
			'taxonomy'  => 7,
		);
	}

	/**
	 * Scores one document.
	 *
	 * Field map values are arrays of tokens. Optional match_type per token
	 * can be provided via $match_types[ field ][ token ] = exact|prefix|fuzzy.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, string>                   $query_tokens Query tokens.
	 * @param array<string, array<int, string>>    $fields       Field => tokens.
	 * @param array<string, int>                   $weights      Field weights.
	 * @param array<string, array<string, string>> $match_types Optional match kinds.
	 * @return float
	 */
	public static function score( $query_tokens, $fields, $weights = array(), $match_types = array() ) {
		if ( empty( $query_tokens ) ) {
			return 0.0;
		}

		$weights = array_merge( self::default_weights(), $weights );
		$score   = 0.0;
		$matched = 0;

		foreach ( $query_tokens as $token ) {
			$token_hit = false;

			foreach ( $fields as $field => $tokens ) {
				if ( empty( $tokens ) ) {
					continue;
				}

				$weight = isset( $weights[ $field ] ) ? (int) $weights[ $field ] : 1;
				$kind   = self::best_match( $token, $tokens, $field, $match_types );

				if ( '' === $kind ) {
					continue;
				}

				$token_hit = true;
				$score    += self::apply_kind( $weight, $kind );

				if ( 'exact' === $kind && 'title' === $field ) {
					$score += 20;
				}
				if ( 'exact' === $kind && 'sku' === $field ) {
					$score += 25;
				}
			}

			if ( $token_hit ) {
				++$matched;
			}
		}

		$total = count( $query_tokens );
		if ( $total > 0 ) {
			$score += ( $matched / $total ) * 15;
		}

		if ( $matched === $total && $total > 1 ) {
			$score += 10;
		}

		return (float) $score;
	}

	/**
	 * Finds the best match kind for one query token in a field.
	 *
	 * @since 0.1.0
	 *
	 * @param string                               $token       Query token.
	 * @param array<int, string>                   $tokens      Field tokens.
	 * @param string                               $field       Field name.
	 * @param array<string, array<string, string>> $match_types Declared kinds.
	 * @return string empty|exact|prefix|fuzzy
	 */
	public static function best_match( $token, $tokens, $field, $match_types ) {
		if ( isset( $match_types[ $field ][ $token ] ) ) {
			return (string) $match_types[ $field ][ $token ];
		}

		$lookup = array_fill_keys( $tokens, true );
		if ( isset( $lookup[ $token ] ) ) {
			return 'exact';
		}

		foreach ( $tokens as $candidate ) {
			if ( 0 === strpos( $candidate, $token ) ) {
				return 'prefix';
			}
		}

		return '';
	}

	/**
	 * Applies a match-kind multiplier to a field weight.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $weight Field weight.
	 * @param string $kind   Match kind.
	 * @return float
	 */
	public static function apply_kind( $weight, $kind ) {
		switch ( $kind ) {
			case 'exact':
				return (float) $weight * 3.0;
			case 'prefix':
				return (float) $weight * 1.6;
			case 'fuzzy':
				return (float) $weight * 0.55;
			default:
				return (float) $weight;
		}
	}

	/**
	 * Levenshtein distance with a cheap length-difference bailout.
	 *
	 * @since 0.1.0
	 *
	 * @param string $a First string.
	 * @param string $b Second string.
	 * @param int    $max Maximum useful distance.
	 * @return int
	 */
	public static function distance( $a, $b, $max = 2 ) {
		if ( $a === $b ) {
			return 0;
		}

		$len_a = mb_strlen( $a, 'UTF-8' );
		$len_b = mb_strlen( $b, 'UTF-8' );

		if ( abs( $len_a - $len_b ) > $max ) {
			return $max + 1;
		}

		if ( function_exists( 'levenshtein' ) && strlen( $a ) === $len_a && strlen( $b ) === $len_b ) {
			return levenshtein( $a, $b );
		}

		return self::mb_levenshtein( $a, $b );
	}

	/**
	 * Multibyte Levenshtein implementation.
	 *
	 * @since 0.1.0
	 *
	 * @param string $a First string.
	 * @param string $b Second string.
	 * @return int
	 */
	public static function mb_levenshtein( $a, $b ) {
		$rows = mb_strlen( $a, 'UTF-8' );
		$cols = mb_strlen( $b, 'UTF-8' );
		$prev = range( 0, $cols );

		for ( $i = 0; $i < $rows; $i++ ) {
			$curr    = array();
			$curr[0] = $i + 1;
			$ca      = mb_substr( $a, $i, 1, 'UTF-8' );

			for ( $j = 0; $j < $cols; $j++ ) {
				$cb             = mb_substr( $b, $j, 1, 'UTF-8' );
				$cost           = ( $ca === $cb ) ? 0 : 1;
				$curr[ $j + 1 ] = min(
					$curr[ $j ] + 1,
					$prev[ $j + 1 ] + 1,
					$prev[ $j ] + $cost
				);
			}

			$prev = $curr;
		}

		return (int) $prev[ $cols ];
	}

	/**
	 * Whether two tokens are fuzzy-equivalent.
	 *
	 * @since 0.1.0
	 *
	 * @param string          $token     Query token.
	 * @param string          $candidate Index token.
	 * @param string|bool|int $level     off|soft|normal|hard, or a legacy boolean.
	 * @return bool
	 */
	public static function is_fuzzy_match( $token, $candidate, $level = 'normal' ) {
		if ( 'off' === $level || false === $level || '0' === (string) $level ) {
			return false;
		}

		$len = mb_strlen( $token, 'UTF-8' );
		$min = 4;
		$max = 1;

		if ( 'soft' === $level ) {
			$min = 5;
			$max = 1;
		} elseif ( 'hard' === $level ) {
			$min = 3;
			$max = ( $len >= 10 ) ? 3 : ( ( $len >= 6 ) ? 2 : 1 );
		} else {
			$min = 4;
			$max = ( $len >= 8 ) ? 2 : 1;
		}

		if ( $len < $min ) {
			return false;
		}

		return self::distance( $token, $candidate, $max ) <= $max;
	}
}
