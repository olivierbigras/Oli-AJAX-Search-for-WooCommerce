<?php
/**
 * In-memory inverted index used by tests and scoring helpers.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores documents and answers token queries.
 *
 * @since 0.1.0
 */
class Oli_AS_Index_Engine {

	/**
	 * Inverted index: language => term => object_key => field => weight.
	 *
	 * @since 0.1.0
	 * @var array<string, array<string, array<string, array<string, int>>>>
	 */
	protected $inv = array();

	/**
	 * Documents keyed by object_key.
	 *
	 * @since 0.1.0
	 * @var array<string, array<string, mixed>>
	 */
	protected $docs = array();

	/**
	 * Unique terms per language.
	 *
	 * @since 0.1.0
	 * @var array<string, array<string, bool>>
	 */
	protected $terms = array();

	/**
	 * Builds a stable document key.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type   Object type.
	 * @param int    $id     Object ID.
	 * @param string $lang   Language code.
	 * @return string
	 */
	public static function make_key( $type, $id, $lang ) {
		return $type . ':' . (int) $id . ':' . $lang;
	}

	/**
	 * Adds or replaces a document.
	 *
	 * @since 0.1.0
	 *
	 * @param string                            $key    Document key.
	 * @param array<string, mixed>              $meta   Display metadata.
	 * @param array<string, array<int, string>> $fields Field tokens.
	 * @param array<string, int>                $weights Field weights.
	 * @return void
	 */
	public function add_document( $key, $meta, $fields, $weights = array() ) {
		$this->remove_document( $key );

		$lang    = isset( $meta['language'] ) ? (string) $meta['language'] : 'default';
		$weights = array_merge( Oli_AS_Relevance::default_weights(), $weights );

		$meta['key']        = $key;
		$this->docs[ $key ] = $meta;

		foreach ( $fields as $field => $tokens ) {
			$weight = isset( $weights[ $field ] ) ? (int) $weights[ $field ] : 1;
			foreach ( $tokens as $token ) {
				$token = (string) $token;
				if ( '' === $token ) {
					continue;
				}
				if ( ! isset( $this->inv[ $lang ][ $token ][ $key ][ $field ] ) ) {
					$this->inv[ $lang ][ $token ][ $key ][ $field ] = 0;
				}
				$this->inv[ $lang ][ $token ][ $key ][ $field ] += $weight;
				$this->terms[ $lang ][ $token ]                  = true;
			}
		}
	}

	/**
	 * Removes a document from the index.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key Document key.
	 * @return void
	 */
	public function remove_document( $key ) {
		if ( ! isset( $this->docs[ $key ] ) ) {
			return;
		}

		$lang = isset( $this->docs[ $key ]['language'] ) ? (string) $this->docs[ $key ]['language'] : 'default';
		unset( $this->docs[ $key ] );

		if ( empty( $this->inv[ $lang ] ) ) {
			return;
		}

		foreach ( $this->inv[ $lang ] as $term => $postings ) {
			unset( $this->inv[ $lang ][ $term ][ $key ] );
			if ( empty( $this->inv[ $lang ][ $term ] ) ) {
				unset( $this->inv[ $lang ][ $term ] );
				unset( $this->terms[ $lang ][ $term ] );
			}
		}
	}

	/**
	 * Searches the in-memory index.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $query   Raw query.
	 * @param array<string, mixed> $options Search options.
	 * @return array<int, array<string, mixed>>
	 */
	public function search( $query, $options = array() ) {
		$defaults = array(
			'language'      => 'default',
			'types'         => array(),
			'limit'         => 10,
			'fuzzy'         => true,
			'fuzzy_level'   => '',
			'stopwords'     => array(),
			'synonyms'      => array(),
			'weights'       => array(),
			'excluded_keys' => array(),
		);
		$options  = array_merge( $defaults, $options );
		$level    = $this->resolve_fuzzy_level( $options );
		$tokens   = Oli_AS_Tokenizer::tokenize( $query );
		$tokens   = Oli_AS_Tokenizer::remove_stopwords( $tokens, $options['stopwords'] );
		$tokens   = Oli_AS_Synonyms::expand( $tokens, $options['synonyms'] );

		if ( empty( $tokens ) ) {
			return array();
		}

		$lang   = (string) $options['language'];
		$scores = array();
		$kinds  = array();

		foreach ( $tokens as $token ) {
			$matches = $this->lookup_term( $lang, $token, $level );
			foreach ( $matches as $row ) {
				$key = $row['key'];
				if ( isset( $options['excluded_keys'][ $key ] ) ) {
					continue;
				}
				if ( ! isset( $scores[ $key ] ) ) {
					$scores[ $key ] = 0.0;
				}
				$scores[ $key ]         += Oli_AS_Relevance::apply_kind( $row['weight'], $row['kind'] );
				$kinds[ $key ][ $token ] = $row['kind'];
			}
		}

		$results = array();
		foreach ( $scores as $key => $score ) {
			if ( ! isset( $this->docs[ $key ] ) ) {
				continue;
			}

			$doc = $this->docs[ $key ];
			if ( ! empty( $options['types'] ) && ! in_array( $doc['type'], $options['types'], true ) ) {
				continue;
			}

			$coverage = 0;
			foreach ( $tokens as $token ) {
				if ( isset( $kinds[ $key ][ $token ] ) ) {
					++$coverage;
				}
			}
			$score += ( $coverage / count( $tokens ) ) * 15;
			if ( count( $tokens ) === $coverage && count( $tokens ) > 1 ) {
				$score += 10;
			}

			$doc['score'] = $score;
			$results[]    = $doc;
		}

		usort(
			$results,
			static function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return 0;
				}
				return ( $a['score'] < $b['score'] ) ? 1 : -1;
			}
		);

		$limit = max( 1, (int) $options['limit'] );
		return array_slice( $results, 0, $limit );
	}

	/**
	 * Looks up postings for one token.
	 *
	 * @since 0.1.0
	 *
	 * @param string $lang  Language.
	 * @param string $token Token.
	 * @param string $level Fuzzy level (off disables fuzzy matches).
	 * @return array<int, array<string, mixed>>
	 */
	protected function lookup_term( $lang, $token, $level ) {
		$found = array();
		$fuzzy = ( 'off' !== $level );

		if ( isset( $this->inv[ $lang ][ $token ] ) ) {
			foreach ( $this->inv[ $lang ][ $token ] as $key => $fields ) {
				$found[] = array(
					'key'    => $key,
					'weight' => array_sum( $fields ),
					'kind'   => 'exact',
				);
			}
		}

		if ( ! isset( $this->terms[ $lang ] ) ) {
			return $found;
		}

		foreach ( array_keys( $this->terms[ $lang ] ) as $term ) {
			if ( $term === $token ) {
				continue;
			}

			$kind = '';
			if ( 0 === strpos( $term, $token ) ) {
				$kind = 'prefix';
			} elseif ( $fuzzy && Oli_AS_Relevance::is_fuzzy_match( $token, $term, $level ) ) {
				$kind = 'fuzzy';
			}

			if ( '' === $kind || ! isset( $this->inv[ $lang ][ $term ] ) ) {
				continue;
			}

			foreach ( $this->inv[ $lang ][ $term ] as $key => $fields ) {
				$found[] = array(
					'key'    => $key,
					'weight' => array_sum( $fields ),
					'kind'   => $kind,
				);
			}
		}

		return $found;
	}

	/**
	 * Returns a stored document.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key Document key.
	 * @return array<string, mixed>|null
	 */
	public function get_document( $key ) {
		return isset( $this->docs[ $key ] ) ? $this->docs[ $key ] : null;
	}

	/**
	 * Counts indexed documents.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->docs );
	}

	/**
	 * Resolves a fuzzy level from search options.
	 *
	 * @since 0.1.1
	 *
	 * @param array<string, mixed> $options Search options.
	 * @return string
	 */
	protected function resolve_fuzzy_level( $options ) {
		if ( isset( $options['fuzzy'] ) && ( false === $options['fuzzy'] || '0' === (string) $options['fuzzy'] || 'off' === $options['fuzzy'] ) ) {
			return 'off';
		}

		$level = isset( $options['fuzzy_level'] ) ? (string) $options['fuzzy_level'] : '';
		if ( in_array( $level, array( 'off', 'soft', 'normal', 'hard' ), true ) ) {
			return $level;
		}

		return ! empty( $options['fuzzy'] ) ? 'normal' : 'off';
	}
}
