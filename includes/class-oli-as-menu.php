<?php
/**
 * Navigation menu search item.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds a custom nav menu item that renders the search form.
 *
 * @since 0.1.0
 */
class Oli_AS_Menu {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Menu|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Menu
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers menu hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_head-nav-menus.php', array( $this, 'add_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'render_item' ), 10, 4 );
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_item' ) );
	}

	/**
	 * Enqueues the Menus screen script.
	 *
	 * @since 0.2.1
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_admin( $hook ) {
		if ( 'nav-menus.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'oli-as-nav-menu',
			OLI_AS_URL . 'assets/js/admin-nav-menu.js',
			array( 'jquery', 'nav-menu' ),
			OLI_AS_VERSION,
			true
		);

		wp_localize_script(
			'oli-as-nav-menu',
			'oliAsNavMenu',
			array(
				'title' => __( 'Search', 'oli-ajax-search' ),
			)
		);
	}

	/**
	 * Adds the custom metabox on the menus screen.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function add_metabox() {
		add_meta_box(
			'oli-as-nav-menu',
			__( 'Oli AJAX Search', 'oli-ajax-search' ),
			array( $this, 'metabox' ),
			'nav-menus',
			'side',
			'default'
		);
	}

	/**
	 * Metabox markup.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function metabox() {
		global $nav_menu_selected_id;
		?>
		<div id="oli-as-menu-item">
			<p><?php esc_html_e( 'Add a live search field to this menu.', 'oli-ajax-search' ); ?></p>
			<p class="button-controls wp-clearfix">
				<span class="add-to-menu">
					<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'oli-ajax-search' ); ?>" name="add-oli-as-menu-item" id="submit-oli-as-menu-item" />
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Marks custom search items.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $item Menu item.
	 * @return WP_Post
	 */
	public function setup_item( $item ) {
		if ( isset( $item->url ) && '#oli-ajax-search' === $item->url ) {
			$item->classes[] = 'oli-as-menu-item';
		}
		return $item;
	}

	/**
	 * Replaces the menu link with the search form.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $item_output Item HTML.
	 * @param WP_Post $item        Item.
	 * @param int     $depth       Depth.
	 * @param object  $args        Args.
	 * @return string
	 */
	public function render_item( $item_output, $item, $depth, $args ) {
		unset( $depth, $args );
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		if ( ! in_array( 'oli-as-menu-item', $classes, true ) && '#oli-ajax-search' !== $item->url ) {
			return $item_output;
		}

		$layout = (string) Oli_AS_Settings::get( 'layout', 'classic' );
		return '<div class="oli-as-menu-wrap">' . Oli_AS_Shortcode::instance()->render( array( 'layout' => $layout ) ) . '</div>';
	}
}
