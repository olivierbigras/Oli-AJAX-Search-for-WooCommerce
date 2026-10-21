<?php
/**
 * Background and incremental search indexer.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds and updates custom index tables.
 *
 * @since 0.1.0
 */
class Oli_AS_Indexer {

	/**
	 * Batch hook.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	const HOOK = 'oli_as_index_batch';

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Indexer|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Indexer
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers index hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( self::HOOK, array( $this, 'process_batch' ), 10, 1 );
		add_action( 'save_post_product', array( $this, 'on_save_product' ), 20, 1 );
		add_action( 'woocommerce_update_product', array( $this, 'on_save_product' ), 20, 1 );
		add_action( 'woocommerce_new_product', array( $this, 'on_save_product' ), 20, 1 );
		add_action( 'woocommerce_delete_product', array( $this, 'on_delete_product' ), 10, 1 );
		add_action( 'before_delete_post', array( $this, 'on_delete_post' ), 10, 1 );
		add_action( 'woocommerce_product_set_stock', array( $this, 'on_stock_change' ), 10, 1 );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'on_stock_change' ), 10, 1 );
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'on_stock_status' ), 10, 1 );
		add_action( 'created_term', array( $this, 'on_term_change' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'on_term_change' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'on_term_delete' ), 10, 3 );
		add_action( 'save_post', array( $this, 'on_save_content' ), 20, 2 );
		add_action( 'trp_save_editor_translations_regular_strings', array( $this, 'on_translation_saved' ) );
		add_action( 'trp_update_translation', array( $this, 'on_translation_saved' ) );
		add_action( 'oli_as_cron_maintenance', array( $this, 'maintenance' ) );
	}

	/**
	 * Starts a full rebuild.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function start_rebuild() {
		Oli_AS_Cache::bump();
		$this->clear_index();

		$total = $this->count_source_items();
		$this->update_status(
			array(
				'state'     => 'building',
				'processed' => 0,
				'total'     => $total,
				'started'   => time(),
				'updated'   => time(),
				'message'   => __( 'Indexing started.', 'oli-ajax-search' ),
			)
		);

		$this->queue_batch( 0 );

		return $this->get_status();
	}

	/**
	 * Queues the next batch.
	 *
	 * @since 0.1.0
	 *
	 * @param int $offset Offset.
	 * @return void
	 */
	public function queue_batch( $offset ) {
		$offset = absint( $offset );

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::HOOK, array( $offset ), 'oli-ajax-search' );
			return;
		}

		wp_schedule_single_event( time() + 1, self::HOOK, array( $offset ) );
		spawn_cron();
	}

	/**
	 * Processes one indexing batch.
	 *
	 * @since 0.1.0
	 *
	 * @param int $offset Offset.
	 * @return void
	 */
	public function process_batch( $offset = 0 ) {
		Oli_AS_Compat_TranslatePress::prepare_index_request();
		$offset    = absint( $offset );
		$batch     = max( 10, (int) Oli_AS_Settings::get( 'index_batch_size', 40 ) );
		$items     = $this->get_source_items( $offset, $batch );
		$processed = 0;

		foreach ( $items as $item ) {
			$this->index_item( $item['type'], (int) $item['id'] );
			++$processed;
		}

		$status = $this->get_status();
		$done   = (int) $status['processed'] + $processed;
		$total  = (int) $status['total'];

		if ( count( $items ) < $batch ) {
			$this->update_status(
				array(
					'state'     => 'complete',
					'processed' => $done,
					'total'     => max( $total, $done ),
					'updated'   => time(),
					'message'   => __( 'Index is up to date.', 'oli-ajax-search' ),
				)
			);
			return;
		}

		$this->update_status(
			array(
				'state'     => 'building',
				'processed' => $done,
				'total'     => $total,
				'updated'   => time(),
				'message'   => sprintf(
					/* translators: 1: processed count, 2: total count */
					__( 'Indexed %1$d of %2$d items.', 'oli-ajax-search' ),
					$done,
					$total
				),
			)
		);

		$this->queue_batch( $offset + $batch );
	}

	/**
	 * Indexes a single source item for every language.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Object type.
	 * @param int    $id   Object ID.
	 * @return void
	 */
	public function index_item( $type, $id ) {
		Oli_AS_Compat_TranslatePress::prepare_index_request();
		$id = absint( $id );
		$this->delete_item( $type, $id );

		$languages = Oli_AS_Language::instance()->languages();
		foreach ( $languages as $lang ) {
			$document = $this->build_document( $type, $id, $lang );
			if ( $document ) {
				$this->persist_document( $document );
			}
		}
	}

	/**
	 * Builds a document array for one object and language.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Object type.
	 * @param int    $id   Object ID.
	 * @param string $lang Language.
	 * @return array<string, mixed>|null
	 */
	public function build_document( $type, $id, $lang ) {
		switch ( $type ) {
			case 'product':
				return $this->build_product_document( $id, $lang );
			case 'category':
				return $this->build_term_document( $id, 'product_cat', 'category', $lang );
			case 'tag':
				return $this->build_term_document( $id, 'product_tag', 'tag', $lang );
			case 'attribute':
			case 'brand':
				return $this->build_attribute_document( $id, $lang );
			case 'post':
				return $this->build_post_document( $id, 'post', $lang );
			case 'page':
				return $this->build_post_document( $id, 'page', $lang );
			default:
				return null;
		}
	}

	/**
	 * Builds a product document, including variation SKUs.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $id   Product ID.
	 * @param string $lang Language.
	 * @return array<string, mixed>|null
	 */
	protected function build_product_document( $id, $lang ) {
		$product = wc_get_product( $id );
		if ( ! Oli_AS_Visibility::is_searchable_product( $product ) ) {
			return null;
		}

		if ( Oli_AS_Settings::enabled( 'hide_out_of_stock' ) && ! $product->is_in_stock() ) {
			return null;
		}

		if ( ! $this->passes_product_filter( $id ) ) {
			return null;
		}

		$language = Oli_AS_Language::instance();
		$title    = $this->maybe_switch_post_content( $id, 'product', $lang, $product->get_name() );
		$excerpt  = $this->maybe_switch_post_content( $id, 'product', $lang, $product->get_short_description() );
		$content  = $this->maybe_switch_post_content( $id, 'product', $lang, $product->get_description() );
		$title    = apply_filters( 'the_title', $title, $id );
		$title    = $language->translate( $title, $lang );
		$excerpt  = $language->translate_html( $excerpt, $lang );
		$content  = $language->translate_html( $content, $lang );
		$url      = $language->url( $product->get_permalink(), $lang );
		$sku      = (string) $product->get_sku();
		$fields   = array();
		$weights  = Oli_AS_Settings::weights();

		if ( Oli_AS_Settings::enabled( 'search_title' ) ) {
			$fields['title'] = Oli_AS_Tokenizer::tokenize( $title );
		}
		if ( Oli_AS_Settings::enabled( 'search_excerpt' ) ) {
			$fields['excerpt'] = Oli_AS_Tokenizer::tokenize( $excerpt );
		}
		if ( Oli_AS_Settings::enabled( 'search_content' ) ) {
			$fields['content'] = Oli_AS_Tokenizer::tokenize( $content );
		}
		$variation_skus = array();
		if ( Oli_AS_Settings::enabled( 'search_sku' ) ) {
			$sku_tokens = Oli_AS_Tokenizer::tokenize_sku( $sku );
			if ( Oli_AS_Settings::enabled( 'search_variation_sku' ) && $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $child_id ) {
					$variation = wc_get_product( $child_id );
					if ( $variation ) {
						$child_sku = (string) $variation->get_sku();
						if ( '' !== $child_sku ) {
							$variation_skus[ $child_sku ] = array(
								'id'  => (int) $child_id,
								'url' => $language->url( $variation->get_permalink(), $lang ),
							);
							$sku_tokens                   = array_merge( $sku_tokens, Oli_AS_Tokenizer::tokenize_sku( $child_sku ) );
						}
					}
				}
			}
			$fields['sku'] = array_values( array_unique( $sku_tokens ) );
		}

		if ( Oli_AS_Settings::enabled( 'search_gtin' ) ) {
			$gtin = $this->product_gtin( $product );
			if ( '' !== $gtin ) {
				$meta_tokens    = isset( $fields['meta'] ) ? $fields['meta'] : array();
				$fields['meta'] = array_values( array_unique( array_merge( $meta_tokens, Oli_AS_Tokenizer::tokenize_sku( $gtin ) ) ) );
				$fields['sku']  = isset( $fields['sku'] ) ? $fields['sku'] : array();
				$fields['sku']  = array_values( array_unique( array_merge( $fields['sku'], Oli_AS_Tokenizer::tokenize_sku( $gtin ) ) ) );
			}
		}

		if ( Oli_AS_Settings::enabled( 'search_attributes' ) ) {
			$fields['attribute'] = $this->product_attribute_tokens( $product, $lang );
		}

		if ( Oli_AS_Settings::enabled( 'search_taxonomies' ) || Oli_AS_Settings::enabled( 'search_in_categories' ) || Oli_AS_Settings::enabled( 'search_in_tags' ) ) {
			$fields['taxonomy'] = $this->product_taxonomy_tokens( $id, $lang );
		}

		if ( Oli_AS_Settings::enabled( 'search_custom_fields' ) ) {
			$existing       = isset( $fields['meta'] ) ? $fields['meta'] : array();
			$fields['meta'] = array_values( array_unique( array_merge( $existing, $this->product_meta_tokens( $id ) ) ) );
		}

		$stock = $product->get_stock_status();
		$extra = array(
			'stock'          => $stock,
			'type'           => $product->get_type(),
			'sku'            => $sku,
			'in_stock'       => $product->is_in_stock() ? 1 : 0,
			'price'          => $product->get_price(),
			'gtin'           => $this->product_gtin( $product ),
			'variation_skus' => $variation_skus,
			'on_sale'        => $product->is_on_sale() ? 1 : 0,
			'featured'       => $product->is_featured() ? 1 : 0,
		);

		return array(
			'key'       => Oli_AS_Index_Engine::make_key( 'product', $id, $lang ),
			'id'        => $id,
			'type'      => 'product',
			'language'  => $lang,
			'title'     => $title,
			'sku'       => $sku,
			'excerpt'   => wp_strip_all_tags( $excerpt ),
			'url'       => $url,
			'image_id'  => (int) $product->get_image_id(),
			'parent_id' => 0,
			'extra'     => $extra,
			'fields'    => $fields,
			'weights'   => $weights,
		);
	}

	/**
	 * Builds a taxonomy term document.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy.
	 * @param string $type     Index type.
	 * @param string $lang     Language.
	 * @return array<string, mixed>|null
	 */
	protected function build_term_document( $term_id, $taxonomy, $type, $lang ) {
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		if ( Oli_AS_Compat_WPML::is_active() ) {
			$translated = Oli_AS_Compat_WPML::object_id( $term_id, $taxonomy, $lang );
			if ( $translated ) {
				$term = get_term( $translated, $taxonomy );
				if ( ! $term || is_wp_error( $term ) ) {
					return null;
				}
			}
		}

		$language = Oli_AS_Language::instance();
		$title    = $language->translate( $term->name, $lang );
		$excerpt  = $language->translate( (string) $term->description, $lang );
		$link     = get_term_link( $term );
		$url      = $language->url( is_wp_error( $link ) ? home_url( '/' ) : $link, $lang );
		$thumb    = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );

		$fields = array(
			'title'    => Oli_AS_Tokenizer::tokenize( $title ),
			'excerpt'  => Oli_AS_Tokenizer::tokenize( $excerpt ),
			'taxonomy' => Oli_AS_Tokenizer::tokenize( $title . ' ' . $term->slug ),
		);

		return array(
			'key'       => Oli_AS_Index_Engine::make_key( $type, (int) $term->term_id, $lang ),
			'id'        => (int) $term->term_id,
			'type'      => $type,
			'language'  => $lang,
			'title'     => $title,
			'sku'       => '',
			'excerpt'   => wp_strip_all_tags( $excerpt ),
			'url'       => $url,
			'image_id'  => $thumb,
			'parent_id' => (int) $term->parent,
			'extra'     => array(
				'taxonomy' => $taxonomy,
				'slug'     => $term->slug,
				'count'    => (int) $term->count,
			),
			'fields'    => $fields,
			'weights'   => Oli_AS_Settings::weights(),
		);
	}

	/**
	 * Builds an attribute/brand term document.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $term_id Term ID.
	 * @param string $lang    Language.
	 * @return array<string, mixed>|null
	 */
	protected function build_attribute_document( $term_id, $lang ) {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		$taxonomy = $term->taxonomy;
		$is_brand = in_array( $taxonomy, Oli_AS_Settings::brand_taxonomies(), true );
		$is_attr  = ( 0 === strpos( $taxonomy, 'pa_' ) );
		if ( ! $is_brand && ! $is_attr ) {
			return null;
		}

		$type = $is_brand ? 'brand' : 'attribute';
		$doc  = $this->build_term_document( $term_id, $taxonomy, $type, $lang );
		if ( $doc ) {
			$doc['extra']['is_brand'] = $is_brand ? 1 : 0;
		}
		return $doc;
	}

	/**
	 * Builds a post or page document.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $id   Post ID.
	 * @param string $type post or page.
	 * @param string $lang Language.
	 * @return array<string, mixed>|null
	 */
	protected function build_post_document( $id, $type, $lang ) {
		$post_id = $this->translated_post_id( $id, $type, $lang );
		$post    = get_post( $post_id ? $post_id : $id );
		if ( ! $post || $type !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		$language = Oli_AS_Language::instance();
		$title    = $language->translate( apply_filters( 'the_title', $post->post_title, $post->ID ), $lang );
		$excerpt  = $language->translate_html( $post->post_excerpt, $lang );
		$content  = $language->translate_html( $post->post_content, $lang );
		$url      = $language->url( get_permalink( $post ), $lang );

		$fields = array(
			'title'   => Oli_AS_Tokenizer::tokenize( $title ),
			'excerpt' => Oli_AS_Tokenizer::tokenize( $excerpt ),
			'content' => Oli_AS_Tokenizer::tokenize( $content ),
		);

		return array(
			'key'       => Oli_AS_Index_Engine::make_key( $type, $id, $lang ),
			'id'        => $id,
			'type'      => $type,
			'language'  => $lang,
			'title'     => $title,
			'sku'       => '',
			'excerpt'   => wp_strip_all_tags( $excerpt ),
			'url'       => $url,
			'image_id'  => (int) get_post_thumbnail_id( $post ),
			'parent_id' => (int) $post->post_parent,
			'extra'     => array(),
			'fields'    => $fields,
			'weights'   => Oli_AS_Settings::weights(),
		);
	}

	/**
	 * Writes a document and its postings.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $document Document.
	 * @return void
	 */
	protected function persist_document( $document ) {
		global $wpdb;

		$docs_table  = Oli_AS_Install::docs_table();
		$inv_table   = Oli_AS_Install::inv_table();
		$terms_table = Oli_AS_Install::terms_table();
		$now         = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace(
			$docs_table,
			array(
				'object_key'  => $document['key'],
				'object_id'   => (int) $document['id'],
				'object_type' => $document['type'],
				'language'    => $document['language'],
				'title'       => $document['title'],
				'sku'         => $document['sku'],
				'excerpt'     => $document['excerpt'],
				'url'         => $document['url'],
				'image_id'    => (int) $document['image_id'],
				'parent_id'   => (int) $document['parent_id'],
				'extra'       => wp_json_encode( $document['extra'] ),
				'updated_at'  => $now,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		$weights = isset( $document['weights'] ) ? $document['weights'] : Oli_AS_Settings::weights();
		$fields  = isset( $document['fields'] ) ? $document['fields'] : array();

		foreach ( $fields as $field => $tokens ) {
			$weight = isset( $weights[ $field ] ) ? (int) $weights[ $field ] : 1;
			foreach ( array_unique( $tokens ) as $token ) {
				$token = (string) $token;
				if ( '' === $token ) {
					continue;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->replace(
					$inv_table,
					array(
						'term'       => $token,
						'language'   => $document['language'],
						'object_key' => $document['key'],
						'field'      => $field,
						'weight'     => $weight,
					),
					array( '%s', '%s', '%s', '%s', '%d' )
				);

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->replace(
					$terms_table,
					array(
						'term'     => $token,
						'language' => $document['language'],
					),
					array( '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Deletes all postings for an item in every language.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Object type.
	 * @param int    $id   Object ID.
	 * @return void
	 */
	public function delete_item( $type, $id ) {
		global $wpdb;

		$docs_table = Oli_AS_Install::docs_table();
		$inv_table  = Oli_AS_Install::inv_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT object_key FROM {$docs_table} WHERE object_id = %d AND object_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id,
				$type
			)
		);

		if ( empty( $keys ) ) {
			return;
		}

		foreach ( $keys as $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $inv_table, array( 'object_key' => $key ), array( '%s' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $docs_table, array( 'object_key' => $key ), array( '%s' ) );
		}
	}

	/**
	 * Empties index tables.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function clear_index() {
		global $wpdb;

		$inv   = Oli_AS_Install::inv_table();
		$docs  = Oli_AS_Install::docs_table();
		$terms = Oli_AS_Install::terms_table();

		// Table names cannot be parameterized; they come from a fixed helper.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "TRUNCATE TABLE {$inv}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "TRUNCATE TABLE {$docs}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "TRUNCATE TABLE {$terms}" );
	}

	/**
	 * Counts source items for progress.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public function count_source_items() {
		return count( $this->get_source_items( 0, 0, true ) );
	}

	/**
	 * Lists source items.
	 *
	 * @since 0.1.0
	 *
	 * @param int  $offset Offset.
	 * @param int  $limit  Limit. Zero means all when $count_only is true.
	 * @param bool $count_only Return all IDs for counting.
	 * @return array<int, array<string, mixed>>|array<int, int>
	 */
	protected function get_source_items( $offset, $limit, $count_only = false ) {
		$items = array();

		$products = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $products as $id ) {
			$items[] = array(
				'type' => 'product',
				'id'   => (int) $id,
			);
		}

		foreach ( array(
			'product_cat' => 'category',
			'product_tag' => 'tag',
		) as $taxonomy => $type ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $id ) {
					$items[] = array(
						'type' => $type,
						'id'   => (int) $id,
					);
				}
			}
		}

		$attr_taxonomies = array();
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( wc_get_attribute_taxonomies() as $attribute ) {
				$tax = wc_attribute_taxonomy_name( $attribute->attribute_name );
				if ( $tax ) {
					$attr_taxonomies[] = $tax;
				}
			}
		}
		$attr_taxonomies = array_merge( $attr_taxonomies, Oli_AS_Settings::brand_taxonomies() );
		$attr_taxonomies = array_unique( $attr_taxonomies );

		foreach ( $attr_taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				$type = in_array( $taxonomy, Oli_AS_Settings::brand_taxonomies(), true ) ? 'brand' : 'attribute';
				foreach ( $terms as $id ) {
					$items[] = array(
						'type' => $type,
						'id'   => (int) $id,
					);
				}
			}
		}

		if ( Oli_AS_Settings::enabled( 'index_posts' ) || Oli_AS_Settings::enabled( 'show_posts' ) ) {
			$posts = get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			foreach ( $posts as $id ) {
				$items[] = array(
					'type' => 'post',
					'id'   => (int) $id,
				);
			}
		}

		if ( Oli_AS_Settings::enabled( 'index_pages' ) || Oli_AS_Settings::enabled( 'show_pages' ) ) {
			$pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			foreach ( $pages as $id ) {
				$items[] = array(
					'type' => 'page',
					'id'   => (int) $id,
				);
			}
		}

		if ( $count_only ) {
			return $items;
		}

		return array_slice( $items, $offset, $limit );
	}

	/**
	 * Attribute tokens for a product.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product Product.
	 * @param string     $lang    Language.
	 * @return array<int, string>
	 */
	protected function product_attribute_tokens( $product, $lang ) {
		$tokens   = array();
		$language = Oli_AS_Language::instance();

		foreach ( $product->get_attributes() as $attribute ) {
			if ( is_object( $attribute ) && method_exists( $attribute, 'is_taxonomy' ) && $attribute->is_taxonomy() ) {
				$terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $term ) {
						$tokens = array_merge( $tokens, Oli_AS_Tokenizer::tokenize( $language->translate( $term->name, $lang ) ) );
					}
				}
			} elseif ( is_object( $attribute ) && method_exists( $attribute, 'get_options' ) ) {
				foreach ( (array) $attribute->get_options() as $option ) {
					$tokens = array_merge( $tokens, Oli_AS_Tokenizer::tokenize( $language->translate( (string) $option, $lang ) ) );
				}
			}
		}

		return array_values( array_unique( $tokens ) );
	}

	/**
	 * Taxonomy tokens for a product.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $product_id Product ID.
	 * @param string $lang       Language.
	 * @return array<int, string>
	 */
	protected function product_taxonomy_tokens( $product_id, $lang ) {
		$tokens     = array();
		$language   = Oli_AS_Language::instance();
		$taxonomies = array_merge( array( 'product_cat', 'product_tag' ), Oli_AS_Settings::brand_taxonomies() );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $product_id, $taxonomy );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$tokens = array_merge( $tokens, Oli_AS_Tokenizer::tokenize( $language->translate( $term->name, $lang ) ) );
			}
		}

		return array_values( array_unique( $tokens ) );
	}

	/**
	 * Custom field tokens.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, string>
	 */
	protected function product_meta_tokens( $product_id ) {
		$tokens = array();
		foreach ( Oli_AS_Settings::custom_field_keys() as $key ) {
			$value = get_post_meta( $product_id, $key, true );
			if ( is_scalar( $value ) ) {
				$tokens = array_merge( $tokens, Oli_AS_Tokenizer::tokenize( (string) $value ) );
			}
		}
		return array_values( array_unique( $tokens ) );
	}

	/**
	 * Uses WPML/Polylang translated post content when available.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $id      Original ID.
	 * @param string $type    Type.
	 * @param string $lang    Language.
	 * @param string $fallback Fallback text.
	 * @return string
	 */
	protected function maybe_switch_post_content( $id, $type, $lang, $fallback ) {
		$translated_id = $this->translated_post_id( $id, $type, $lang );
		if ( $translated_id && $translated_id !== $id ) {
			$post = get_post( $translated_id );
			if ( $post ) {
				if ( 'product' === $type ) {
					$product = wc_get_product( $translated_id );
					if ( $product ) {
						return $product->get_name();
					}
				}
				return $post->post_title;
			}
		}
		return $fallback;
	}

	/**
	 * Translated post ID if a multilingual plugin stores separate posts.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $id   ID.
	 * @param string $type Type.
	 * @param string $lang Language.
	 * @return int
	 */
	protected function translated_post_id( $id, $type, $lang ) {
		if ( Oli_AS_Compat_WPML::is_active() ) {
			$translated = Oli_AS_Compat_WPML::object_id( $id, $type, $lang );
			if ( $translated ) {
				return $translated;
			}
		}
		if ( Oli_AS_Compat_Polylang::is_active() ) {
			$translated = Oli_AS_Compat_Polylang::object_id( $id, $lang );
			if ( $translated ) {
				return $translated;
			}
		}
		return $id;
	}

	/**
	 * Incremental product save.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function on_save_product( $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id < 1 ) {
			return;
		}
		$this->index_item( 'product', $product_id );
	}

	/**
	 * Deletes a product from the index.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function on_delete_product( $product_id ) {
		$this->delete_item( 'product', absint( $product_id ) );
	}

	/**
	 * Deletes posts/pages from the index.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_delete_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		if ( in_array( $post->post_type, array( 'product', 'post', 'page' ), true ) ) {
			$this->delete_item( $post->post_type, (int) $post_id );
		}
	}

	/**
	 * Reindexes after stock quantity changes.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	public function on_stock_change( $product ) {
		if ( $product instanceof WC_Product ) {
			$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			if ( $id ) {
				$this->index_item( 'product', (int) $id );
			}
		}
	}

	/**
	 * Reindexes after stock status changes.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function on_stock_status( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product && $product->is_type( 'variation' ) ) {
			$product_id = $product->get_parent_id();
		}
		$this->index_item( 'product', absint( $product_id ) );
	}

	/**
	 * Reindexes a term.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	public function on_term_change( $term_id, $tt_id, $taxonomy ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$type = $this->taxonomy_type( $taxonomy );
		if ( $type ) {
			$this->index_item( $type, (int) $term_id );
		}
	}

	/**
	 * Deletes a term from the index.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	public function on_term_delete( $term_id, $tt_id, $taxonomy ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$type = $this->taxonomy_type( $taxonomy );
		if ( $type ) {
			$this->delete_item( $type, (int) $term_id );
		}
	}

	/**
	 * Reindexes posts and pages.
	 *
	 * @since 0.1.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	public function on_save_content( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		if ( 'post' === $post->post_type && Oli_AS_Settings::enabled( 'index_posts' ) ) {
			$this->index_item( 'post', (int) $post_id );
		}
		if ( 'page' === $post->post_type && Oli_AS_Settings::enabled( 'index_pages' ) ) {
			$this->index_item( 'page', (int) $post_id );
		}
	}

	/**
	 * Rebuilds when TranslatePress translations are saved.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function on_translation_saved() {
		$this->start_refresh();
	}

	/**
	 * Rebuilds documents without truncating the existing index first.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function start_refresh() {
		$status = $this->get_status();
		if ( 'building' === $status['state'] ) {
			return $status;
		}

		$total = $this->count_source_items();
		$this->update_status(
			array(
				'state'     => 'building',
				'processed' => 0,
				'total'     => $total,
				'started'   => time(),
				'updated'   => time(),
				'message'   => __( 'Refreshing translations in the index.', 'oli-ajax-search' ),
			)
		);
		$this->queue_batch( 0 );

		return $this->get_status();
	}

	/**
	 * Daily maintenance placeholder.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function maintenance() {
		Oli_AS_Install::maybe_upgrade();

		if ( ! Oli_AS_Settings::enabled( 'indexer_schedule' ) && ! Oli_AS_Settings::enabled( 'scheduled_rebuild' ) ) {
			return;
		}

		$hour      = (int) current_time( 'G' );
		$target    = (int) Oli_AS_Settings::get( 'indexer_schedule_start_time', 3 );
		$interval  = (string) Oli_AS_Settings::get( 'indexer_schedule_interval', 'weekly' );
		$today     = (int) current_time( 'w' );
		$stamp     = (string) get_option( 'oli_as_last_scheduled_rebuild', '' );
		$today_key = (string) current_time( 'Y-m-d' );

		if ( $hour !== $target ) {
			return;
		}
		if ( 'weekly' === $interval && 1 !== $today ) {
			return;
		}
		if ( $stamp === $today_key ) {
			return;
		}

		update_option( 'oli_as_last_scheduled_rebuild', $today_key, false );
		$this->start_refresh();
	}

	/**
	 * Maps a taxonomy to an index type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return string
	 */
	protected function taxonomy_type( $taxonomy ) {
		if ( 'product_cat' === $taxonomy ) {
			return 'category';
		}
		if ( 'product_tag' === $taxonomy ) {
			return 'tag';
		}
		if ( in_array( $taxonomy, Oli_AS_Settings::brand_taxonomies(), true ) ) {
			return 'brand';
		}
		if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
			return 'attribute';
		}
		return '';
	}

	/**
	 * Product include/exclude rules.
	 *
	 * @since 0.1.1
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	protected function passes_product_filter( $product_id ) {
		$ids      = Oli_AS_Settings::parse_id_list( (string) Oli_AS_Settings::get( 'exclude_ids', '' ) );
		$cats     = Oli_AS_Settings::parse_id_list( (string) Oli_AS_Settings::get( 'exclude_cat_ids', '' ) );
		$mode     = (string) Oli_AS_Settings::get( 'filter_mode', 'exclude' );
		$on_sale  = Oli_AS_Settings::enabled( 'filter_on_sale' );
		$featured = Oli_AS_Settings::enabled( 'filter_featured' );
		$matches  = ( ! empty( $ids ) && in_array( $product_id, $ids, true ) )
			|| ( ! empty( $cats ) && has_term( $cats, 'product_cat', $product_id ) );

		if ( $on_sale || $featured ) {
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
			if ( $product ) {
				if ( $on_sale && $product->is_on_sale() ) {
					$matches = true;
				}
				if ( $featured && $product->is_featured() ) {
					$matches = true;
				}
			}
		}

		if ( 'include' === $mode ) {
			if ( empty( $ids ) && empty( $cats ) && ! $on_sale && ! $featured ) {
				return true;
			}
			return $matches;
		}

		if ( $matches ) {
			return false;
		}
		return true;
	}

	/**
	 * Global unique identifier (GTIN/UPC/EAN/ISBN).
	 *
	 * @since 0.1.1
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	protected function product_gtin( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		if ( method_exists( $product, 'get_global_unique_id' ) ) {
			$value = (string) $product->get_global_unique_id();
			if ( '' !== $value ) {
				return $value;
			}
		}
		$value = (string) $product->get_meta( '_global_unique_id' );
		return $value;
	}

	/**
	 * Returns index status.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_status() {
		$defaults = array(
			'state'     => 'idle',
			'processed' => 0,
			'total'     => 0,
			'started'   => 0,
			'updated'   => 0,
			'message'   => '',
		);
		$stored   = get_option( 'oli_as_index_status', array() );
		return array_merge( $defaults, is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Updates index status.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $status Status.
	 * @return void
	 */
	public function update_status( $status ) {
		update_option( 'oli_as_index_status', array_merge( $this->get_status(), $status ), false );
	}

	/**
	 * Indexed document count.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public function indexed_count() {
		global $wpdb;
		$table = Oli_AS_Install::docs_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}
}
