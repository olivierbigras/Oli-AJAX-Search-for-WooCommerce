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
		add_filter( 'walker_nav_menu_start_el', array( $this, 'render_item' ), 10, 4 );
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_item' ) );
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
		<script>
		(function(){
			var button = document.getElementById('submit-oli-as-menu-item');
			if (!button) { return; }
			button.addEventListener('click', function(event){
				event.preventDefault();
				var box = document.getElementById('oli-as-menu-item');
				if (!box) { return; }
				var form = box.closest('form') || document.getElementById('nav-menu-meta');
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = 'menu-item[-1][menu-item-title]';
				input.value = '<?php echo esc_js( __( 'Search', 'oli-ajax-search' ) ); ?>';
				box.appendChild(input);
				var type = document.createElement('input');
				type.type = 'hidden';
				type.name = 'menu-item[-1][menu-item-type]';
				type.value = 'custom';
				box.appendChild(type);
				var url = document.createElement('input');
				url.type = 'hidden';
				url.name = 'menu-item[-1][menu-item-url]';
				url.value = '#oli-ajax-search';
				box.appendChild(url);
				var classes = document.createElement('input');
				classes.type = 'hidden';
				classes.name = 'menu-item[-1][menu-item-classes]';
				classes.value = 'oli-as-menu-item';
				box.appendChild(classes);
				if (window.wpNavMenu && typeof window.wpNavMenu.addItemToMenu === 'function') {
					window.wpNavMenu.addItemToMenu(jQuery(box).find('input').serialize(), 'oli-as-menu-item', function(){});
				}
			});
		})();
		</script>
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
