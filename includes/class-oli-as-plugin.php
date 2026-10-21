<?php
/**
 * Main plugin controller.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers hooks for frontend, admin, index, and search.
 *
 * @since 0.1.0
 */
class Oli_AS_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	protected function __construct() {}

	/**
	 * Wires all runtime hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		load_plugin_textdomain( 'oli-ajax-search', false, dirname( OLI_AS_BASENAME ) . '/languages' );

		Oli_AS_Install::maybe_upgrade();

		Oli_AS_Assets::instance()->init();
		Oli_AS_Rest::instance()->init();
		Oli_AS_Ajax::instance()->init();
		Oli_AS_Shortcode::instance()->init();
		Oli_AS_Block::instance()->init();
		Oli_AS_Menu::instance()->init();
		Oli_AS_Form_Replacer::instance()->init();
		Oli_AS_Results_Page::instance()->init();
		Oli_AS_Indexer::instance()->init();
		Oli_AS_Analytics::instance()->init();
		Oli_AS_Language::instance()->init();
		Oli_AS_Troubleshooting::instance()->init();
		Oli_AS_Compat_Qtranslate::init();
		Oli_AS_Compat_Elementor::init();

		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		if ( is_admin() ) {
			Oli_AS_Admin::instance()->init();
		}

		add_filter( 'plugin_action_links_' . OLI_AS_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Registers the search widget.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_widget() {
		register_widget( 'Oli_AS_Widget' );
	}

	/**
	 * Adds a settings link on the plugins screen.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, string> $links Existing links.
	 * @return array<int, string>
	 */
	public function action_links( $links ) {
		$url     = admin_url( 'admin.php?page=oli-ajax-search' );
		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'oli-ajax-search' ) . '</a>';
		return $links;
	}
}
