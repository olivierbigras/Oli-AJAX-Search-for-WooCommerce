<?php
/**
 * Script and style registration.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues frontend and admin assets.
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	/**
	 * Frontend assets.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_frontend() {
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

		wp_enqueue_style( 'oli-as-frontend' );
		wp_enqueue_script( 'oli-as-frontend' );

		$settings = Oli_AS_Settings::get_all();
		$lang     = Oli_AS_Language::instance()->current();
		$see_all  = trim( (string) $settings['see_all_text'] );
		$empty    = trim( (string) $settings['no_results_text'] );

		wp_localize_script(
			'oli-as-frontend',
			'oliAsSettings',
			array(
				'prettyUrl'        => esc_url_raw( Oli_AS_Language::instance()->url( home_url( '/oli-as/suggest' ), $lang ) ),
				'restUrl'          => esc_url_raw( rest_url( 'oli-as/v1/suggest' ) ),
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'oli_as_search' ),
				'restNonce'        => wp_create_nonce( 'wp_rest' ),
				'lang'             => $lang,
				'minChars'         => (int) $settings['min_chars'],
				'debounce'         => (int) $settings['debounce_ms'],
				'mobileOverlay'    => Oli_AS_Settings::enabled( 'mobile_overlay' ),
				'mobileBreakpoint' => (int) $settings['mobile_breakpoint'],
				'darkenBackground' => Oli_AS_Settings::enabled( 'darken_background' ),
				'showPreloader'    => Oli_AS_Settings::enabled( 'show_preloader' ),
				'replaceThemeForm' => Oli_AS_Settings::enabled( 'replace_theme_form' ),
				'showDetails'      => Oli_AS_Settings::enabled( 'show_details_panel' ),
				'showRecent'       => Oli_AS_Settings::enabled( 'show_recent' ),
				'layout'           => (string) $settings['layout'],
				'i18n'             => array(
					'products'   => __( 'Products', 'oli-ajax-search' ),
					'categories' => __( 'Categories', 'oli-ajax-search' ),
					'tags'       => __( 'Tags', 'oli-ajax-search' ),
					'brands'     => __( 'Brands', 'oli-ajax-search' ),
					'attributes' => __( 'Attributes', 'oli-ajax-search' ),
					'posts'      => __( 'Posts', 'oli-ajax-search' ),
					'pages'      => __( 'Pages', 'oli-ajax-search' ),
					'seeAll'     => '' !== $see_all ? $see_all : __( 'See all results', 'oli-ajax-search' ),
					'noResults'  => '' !== $empty ? $empty : __( 'No results found.', 'oli-ajax-search' ),
					'sku'        => __( 'SKU', 'oli-ajax-search' ),
					'inStock'    => __( 'In stock', 'oli-ajax-search' ),
					'outOfStock' => __( 'Out of stock', 'oli-ajax-search' ),
					'addToCart'  => __( 'Add to cart', 'oli-ajax-search' ),
					'recent'     => __( 'Recent searches', 'oli-ajax-search' ),
					'close'      => __( 'Close search', 'oli-ajax-search' ),
					'search'     => __( 'Search', 'oli-ajax-search' ),
				),
				'selectors'        => array(
					'form.woocommerce-product-search',
					'.site-search form',
					'.storefront-handheld-footer-bar .search form',
					'.ast-search-menu-icon form',
					'.ast-search-box.header-cover form',
					'.header-navigation-search form',
					'.search-toggle-form form',
					'form.search-form',
					'form.wp-block-search',
				),
			)
		);

		$max_width = (int) $settings['max_form_width'];
		$css       = sprintf(
			'.oli-as-root{--oli-as-bg:%1$s;--oli-as-text:%2$s;--oli-as-border:%3$s;--oli-as-accent:%4$s;--oli-as-hover:%5$s;--oli-as-price:%6$s;--oli-as-highlight:%7$s;%8$s}',
			sanitize_hex_color( $settings['color_bg'] ),
			sanitize_hex_color( $settings['color_text'] ),
			sanitize_hex_color( $settings['color_border'] ),
			sanitize_hex_color( $settings['color_accent'] ),
			sanitize_hex_color( $settings['color_hover'] ),
			sanitize_hex_color( $settings['color_price'] ),
			sanitize_hex_color( $settings['color_highlight'] ),
			$max_width > 0 ? 'max-width:' . $max_width . 'px;' : ''
		);
		$css      .= sprintf(
			'@media (max-width:%dpx){.oli-as-details{display:none;}}',
			max( 320, (int) $settings['mobile_breakpoint'] )
		);
		wp_add_inline_style( 'oli-as-frontend', $css );
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
		if ( false === strpos( $hook, 'oli-ajax-search' ) ) {
			return;
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
