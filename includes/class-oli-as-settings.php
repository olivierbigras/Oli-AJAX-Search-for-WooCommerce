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
			'replace_theme_form'          => '0',
			'min_chars'                   => 3,
			'max_form_width'              => 600,
			'show_submit'                 => '0',
			'submit_text'                 => __( 'Search', 'oli-ajax-search' ),
			'bg_submit_color'             => '',
			'text_submit_color'           => '',
			'placeholder'                 => __( 'Search for products...', 'oli-ajax-search' ),
			'search_style'                => 'oval',
			'layout'                      => 'classic',
			'mobile_breakpoint'           => 992,
			'search_icon_color'           => '',
			'mobile_overlay'              => '1',
			'mobile_overlay_breakpoint'   => 992,
			'darken_background'           => '0',
			'bg_input_underlay_color'     => '',
			'color_bg'                    => '',
			'color_text'                  => '',
			'color_border'                => '',
			'show_preloader'              => '1',
			'preloader_url'               => '',
			'suggestions_limit'           => 7,
			'no_results_text'             => __( 'No results', 'oli-ajax-search' ),
			'show_image'                  => '1',
			'show_price'                  => '0',
			'show_excerpt'                => '0',
			'show_sku'                    => '0',
			'show_stock'                  => '0',
			'show_sale_badge'             => '0',
			'show_featured_badge'         => '0',
			'see_all_text'                => __( 'See all products...', 'oli-ajax-search' ),
			'show_categories'             => '1',
			'show_category_images'        => '0',
			'show_tags'                   => '0',
			'show_brands'                 => '0',
			'show_brand_images'           => '0',
			'show_posts'                  => '0',
			'show_pages'                  => '0',
			'show_details_panel'          => '0',
			'show_add_to_cart'            => '1',
			'show_recent'                 => '0',
			'sug_bg_color'                => '',
			'sug_hover_color'             => '',
			'sug_text_color'              => '',
			'sug_highlight_color'         => '',
			'sug_border_color'            => '',
			'search_title'                => '1',
			'search_content'              => '0',
			'search_excerpt'              => '0',
			'search_sku'                  => '0',
			'search_variation_sku'        => '1',
			'search_gtin'                 => '0',
			'search_attributes'           => '0',
			'search_taxonomies'           => '0',
			'search_in_categories'        => '0',
			'search_in_tags'              => '0',
			'search_custom_fields'        => '0',
			'custom_fields'               => '',
			'hide_out_of_stock'           => '0',
			'fuzzy_level'                 => 'normal',
			'fuzzy'                       => '1',
			'synonyms'                    => '',
			'stopwords'                   => '',
			'filter_mode'                 => 'exclude',
			'exclude_ids'                 => '',
			'exclude_cat_ids'             => '',
			'filter_on_sale'              => '0',
			'filter_featured'             => '0',
			'analytics_enabled'           => '0',
			'analytics_widget'            => '0',
			'indexer_schedule'            => '0',
			'indexer_schedule_interval'   => 'weekly',
			'indexer_schedule_start_time' => 3,
			'use_on_results_page'         => '1',
			'index_posts'                 => '0',
			'index_pages'                 => '0',
			'index_batch_size'            => 40,
			'debounce_ms'                 => 400,
			'max_products'                => 7,
			'max_categories'              => 3,
			'max_tags'                    => 3,
			'max_attributes'              => 3,
			'max_brands'                  => 3,
			'max_posts'                   => 2,
			'max_pages'                   => 2,
			'color_accent'                => '#2271b1',
			'color_hover'                 => '#f4f7fb',
			'color_price'                 => '#111111',
			'color_highlight'             => '#fff3bf',
			'weight_title'                => 10,
			'weight_sku'                  => 12,
			'weight_excerpt'              => 4,
			'weight_content'              => 2,
			'weight_attribute'            => 6,
			'weight_meta'                 => 5,
			'weight_taxonomy'             => 7,
			'brand_taxonomies'            => 'product_brand,pwb-brand,yith_product_brand,pa_brand',
			'show_advanced'               => '0',
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

		if ( empty( self::$cache['max_products'] ) && ! empty( self::$cache['suggestions_limit'] ) ) {
			self::$cache['max_products'] = (int) self::$cache['suggestions_limit'];
		}

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
		return ( '1' === (string) $value || 'on' === (string) $value || true === $value || 1 === $value );
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
		$stored = get_option( self::OPTION, array() );
		$output = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
		$input  = is_array( $input ) ? $input : array();

		$text_keys = array(
			'placeholder',
			'custom_fields',
			'synonyms',
			'stopwords',
			'exclude_ids',
			'exclude_cat_ids',
			'brand_taxonomies',
			'submit_text',
			'no_results_text',
			'see_all_text',
			'preloader_url',
			'bg_submit_color',
			'text_submit_color',
			'search_icon_color',
			'bg_input_underlay_color',
			'sug_bg_color',
			'sug_hover_color',
			'sug_text_color',
			'sug_highlight_color',
			'sug_border_color',
		);

		foreach ( $text_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = sanitize_textarea_field( wp_unslash( $input[ $key ] ) );
			}
		}

		if ( isset( $input['placeholder'] ) ) {
			$output['placeholder'] = sanitize_text_field( wp_unslash( $input['placeholder'] ) );
		}

		if ( isset( $input['preloader_url'] ) ) {
			$output['preloader_url'] = esc_url_raw( wp_unslash( $input['preloader_url'] ) );
		}

		if ( isset( $input['no_results_text'] ) ) {
			$output['no_results_text'] = wp_kses_post( wp_unslash( $input['no_results_text'] ) );
		}

		$int_keys = array(
			'min_chars'                   => array( 1, 10 ),
			'debounce_ms'                 => array( 50, 2000 ),
			'suggestions_limit'           => array( 1, 30 ),
			'max_products'                => array( 1, 30 ),
			'max_categories'              => array( 0, 20 ),
			'max_tags'                    => array( 0, 20 ),
			'max_attributes'              => array( 0, 20 ),
			'max_brands'                  => array( 0, 20 ),
			'max_posts'                   => array( 0, 20 ),
			'max_pages'                   => array( 0, 20 ),
			'mobile_breakpoint'           => array( 320, 1600 ),
			'mobile_overlay_breakpoint'   => array( 320, 1600 ),
			'max_form_width'              => array( 0, 2000 ),
			'indexer_schedule_start_time' => array( 0, 23 ),
			'weight_title'                => array( 0, 50 ),
			'weight_sku'                  => array( 0, 50 ),
			'weight_excerpt'              => array( 0, 50 ),
			'weight_content'              => array( 0, 50 ),
			'weight_attribute'            => array( 0, 50 ),
			'weight_meta'                 => array( 0, 50 ),
			'weight_taxonomy'             => array( 0, 50 ),
			'index_batch_size'            => array( 10, 200 ),
		);

		foreach ( $int_keys as $key => $range ) {
			if ( isset( $input[ $key ] ) ) {
				if ( '' === $input[ $key ] && 'max_form_width' === $key ) {
					$output[ $key ] = 0;
					continue;
				}
				$output[ $key ] = min( $range[1], max( $range[0], absint( $input[ $key ] ) ) );
			}
		}

		if ( isset( $input['suggestions_limit'] ) ) {
			$output['max_products'] = (int) $output['suggestions_limit'];
		}

		if ( isset( $input['layout'] ) ) {
			$layouts          = array( 'classic', 'icon', 'icon-flexible', 'icon-flexible-inv' );
			$output['layout'] = in_array( $input['layout'], $layouts, true ) ? $input['layout'] : 'classic';
		}

		if ( isset( $input['search_style'] ) ) {
			$styles                 = array( 'oval', 'oval-compact', 'rectangle' );
			$output['search_style'] = in_array( $input['search_style'], $styles, true ) ? $input['search_style'] : 'oval';
		}

		if ( isset( $input['fuzzy_level'] ) ) {
			$levels                = array( 'off', 'soft', 'normal', 'hard' );
			$output['fuzzy_level'] = in_array( $input['fuzzy_level'], $levels, true ) ? $input['fuzzy_level'] : 'normal';
			$output['fuzzy']       = ( 'off' === $output['fuzzy_level'] ) ? '0' : '1';
		}

		if ( isset( $input['filter_mode'] ) ) {
			$modes                 = array( 'exclude', 'include' );
			$output['filter_mode'] = in_array( $input['filter_mode'], $modes, true ) ? $input['filter_mode'] : 'exclude';
		}

		if ( isset( $input['indexer_schedule_interval'] ) ) {
			$intervals                           = array( 'daily', 'weekly' );
			$output['indexer_schedule_interval'] = in_array( $input['indexer_schedule_interval'], $intervals, true ) ? $input['indexer_schedule_interval'] : 'weekly';
		}

		$checkboxes = array(
			'replace_theme_form',
			'show_submit',
			'mobile_overlay',
			'darken_background',
			'show_preloader',
			'show_image',
			'show_price',
			'show_excerpt',
			'show_sku',
			'show_stock',
			'show_sale_badge',
			'show_featured_badge',
			'show_categories',
			'show_category_images',
			'show_tags',
			'show_brands',
			'show_brand_images',
			'show_posts',
			'show_pages',
			'show_details_panel',
			'show_add_to_cart',
			'show_recent',
			'search_title',
			'search_content',
			'search_excerpt',
			'search_sku',
			'search_variation_sku',
			'search_gtin',
			'search_attributes',
			'search_taxonomies',
			'search_in_categories',
			'search_in_tags',
			'search_custom_fields',
			'hide_out_of_stock',
			'filter_on_sale',
			'filter_featured',
			'analytics_enabled',
			'analytics_widget',
			'indexer_schedule',
			'use_on_results_page',
			'index_posts',
			'index_pages',
			'show_advanced',
		);

		foreach ( $checkboxes as $key ) {
			if ( isset( $input['_seen'] ) && is_array( $input['_seen'] ) && isset( $input['_seen'][ $key ] ) ) {
				$output[ $key ] = ( ! empty( $input[ $key ] ) ) ? '1' : '0';
			} elseif ( isset( $input[ $key ] ) ) {
				$output[ $key ] = ( ! empty( $input[ $key ] ) ) ? '1' : '0';
			}
		}

		$colors = array(
			'color_bg',
			'color_text',
			'color_border',
			'color_accent',
			'color_hover',
			'color_price',
			'color_highlight',
			'bg_submit_color',
			'text_submit_color',
			'search_icon_color',
			'bg_input_underlay_color',
			'sug_bg_color',
			'sug_hover_color',
			'sug_text_color',
			'sug_highlight_color',
			'sug_border_color',
		);
		foreach ( $colors as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$raw = sanitize_text_field( wp_unslash( $input[ $key ] ) );
				if ( '' === $raw ) {
					$output[ $key ] = '';
					continue;
				}
				$color = sanitize_hex_color( $raw );
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
	 * Fuzzy matching level.
	 *
	 * @since 0.1.1
	 *
	 * @return string off|soft|normal|hard
	 */
	public static function fuzzy_level() {
		$level = (string) self::get( 'fuzzy_level', '' );
		if ( in_array( $level, array( 'off', 'soft', 'normal', 'hard' ), true ) ) {
			return $level;
		}
		return self::enabled( 'fuzzy' ) ? 'normal' : 'off';
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

	/**
	 * Hours for the indexer scheduler.
	 *
	 * @since 0.2.0
	 *
	 * @return array<int, string>
	 */
	public static function hours() {
		$hours = array();
		for ( $i = 0; $i < 24; $i++ ) {
			$hours[ $i ] = sprintf( '%02d:00', $i );
		}
		return $hours;
	}
}
