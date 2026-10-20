<?php
/**
 * Script and style registration.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues frontend and admin assets only where they are needed.
 *
 * @since 0.1.0
 */
class Oli_AS_Assets {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Assets|null
	 */
	protected static $instance = null;

	/**
	 * Whether a search form was rendered on this request.
	 *
	 * @since 0.2.0
	 * @var bool
	 */
	protected $needed = false;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Assets
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_frontend' ), 20 );
		add_action( 'wp_footer', array( $this, 'maybe_enqueue_frontend' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	/**
	 * Marks frontend assets as required.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function mark_needed() {
		$this->needed = true;
	}

	/**
	 * Registers frontend handles without loading them.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register_frontend() {
		wp_register_style(
			'oli-as-frontend',
			OLI_AS_URL . 'assets/css/frontend.css',
			array(),
			OLI_AS_VERSION
		);
		wp_register_script(
			'oli-as-frontend',
			OLI_AS_URL . 'assets/js/frontend.js',
			array(),
			OLI_AS_VERSION,
			true
		);
	}

	/**
	 * Enqueues frontend assets when a form is present or theme replace is on.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function maybe_enqueue_frontend() {
		if ( is_admin() || wp_script_is( 'oli-as-frontend', 'enqueued' ) ) {
			return;
		}

		if ( ! $this->should_enqueue() ) {
			return;
		}

		wp_enqueue_style( 'oli-as-frontend' );
		wp_enqueue_script( 'oli-as-frontend' );

		$settings = Oli_AS_Settings::get_all();
		$lang     = Oli_AS_Language::instance()->current();
		$see_all  = trim( (string) $settings['see_all_text'] );
		$empty    = trim( wp_strip_all_tags( (string) $settings['no_results_text'] ) );

		wp_localize_script(
			'oli-as-frontend',
			'oliAsSettings',
			array(
				'prettyUrl'         => esc_url_raw( Oli_AS_Language::instance()->url( home_url( '/oli-as/suggest' ), $lang ) ),
				'restUrl'           => esc_url_raw( rest_url( 'oli-as/v1/suggest' ) ),
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'oli_as_search' ),
				'restNonce'         => wp_create_nonce( 'wp_rest' ),
				'lang'              => $lang,
				'minChars'          => (int) $settings['min_chars'],
				'debounce'          => (int) $settings['debounce_ms'],
				'mobileOverlay'     => Oli_AS_Settings::enabled( 'mobile_overlay' ),
				'mobileBreakpoint'  => (int) $settings['mobile_breakpoint'],
				'overlayBreakpoint' => (int) $settings['mobile_overlay_breakpoint'],
				'darkenBackground'  => Oli_AS_Settings::enabled( 'darken_background' ),
				'showPreloader'     => Oli_AS_Settings::enabled( 'show_preloader' ),
				'preloaderUrl'      => (string) $settings['preloader_url'],
				'replaceThemeForm'  => Oli_AS_Settings::enabled( 'replace_theme_form' ),
				'showDetails'       => Oli_AS_Settings::enabled( 'show_details_panel' ),
				'showRecent'        => Oli_AS_Settings::enabled( 'show_recent' ),
				'showImage'         => Oli_AS_Settings::enabled( 'show_image' ),
				'showPrice'         => Oli_AS_Settings::enabled( 'show_price' ),
				'showSku'           => Oli_AS_Settings::enabled( 'show_sku' ),
				'showExcerpt'       => Oli_AS_Settings::enabled( 'show_excerpt' ),
				'showStock'         => Oli_AS_Settings::enabled( 'show_stock' ),
				'showSaleBadge'     => Oli_AS_Settings::enabled( 'show_sale_badge' ),
				'showFeaturedBadge' => Oli_AS_Settings::enabled( 'show_featured_badge' ),
				'showCategories'    => Oli_AS_Settings::enabled( 'show_categories' ),
				'showTags'          => Oli_AS_Settings::enabled( 'show_tags' ),
				'showBrands'        => Oli_AS_Settings::enabled( 'show_brands' ),
				'showPosts'         => Oli_AS_Settings::enabled( 'show_posts' ),
				'showPages'         => Oli_AS_Settings::enabled( 'show_pages' ),
				'layout'            => (string) $settings['layout'],
				'style'             => (string) $settings['search_style'],
				'i18n'              => array(
					'products'   => __( 'Products', 'oli-ajax-search' ),
					'categories' => __( 'Categories', 'oli-ajax-search' ),
					'tags'       => __( 'Tags', 'oli-ajax-search' ),
					'brands'     => __( 'Brands', 'oli-ajax-search' ),
					'attributes' => __( 'Attributes', 'oli-ajax-search' ),
					'posts'      => __( 'Posts', 'oli-ajax-search' ),
					'pages'      => __( 'Pages', 'oli-ajax-search' ),
					'seeAll'     => '' !== $see_all ? $see_all : __( 'See all products...', 'oli-ajax-search' ),
					'noResults'  => '' !== $empty ? $empty : __( 'No results', 'oli-ajax-search' ),
					'sku'        => __( 'SKU', 'oli-ajax-search' ),
					'inStock'    => __( 'In stock', 'oli-ajax-search' ),
					'outOfStock' => __( 'Out of stock', 'oli-ajax-search' ),
					'addToCart'  => __( 'Add to cart', 'oli-ajax-search' ),
					'recent'     => __( 'Recent searches', 'oli-ajax-search' ),
					'close'      => __( 'Close search', 'oli-ajax-search' ),
					'search'     => __( 'Search', 'oli-ajax-search' ),
					'sale'       => __( 'Sale', 'oli-ajax-search' ),
					'featured'   => __( 'Featured', 'oli-ajax-search' ),
				),
				'selectors'         => array(
					'form.woocommerce-product-search',
					'.site-search form',
					'.storefront-handheld-footer-bar .search form',
					'.ast-search-menu-icon form',
					'.ast-search-box.header-cover form',
					'.header-navigation-search form',
					'.search-toggle-form form',
					'.flatsome-search form',
					'.searchform',
					'.oceanwp-searchform',
					'.et_search_form_container form',
					'form.search-form',
					'form.wp-block-search',
				),
			)
		);

		wp_add_inline_style( 'oli-as-frontend', $this->inline_css( $settings ) );
	}

	/**
	 * Builds personalization CSS from settings.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return string
	 */
	protected function inline_css( $settings ) {
		$max_width = (int) $settings['max_form_width'];
		$vars      = array(
			'--oli-as-bg'         => $this->color_or( $settings['color_bg'], '#ffffff' ),
			'--oli-as-text'       => $this->color_or( $settings['color_text'], '#111111' ),
			'--oli-as-border'     => $this->color_or( $settings['color_border'], '#d0d0d0' ),
			'--oli-as-accent'     => $this->color_or( $settings['color_accent'], '#2271b1' ),
			'--oli-as-hover'      => $this->color_or( $settings['sug_hover_color'] ? $settings['sug_hover_color'] : $settings['color_hover'], '#f4f7fb' ),
			'--oli-as-price'      => $this->color_or( $settings['color_price'], '#111111' ),
			'--oli-as-highlight'  => $this->color_or( $settings['sug_highlight_color'] ? $settings['sug_highlight_color'] : $settings['color_highlight'], '#fff3bf' ),
			'--oli-as-sug-bg'     => $this->color_or( $settings['sug_bg_color'], '#ffffff' ),
			'--oli-as-sug-text'   => $this->color_or( $settings['sug_text_color'], '#111111' ),
			'--oli-as-sug-border' => $this->color_or( $settings['sug_border_color'], '#dddddd' ),
			'--oli-as-submit-bg'  => $this->color_or( $settings['bg_submit_color'], $this->color_or( $settings['color_accent'], '#2271b1' ) ),
			'--oli-as-submit-fg'  => $this->color_or( $settings['text_submit_color'], '#ffffff' ),
			'--oli-as-icon'       => $this->color_or( $settings['search_icon_color'], $this->color_or( $settings['color_accent'], '#2271b1' ) ),
			'--oli-as-underlay'   => $this->color_or( $settings['bg_input_underlay_color'], 'transparent' ),
		);

		$pairs = array();
		foreach ( $vars as $name => $value ) {
			$pairs[] = $name . ':' . $value;
		}
		if ( $max_width > 0 ) {
			$pairs[] = 'max-width:' . $max_width . 'px';
		}

		$css  = '.oli-as-root{' . implode( ';', $pairs ) . ';}';
		$css .= sprintf(
			'@media (max-width:%dpx){.oli-as-details{display:none;}}',
			max( 320, (int) $settings['mobile_breakpoint'] )
		);

		return $css;
	}

	/**
	 * Returns a sanitized color or a fallback.
	 *
	 * @since 0.2.0
	 *
	 * @param string $color    Color.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	protected function color_or( $color, $fallback ) {
		$clean = is_string( $color ) ? sanitize_hex_color( $color ) : '';
		return $clean ? $clean : $fallback;
	}

	/**
	 * Whether frontend assets should load.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	protected function should_enqueue() {
		if ( $this->needed ) {
			return true;
		}

		if ( Oli_AS_Settings::enabled( 'replace_theme_form' ) ) {
			return true;
		}

		if ( is_active_widget( false, false, 'oli_as_widget', true ) ) {
			return true;
		}

		if ( is_singular() ) {
			$post = get_post();
			if ( $post instanceof WP_Post ) {
				if ( has_shortcode( $post->post_content, 'oli_search' ) || has_shortcode( $post->post_content, 'oli_ajax_search' ) ) {
					return true;
				}
				if ( function_exists( 'has_block' ) && ( has_block( 'oli-ajax-search/search-form', $post ) || has_block( 'oli-ajax-search/search-nav', $post ) ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Admin assets.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook Current screen hook.
	 * @return void
	 */
	public function enqueue_admin( $hook ) {
		$is_settings = false !== strpos( $hook, 'oli-ajax-search' );
		$is_preview  = $is_settings;

		if ( ! $is_settings ) {
			return;
		}

		if ( $is_preview ) {
			$this->register_frontend();
			$this->mark_needed();
			$this->maybe_enqueue_frontend();
		}

		wp_enqueue_style(
			'oli-as-admin',
			OLI_AS_URL . 'assets/css/admin.css',
			array( 'wp-color-picker' ),
			OLI_AS_VERSION
		);
		wp_enqueue_script(
			'oli-as-admin',
			OLI_AS_URL . 'assets/js/admin.js',
			array( 'wp-color-picker' ),
			OLI_AS_VERSION,
			true
		);

		wp_localize_script(
			'oli-as-admin',
			'oliAsAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'oli_as_admin' ),
			)
		);
	}
}
