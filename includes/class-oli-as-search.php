<?php
/**
 * Database-backed search.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Queries the inverted index and hydrates suggestions.
 *
 * @since 0.1.0
 */
class Oli_AS_Search {

	/**
	 * Runs a grouped suggestion search.
	 *
	 * @since 0.1.0
	 *
	 * @param string $query Raw query.
	 * @param string $lang  Language.
	 * @return array<string, mixed>
	 */
	public function suggest( $query, $lang = '' ) {
		$start = microtime( true );
		$lang  = Oli_AS_Language::instance()->from_request( $lang );
		$query = trim( wp_unslash( $query ) );

		$tokens = Oli_AS_Tokenizer::tokenize( $query );
		$tokens = Oli_AS_Tokenizer::remove_stopwords( $tokens, Oli_AS_Settings::stopwords() );
		$tokens = Oli_AS_Synonyms::expand( $tokens, Oli_AS_Settings::synonym_groups() );

		$groups = array(
			'products'   => array(),
			'categories' => array(),
			'tags'       => array(),
			'attributes' => array(),
			'posts'      => array(),
			'pages'      => array(),
		);

		if ( empty( $tokens ) ) {
			return $this->payload( $query, $lang, $groups, 0, $start );
		}

		$limits = array(
			'product'   => (int) Oli_AS_Settings::get( 'max_products', 6 ),
			'category'  => (int) Oli_AS_Settings::get( 'max_categories', 3 ),
			'tag'       => (int) Oli_AS_Settings::get( 'max_tags', 3 ),
			'attribute' => (int) Oli_AS_Settings::get( 'max_attributes', 3 ),
			'post'      => (int) Oli_AS_Settings::get( 'max_posts', 2 ),
			'page'      => (int) Oli_AS_Settings::get( 'max_pages', 2 ),
		);

		$scored = $this->query_index( $tokens, $lang, array_sum( $limits ) + 20 );
		$bucket = array(
			'product'   => array(),
			'category'  => array(),
			'tag'       => array(),
			'attribute' => array(),
			'post'      => array(),
			'page'      => array(),
		);

		foreach ( $scored as $row ) {
			$type = $row['object_type'];
			if ( ! isset( $bucket[ $type ] ) ) {
				continue;
			}
			if ( count( $bucket[ $type ] ) >= $limits[ $type ] ) {
				continue;
			}
			$bucket[ $type ][] = $this->hydrate( $row );
		}

		$groups['products']   = $bucket['product'];
		$groups['categories'] = $bucket['category'];
		$groups['tags']       = $bucket['tag'];
		$groups['attributes'] = $bucket['attribute'];
		$groups['posts']      = $bucket['post'];
		$groups['pages']      = $bucket['page'];

		$total = 0;
		foreach ( $groups as $items ) {
			$total += count( $items );
		}

		return $this->payload( $query, $lang, $groups, $total, $start );
	}

	/**
	 * Returns matching product IDs in relevance order.
	 *
	 * @since 0.1.0
	 *
	 * @param string $query Raw query.
	 * @param string $lang  Language.
	 * @param int    $limit Limit.
	 * @return array<int, int>
	 */
	public function product_ids( $query, $lang = '', $limit = 100 ) {
		$lang   = Oli_AS_Language::instance()->from_request( $lang );
		$tokens = Oli_AS_Tokenizer::tokenize( $query );
		$tokens = Oli_AS_Tokenizer::remove_stopwords( $tokens, Oli_AS_Settings::stopwords() );
		$tokens = Oli_AS_Synonyms::expand( $tokens, Oli_AS_Settings::synonym_groups() );

		if ( empty( $tokens ) ) {
			return array();
		}

		$rows = $this->query_index( $tokens, $lang, $limit, array( 'product' ) );
		$ids  = array();
		foreach ( $rows as $row ) {
			$ids[] = (int) $row['object_id'];
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Queries the inverted index.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, string> $tokens Query tokens.
	 * @param string             $lang   Language.
	 * @param int                $limit  Max documents.
	 * @param array<int, string> $types  Optional type filter.
	 * @return array<int, array<string, mixed>>
	 */
	protected function query_index( $tokens, $lang, $limit, $types = array() ) {
		global $wpdb;

		$inv  = Oli_AS_Install::inv_table();
		$docs = Oli_AS_Install::docs_table();
		$dict = Oli_AS_Install::terms_table();

		$term_set = $this->expand_index_terms( $tokens, $lang, $dict );
		if ( empty( $term_set ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $term_set ), '%s' ) );
		$params       = array_merge( array( $lang ), array_keys( $term_set ) );

		$type_sql = '';
		if ( ! empty( $types ) ) {
			$type_sql = ' AND d.object_type IN (' . implode( ',', array_fill( 0, count( $types ), '%s' ) ) . ')';
			$params   = array_merge( $params, $types );
		}

		$limit = max( 1, min( 200, (int) $limit ) );

		$sql = "SELECT d.object_key, d.object_id, d.object_type, d.language, d.title, d.sku, d.excerpt, d.url, d.image_id, d.parent_id, d.extra,
			SUM(i.weight * CASE
				WHEN i.term = i_term.qterm THEN 3
				WHEN i.term LIKE CONCAT(i_term.qterm, '%') THEN 1.6
				ELSE 0.55
			END) AS score,
			COUNT(DISTINCT i_term.qterm) AS coverage
			FROM {$inv} i
			INNER JOIN {$docs} d ON d.object_key = i.object_key
			INNER JOIN (
				{$this->term_union_sql( $term_set )}
			) i_term ON i.term = i_term.iterm
			WHERE i.language = %s
			AND i.term IN ({$placeholders})
			{$type_sql}
			GROUP BY d.object_key
			ORDER BY (coverage * 15) + score DESC
			LIMIT {$limit}";

		$prepare_params = array_merge( $this->term_union_params( $term_set ), $params );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_params ), ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Expands tokens to exact, prefix, and fuzzy index terms.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, string> $tokens Query tokens.
	 * @param string             $lang   Language.
	 * @param string             $dict   Terms table.
	 * @return array<string, string> index_term => query_token
	 */
	protected function expand_index_terms( $tokens, $lang, $dict ) {
		global $wpdb;

		$map   = array();
		$fuzzy = Oli_AS_Settings::enabled( 'fuzzy' );

		foreach ( $tokens as $token ) {
			$map[ $token ] = $token;

			$like = $wpdb->esc_like( $token ) . '%';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$prefix_terms = $wpdb->get_col( $wpdb->prepare( "SELECT term FROM {$dict} WHERE language = %s AND term LIKE %s LIMIT 40", $lang, $like ) );
			if ( is_array( $prefix_terms ) ) {
				foreach ( $prefix_terms as $term ) {
					$map[ $term ] = $token;
				}
			}

			if ( ! $fuzzy || mb_strlen( $token, 'UTF-8' ) < 4 ) {
				continue;
			}

			$prefix = mb_substr( $token, 0, 3, 'UTF-8' );
			$plike  = $wpdb->esc_like( $prefix ) . '%';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$candidates = $wpdb->get_col( $wpdb->prepare( "SELECT term FROM {$dict} WHERE language = %s AND term LIKE %s LIMIT 80", $lang, $plike ) );
			if ( is_array( $candidates ) ) {
				foreach ( $candidates as $candidate ) {
					if ( Oli_AS_Relevance::is_fuzzy_match( $token, $candidate ) ) {
						$map[ $candidate ] = $token;
					}
				}
			}
		}

		return $map;
	}

	/**
	 * Builds a UNION of (index term, query token) pairs.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, string> $term_set Map.
	 * @return string
	 */
	protected function term_union_sql( $term_set ) {
		$parts = array();
		foreach ( $term_set as $ignored ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$parts[] = 'SELECT %s AS iterm, %s AS qterm';
		}
		return implode( ' UNION ALL ', $parts );
	}

	/**
	 * Parameters for the term UNION.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, string> $term_set Map.
	 * @return array<int, string>
	 */
	protected function term_union_params( $term_set ) {
		$params = array();
		foreach ( $term_set as $iterm => $qterm ) {
			$params[] = $iterm;
			$params[] = $qterm;
		}
		return $params;
	}

	/**
	 * Hydrates a document row for the frontend.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return array<string, mixed>
	 */
	protected function hydrate( $row ) {
		$extra = array();
		if ( ! empty( $row['extra'] ) ) {
			$decoded = json_decode( $row['extra'], true );
			$extra   = is_array( $decoded ) ? $decoded : array();
		}

		$item = array(
			'id'              => (int) $row['object_id'],
			'type'            => $row['object_type'],
			'title'           => $row['title'],
			'url'             => $row['url'],
			'sku'             => $row['sku'],
			'excerpt'         => wp_trim_words( wp_strip_all_tags( (string) $row['excerpt'] ), 18 ),
			'image'           => $this->image_url( (int) $row['image_id'] ),
			'score'           => isset( $row['score'] ) ? (float) $row['score'] : 0.0,
			'stock'           => isset( $extra['stock'] ) ? $extra['stock'] : '',
			'in_stock'        => ! empty( $extra['in_stock'] ),
			'price_html'      => '',
			'add_to_cart_url' => '',
		);

		if ( 'product' === $row['object_type'] && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( (int) $row['object_id'] );
			if ( $product ) {
				$item['price_html']      = Oli_AS_Compat_Multicurrency::price_html( $product );
				$item['sku']             = $product->get_sku();
				$item['stock']           = $product->get_stock_status();
				$item['in_stock']        = $product->is_in_stock();
				$item['add_to_cart_url'] = $product->add_to_cart_url();
				if ( empty( $item['image'] ) ) {
					$item['image'] = wc_placeholder_img_src( 'woocommerce_thumbnail' );
				}
			}
		}

		return $item;
	}

	/**
	 * Thumbnail URL.
	 *
	 * @since 0.1.0
	 *
	 * @param int $image_id Attachment ID.
	 * @return string
	 */
	protected function image_url( $image_id ) {
		if ( $image_id < 1 ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
		if ( ! $url ) {
			$url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
		}
		return $url ? $url : '';
	}

	/**
	 * Suggestion payload.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $query  Query.
	 * @param string               $lang   Language.
	 * @param array<string, mixed> $groups Groups.
	 * @param int                  $total  Result count.
	 * @param float                $start  Timer.
	 * @return array<string, mixed>
	 */
	protected function payload( $query, $lang, $groups, $total, $start ) {
		$results_url = $this->results_url( $query, $lang );

		return array(
			'query'       => $query,
			'language'    => $lang,
			'groups'      => $groups,
			'total'       => $total,
			'results_url' => $results_url,
			'took_ms'     => (int) round( ( microtime( true ) - $start ) * 1000 ),
		);
	}

	/**
	 * "See all results" URL, language-prefixed when possible.
	 *
	 * @since 0.1.0
	 *
	 * @param string $query Query.
	 * @param string $lang  Language.
	 * @return string
	 */
	public function results_url( $query, $lang ) {
		$url = add_query_arg(
			array(
				's'         => $query,
				'post_type' => 'product',
				'oli_as'    => '1',
			),
			home_url( '/' )
		);

		return Oli_AS_Language::instance()->url( $url, $lang );
	}
}
