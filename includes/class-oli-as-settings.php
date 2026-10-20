<?php
/**
 * Plugin settings.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and sanitizes plugin options.
 *
 * @since 0.1.0
 */
class Oli_AS_Settings {

	/**
	 * Option key.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	const OPTION = 'oli_as_settings';

	/**
	 * Cached settings.
	 *
	 * @since 0.1.0
	 * @var array<string, mixed>|null
	 */
	protected static $cache = null;

	/**
	 * Default settings.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'placeholder'          => __( 'Search products…', 'oli-ajax-search' ),
			'min_chars'            => 2,
			'debounce_ms'          => 180,
			'max_products'         => 6,
			'max_categories'       => 3,
			'max_tags'             => 3,
			'max_attributes'       => 3,
			'max_posts'            => 2,
			'max_pages'            => 2,
			'layout'               => 'classic',
			'mobile_overlay'       => '1',
			'replace_theme_form'   => '1',
			'show_image'           => '1',
			'show_price'           => '1',
			'show_sku'             => '1',
			'show_stock'           => '1',
			'show_excerpt'         => '1',
			'show_details_panel'   => '1',
			'show_add_to_cart'     => '1',
			'show_recent'          => '1',
			'search_title'         => '1',
			'search_content'       => '1',
			'search_excerpt'       => '1',
			'search_sku'           => '1',
			'search_variation_sku' => '1',
			'search_attributes'    => '1',
			'search_taxonomies'    => '1',
			'search_custom_fields' => '1',
			'custom_fields'        => '',
			'fuzzy'                => '1',
			'synonyms'             => '',
			'stopwords'            => 'the,and,for,with,from,this,that,les,des,une,pour,avec,dans',
			'exclude_ids'          => '',
			'exclude_cat_ids'      => '',
			'hide_out_of_stock'    => '0',
			'use_on_results_page'  => '1',
			'color_bg'             => '#ffffff',
			'color_text'           => '#111111',
			'color_border'         => '#d0d0d0',
			'color_accent'         => '#2271b1',
			'color_hover'          => '#f4f7fb',
			'color_price'          => '#111111',
			'weight_title'         => 10,
			'weight_sku'           => 12,
			'weight_excerpt'       => 4,
			'weight_content'       => 2,
			'weight_attribute'     => 6,
			'weight_meta'          => 5,
			'weight_taxonomy'      => 7,
			'brand_taxonomies'     => 'product_brand,pwb-brand,yith_product_brand,pa_brand',
			'index_posts'          => '1',
			'index_pages'          => '1',
			'index_batch_size'     => 40,
		);
	}

	/**
	 * Returns merged settings.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored      = get_option( self::OPTION, array() );
		self::$cache = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );

		return self::$cache;
	}

	/**
	 * Returns one setting.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Fallback.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = self::get_all();
		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}
		return $fallback;
	}

	/**
	 * Whether a checkbox setting is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function enabled( $key ) {
		$value = self::get( $key, '0' );
		return ( '1' === (string) $value || true === $value || 1 === $value );
	}

	/**
	 * Clears the static cache.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Sanitizes posted settings.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$output   = $defaults;
		$input    = is_array( $input ) ? $input : array();

		$text_keys = array(
			'placeholder',
			'custom_fields',
			'synonyms',
			'stopwords',
			'exclude_ids',
			'exclude_cat_ids',
			'brand_taxonomies',
		);

		foreach ( $text_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = sanitize_textarea_field( wp_unslash( $input[ $key ] ) );
			}
		}

		if ( isset( $input['placeholder'] ) ) {
			$output['placeholder'] = sanitize_text_field( wp_unslash( $input['placeholder'] ) );
		}

		$int_keys = array(
			'min_chars'        => array( 1, 10 ),
			'debounce_ms'      => array( 50, 2000 ),
			'max_products'     => array( 1, 30 ),
			'max_categories'   => array( 0, 20 ),
			'max_tags'         => array( 0, 20 ),
			'max_attributes'   => array( 0, 20 ),
			'max_posts'        => array( 0, 20 ),
			'max_pages'        => array( 0, 20 ),
			'weight_title'     => array( 0, 50 ),
			'weight_sku'       => array( 0, 50 ),
			'weight_excerpt'   => array( 0, 50 ),
			'weight_content'   => array( 0, 50 ),
			'weight_attribute' => array( 0, 50 ),
			'weight_meta'      => array( 0, 50 ),
			'weight_taxonomy'  => array( 0, 50 ),
			'index_batch_size' => array( 10, 200 ),
		);

		foreach ( $int_keys as $key => $range ) {
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = min( $range[1], max( $range[0], absint( $input[ $key ] ) ) );
			}
		}

		$layouts          = array( 'classic', 'icon' );
		$output['layout'] = ( isset( $input['layout'] ) && in_array( $input['layout'], $layouts, true ) ) ? $input['layout'] : 'classic';

		$checkboxes = array(
			'mobile_overlay',
			'replace_theme_form',
			'show_image',
			'show_price',
			'show_sku',
			'show_stock',
			'show_excerpt',
			'show_details_panel',
			'show_add_to_cart',
			'show_recent',
			'search_title',
			'search_content',
			'search_excerpt',
			'search_sku',
			'search_variation_sku',
			'search_attributes',
			'search_taxonomies',
			'search_custom_fields',
			'fuzzy',
			'hide_out_of_stock',
			'use_on_results_page',
			'index_posts',
			'index_pages',
		);

		foreach ( $checkboxes as $key ) {
			$output[ $key ] = ( ! empty( $input[ $key ] ) ) ? '1' : '0';
		}

		$colors = array( 'color_bg', 'color_text', 'color_border', 'color_accent', 'color_hover', 'color_price' );
		foreach ( $colors as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$color = sanitize_hex_color( wp_unslash( $input[ $key ] ) );
				if ( $color ) {
					$output[ $key ] = $color;
				}
			}
		}

		self::$cache = null;

		return $output;
	}

	/**
	 * Parses a comma/newline list of integers.
	 *
	 * @since 0.1.0
	 *
	 * @param string $raw Raw list.
	 * @return array<int, int>
	 */
	public static function parse_id_list( $raw ) {
		$parts = preg_split( '/[\s,]+/', (string) $raw );
		$ids   = array();
		if ( ! is_array( $parts ) ) {
			return $ids;
		}
		foreach ( $parts as $part ) {
			$id = absint( $part );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Parses stopwords.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	public static function stopwords() {
		$raw    = (string) self::get( 'stopwords', '' );
		$parts  = preg_split( '/[\s,]+/', $raw );
		$tokens = array();
		if ( ! is_array( $parts ) ) {
			return $tokens;
		}
		foreach ( $parts as $part ) {
			$normalized = Oli_AS_Tokenizer::tokenize( $part, 1 );
			foreach ( $normalized as $token ) {
				$tokens[] = $token;
			}
		}
		return array_values( array_unique( $tokens ) );
	}

	/**
	 * Parsed synonym groups.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, array<int, string>>
	 */
	public static function synonym_groups() {
		return Oli_AS_Synonyms::parse( (string) self::get( 'synonyms', '' ) );
	}

	/**
	 * Field weights from settings.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, int>
	 */
	public static function weights() {
		return array(
			'title'     => (int) self::get( 'weight_title', 10 ),
			'sku'       => (int) self::get( 'weight_sku', 12 ),
			'excerpt'   => (int) self::get( 'weight_excerpt', 4 ),
			'content'   => (int) self::get( 'weight_content', 2 ),
			'attribute' => (int) self::get( 'weight_attribute', 6 ),
			'meta'      => (int) self::get( 'weight_meta', 5 ),
			'taxonomy'  => (int) self::get( 'weight_taxonomy', 7 ),
		);
	}

	/**
	 * Custom field keys to index.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	public static function custom_field_keys() {
		$raw   = (string) self::get( 'custom_fields', '' );
		$parts = preg_split( '/[\s,]+/', $raw );
		$keys  = array();
		if ( ! is_array( $parts ) ) {
			return $keys;
		}
		foreach ( $parts as $part ) {
			$key = sanitize_key( $part );
			if ( '' !== $key ) {
				$keys[] = $key;
			}
		}
		return array_values( array_unique( $keys ) );
	}

	/**
	 * Brand taxonomy slugs.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string>
	 */
	public static function brand_taxonomies() {
		$raw   = (string) self::get( 'brand_taxonomies', '' );
		$parts = preg_split( '/[\s,]+/', $raw );
		$tax   = array();
		if ( ! is_array( $parts ) ) {
			return $tax;
		}
		foreach ( $parts as $part ) {
			$slug = sanitize_key( $part );
			if ( '' !== $slug ) {
				$tax[] = $slug;
			}
		}
		return array_values( array_unique( $tax ) );
	}
}
