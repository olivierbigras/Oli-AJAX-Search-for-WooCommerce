<?php
/**
 * Settings screen.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI matching the original tab order and labels.
 *
 * @since 0.1.0
 */
class Oli_AS_Admin {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Admin|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers admin hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget' ) );
	}

	/**
	 * Adds the WooCommerce submenu.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Oli AJAX Search', 'oli-ajax-search' ),
			__( 'Oli AJAX Search', 'oli-ajax-search' ),
			'manage_woocommerce',
			'oli-ajax-search',
			array( $this, 'render' )
		);
	}

	/**
	 * Registers the settings API option.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register() {
		register_setting(
			'oli_as_settings_group',
			Oli_AS_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Oli_AS_Settings', 'sanitize' ),
				'default'           => Oli_AS_Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Optional dashboard widget for critical searches.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function dashboard_widget() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( ! Oli_AS_Settings::enabled( 'analytics_enabled' ) || ! Oli_AS_Settings::enabled( 'analytics_widget' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'oli_as_critical_searches',
			__( 'Oli AJAX Search — critical searches', 'oli-ajax-search' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Dashboard widget body.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_dashboard_widget() {
		$rows = Oli_AS_Analytics::instance()->no_result_queries( 8 );
		if ( empty( $rows ) ) {
			echo '<p>' . esc_html__( 'No data yet.', 'oli-ajax-search' ) . '</p>';
			return;
		}

		echo '<ul>';
		foreach ( $rows as $row ) {
			printf(
				'<li><strong>%1$s</strong> — %2$d</li>',
				esc_html( $row->query_text ),
				(int) $row->hits
			);
		}
		echo '</ul>';
	}

	/**
	 * Renders the settings page.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'oli-ajax-search' ) );
		}

		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'starting'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = array(
			'starting'        => __( 'Starting', 'oli-ajax-search' ),
			'search_bar'      => __( 'Search bar', 'oli-ajax-search' ),
			'autocomplete'    => __( 'Autocomplete', 'oli-ajax-search' ),
			'search_config'   => __( 'Search config', 'oli-ajax-search' ),
			'analytics'       => __( 'Analytics', 'oli-ajax-search' ),
			'indexer'         => __( 'Indexer', 'oli-ajax-search' ),
			'troubleshooting' => __( 'Troubleshooting', 'oli-ajax-search' ),
		);
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'starting';
		}

		$settings  = Oli_AS_Settings::get_all();
		$advanced  = Oli_AS_Settings::enabled( 'show_advanced' );
		$form_tabs = array( 'starting', 'search_bar', 'autocomplete', 'search_config', 'analytics', 'indexer' );
		?>
		<div class="wrap oli-as-admin<?php echo esc_attr( $advanced ? ' is-advanced' : '' ); ?>">
			<h1><?php esc_html_e( 'Oli AJAX Search for WooCommerce', 'oli-ajax-search' ); ?></h1>
			<p class="oli-as-advanced-toggle">
				<label>
					<input type="checkbox" id="oli-as-show-advanced" <?php checked( $advanced ); ?> />
					<?php esc_html_e( 'Show advanced settings', 'oli-ajax-search' ); ?>
				</label>
			</p>
			<?php if ( in_array( $tab, array( 'search_bar', 'autocomplete' ), true ) ) : ?>
				<div class="oli-as-preview">
					<p class="description"><?php esc_html_e( 'Live preview', 'oli-ajax-search' ); ?></p>
					<?php echo wp_kses_post( Oli_AS_Shortcode::instance()->render() ); ?>
				</div>
			<?php endif; ?>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a class="nav-tab <?php echo esc_attr( $tab === $key ? 'nav-tab-active' : '' ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=oli-ajax-search&tab=' . $key ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<?php if ( in_array( $tab, $form_tabs, true ) ) : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'oli_as_settings_group' ); ?>
					<?php
					switch ( $tab ) {
						case 'starting':
							$this->render_starting( $settings );
							break;
						case 'search_bar':
							$this->render_search_bar( $settings );
							break;
						case 'autocomplete':
							$this->render_autocomplete( $settings );
							break;
						case 'search_config':
							$this->render_search_config( $settings );
							break;
						case 'analytics':
							$this->render_analytics_settings( $settings );
							break;
						default:
							$this->render_indexer( $settings );
							break;
					}
					if ( 'indexer' !== $tab ) {
						submit_button();
					}
					?>
				</form>
				<?php if ( 'indexer' === $tab ) : ?>
					<?php $this->render_index_status(); ?>
				<?php endif; ?>
				<?php if ( 'analytics' === $tab && Oli_AS_Settings::enabled( 'analytics_enabled' ) ) : ?>
					<?php $this->render_analytics_tables(); ?>
				<?php endif; ?>
			<?php else : ?>
				<?php $this->render_troubleshooting(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Starting tab.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function render_starting( $settings ) {
		$theme = wp_get_theme();
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Replace the search bars', 'oli-ajax-search' ); ?></th>
				<td>
					<p>
						<?php
						printf(
							/* translators: %s: current theme name */
							esc_html__( 'You are using the %s theme. We support common WooCommerce themes so you can easily replace all default search bars.', 'oli-ajax-search' ),
							esc_html( $theme->get( 'Name' ) )
						);
						?>
					</p>
				</td>
			</tr>
			<?php $this->checkbox( 'replace_theme_form', __( 'Search bars', 'oli-ajax-search' ), $settings, __( 'Replace them', 'oli-ajax-search' ) ); ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'How to add search bar in your theme?', 'oli-ajax-search' ); ?></th>
				<td>
					<h4><?php esc_html_e( 'How to add?', 'oli-ajax-search' ); ?></h4>
					<p><?php esc_html_e( 'There are five easy ways to display the search bar in your theme:', 'oli-ajax-search' ); ?></p>
					<ol>
						<li><?php esc_html_e( 'As a menu item — go to the Menu Screen and add the menu item “Oli AJAX Search bar”. Done!', 'oli-ajax-search' ); ?></li>
						<li><?php esc_html_e( 'As a block — insert the Oli AJAX Search block in the editor.', 'oli-ajax-search' ); ?></li>
						<li><?php echo wp_kses_post( __( 'Using a shortcode — <code>[oli_search]</code>', 'oli-ajax-search' ) ); ?></li>
						<li><?php esc_html_e( 'As a widget — go to the Widgets Screen and choose “Oli AJAX Search bar”.', 'oli-ajax-search' ); ?></li>
						<li><?php echo wp_kses_post( __( 'Using PHP — <code>echo do_shortcode(\'[oli_search]\');</code>', 'oli-ajax-search' ) ); ?></li>
					</ol>
					<?php if ( defined( 'ELEMENTOR_VERSION' ) ) : ?>
						<p><?php esc_html_e( 'Elementor is active: you can also add the “Oli AJAX Search” widget.', 'oli-ajax-search' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Search bar tab.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function render_search_bar( $settings ) {
		?>
		<h2 class="title"><?php esc_html_e( 'Basic', 'oli-ajax-search' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr class="oli-as-advanced">
				<th scope="row"><label for="oli_as_min_chars"><?php esc_html_e( 'Minimum characters', 'oli-ajax-search' ); ?></label></th>
				<td>
					<input type="number" min="1" max="10" id="oli_as_min_chars" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[min_chars]" value="<?php echo esc_attr( $settings['min_chars'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Min characters to show autocomplete', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr class="oli-as-advanced">
				<th scope="row"><label for="oli_as_max_form_width"><?php esc_html_e( 'Max form width', 'oli-ajax-search' ); ?></label></th>
				<td>
					<input type="number" min="0" max="2000" id="oli_as_max_form_width" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[max_form_width]" value="<?php echo esc_attr( $settings['max_form_width'] ); ?>" />
					<p class="description"><?php esc_html_e( 'px. To set 100% width leave blank', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<?php $this->checkbox( 'show_submit', __( 'Show submit button', 'oli-ajax-search' ), $settings ); ?>
			<tr>
				<th scope="row"><label for="oli_as_submit_text"><?php esc_html_e( 'Label', 'oli-ajax-search' ); ?></label></th>
				<td>
					<input class="regular-text" type="text" id="oli_as_submit_text" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[submit_text]" value="<?php echo esc_attr( $settings['submit_text'] ); ?>" />
					<p class="description"><?php esc_html_e( 'To display the magnifier icon leave this field empty.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr class="oli-as-advanced">
				<th scope="row"><label for="oli_as_bg_submit_color"><?php esc_html_e( 'Background color', 'oli-ajax-search' ); ?></label></th>
				<td><input class="oli-as-color" type="text" id="oli_as_bg_submit_color" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[bg_submit_color]" value="<?php echo esc_attr( $settings['bg_submit_color'] ); ?>" /></td>
			</tr>
			<tr class="oli-as-advanced">
				<th scope="row"><label for="oli_as_text_submit_color"><?php esc_html_e( 'Text color', 'oli-ajax-search' ); ?> / <?php esc_html_e( 'Search icon color', 'oli-ajax-search' ); ?></label></th>
				<td><input class="oli-as-color" type="text" id="oli_as_text_submit_color" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[text_submit_color]" value="<?php echo esc_attr( $settings['text_submit_color'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_placeholder"><?php esc_html_e( 'Search input placeholder', 'oli-ajax-search' ); ?></label></th>
				<td><input class="regular-text" type="text" id="oli_as_placeholder" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[placeholder]" value="<?php echo esc_attr( $settings['placeholder'] ); ?>" /></td>
			</tr>
		</table>

		<h2 class="title oli-as-advanced"><?php esc_html_e( 'Appearance', 'oli-ajax-search' ); ?></h2>
		<table class="form-table oli-as-advanced" role="presentation">
			<tr>
				<th scope="row"><label for="oli_as_search_style"><?php esc_html_e( 'Style', 'oli-ajax-search' ); ?></label></th>
				<td>
					<select id="oli_as_search_style" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[search_style]">
						<option value="oval" <?php selected( $settings['search_style'], 'oval' ); ?>><?php esc_html_e( 'Oval', 'oli-ajax-search' ); ?></option>
						<option value="oval-compact" <?php selected( $settings['search_style'], 'oval-compact' ); ?>><?php esc_html_e( 'Oval (compact version)', 'oli-ajax-search' ); ?></option>
						<option value="rectangle" <?php selected( $settings['search_style'], 'rectangle' ); ?>><?php esc_html_e( 'Rectangle', 'oli-ajax-search' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Oli provides two different styles of search bars: Rectangle and Oval. Rectangle has a rectangular shape while Oval is a bean-shaped bar with curvy and smooth edges.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_layout"><?php esc_html_e( 'Layout', 'oli-ajax-search' ); ?></label></th>
				<td>
					<select id="oli_as_layout" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[layout]">
						<option value="classic" <?php selected( $settings['layout'], 'classic' ); ?>><?php esc_html_e( 'Search bar', 'oli-ajax-search' ); ?></option>
						<option value="icon" <?php selected( $settings['layout'], 'icon' ); ?>><?php esc_html_e( 'Search icon', 'oli-ajax-search' ); ?></option>
						<option value="icon-flexible" <?php selected( $settings['layout'], 'icon-flexible' ); ?>><?php esc_html_e( 'Icon on mobile, search bar on desktop', 'oli-ajax-search' ); ?></option>
						<option value="icon-flexible-inv" <?php selected( $settings['layout'], 'icon-flexible-inv' ); ?>><?php esc_html_e( 'Icon on desktop, search bar on mobile', 'oli-ajax-search' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Oli AJAX Search might be displayed as a search bar, icon, or mixed of them.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_mobile_breakpoint"><?php esc_html_e( 'Breakpoint', 'oli-ajax-search' ); ?></label></th>
				<td>
					<input type="number" min="320" max="1600" id="oli_as_mobile_breakpoint" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[mobile_breakpoint]" value="<?php echo esc_attr( $settings['mobile_breakpoint'] ); ?>" />
					<p class="description"><?php esc_html_e( 'px', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_search_icon_color"><?php esc_html_e( 'Search icon color', 'oli-ajax-search' ); ?></label></th>
				<td><input class="oli-as-color" type="text" id="oli_as_search_icon_color" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[search_icon_color]" value="<?php echo esc_attr( $settings['search_icon_color'] ); ?>" /></td>
			</tr>
			<?php $this->checkbox( 'mobile_overlay', __( 'Overlay on mobile', 'oli-ajax-search' ), $settings, __( 'The search will open as an overlay on mobile', 'oli-ajax-search' ), __( 'Covers anything that is displayed on the screen and simplifies the view to search bar + results.', 'oli-ajax-search' ) ); ?>
			<tr>
				<th scope="row"><label for="oli_as_overlay_breakpoint"><?php esc_html_e( 'Breakpoint', 'oli-ajax-search' ); ?></label></th>
				<td>
					<input type="number" min="320" max="1600" id="oli_as_overlay_breakpoint" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[mobile_overlay_breakpoint]" value="<?php echo esc_attr( $settings['mobile_overlay_breakpoint'] ); ?>" />
					<p class="description"><?php esc_html_e( 'px', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<?php $this->checkbox( 'darken_background', __( 'Darkened background', 'oli-ajax-search' ), $settings, '', __( 'Darkening the page background while autocomplete is active gives it stronger emphasis, minimizing elements (e.g., ads, carousels, and other page content) that could distract users from considering autocomplete suggestions.', 'oli-ajax-search' ) ); ?>
		</table>

		<h2 class="title oli-as-advanced"><?php esc_html_e( 'Other colors', 'oli-ajax-search' ); ?></h2>
		<table class="form-table oli-as-advanced" role="presentation">
			<?php $this->color_row( 'bg_input_underlay_color', __( 'Search bar underlay', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->color_row( 'color_bg', __( 'Search input background', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->color_row( 'color_text', __( 'Search input text', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->color_row( 'color_border', __( 'Search input border', 'oli-ajax-search' ), $settings ); ?>
		</table>

		<h2 class="title oli-as-advanced"><?php esc_html_e( 'Preloader', 'oli-ajax-search' ); ?></h2>
		<table class="form-table oli-as-advanced" role="presentation">
			<?php $this->checkbox( 'show_preloader', __( 'Show preloader', 'oli-ajax-search' ), $settings ); ?>
			<tr>
				<th scope="row"><label for="oli_as_preloader_url"><?php esc_html_e( 'Upload preloader image', 'oli-ajax-search' ); ?></label></th>
				<td><input class="regular-text" type="url" id="oli_as_preloader_url" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[preloader_url]" value="<?php echo esc_attr( $settings['preloader_url'] ); ?>" /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Autocomplete tab.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function render_autocomplete( $settings ) {
		?>
		<h2 class="title"><?php esc_html_e( 'Basic', 'oli-ajax-search' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="oli_as_suggestions_limit"><?php esc_html_e( 'Limit', 'oli-ajax-search' ); ?></label></th>
				<td>
					<input type="number" min="1" max="30" id="oli_as_suggestions_limit" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[suggestions_limit]" value="<?php echo esc_attr( $settings['suggestions_limit'] ); ?>" />
					<p class="description"><?php esc_html_e( 'maximum number of suggestions', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_no_results"><?php echo esc_html_x( 'No results label', 'admin', 'oli-ajax-search' ); ?></label></th>
				<td>
					<textarea class="large-text" rows="2" id="oli_as_no_results" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[no_results_text]"><?php echo esc_textarea( $settings['no_results_text'] ); ?></textarea>
					<p class="description"><?php echo wp_kses_post( __( 'The following HTML tags are allowed:<br /> <code>h1-h6,p,ul,ol,li,b,em,br,div,span,a</code>.', 'oli-ajax-search' ) ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Products', 'oli-ajax-search' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php $this->checkbox( 'show_image', __( 'Show product image', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->checkbox( 'show_price', __( 'Show price', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->checkbox( 'show_excerpt', __( 'Show product description', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'show_sku', __( 'Show SKU', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->checkbox( 'show_stock', __( 'Show stock status', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'show_sale_badge', __( 'Show sale badge', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'show_featured_badge', __( 'Show featured badge', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<tr>
				<th scope="row"><label for="oli_as_see_all"><?php esc_html_e( 'More results label', 'oli-ajax-search' ); ?></label></th>
				<td><input class="regular-text" type="text" id="oli_as_see_all" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[see_all_text]" value="<?php echo esc_attr( $settings['see_all_text'] ); ?>" /></td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Non-products in autocomplete', 'oli-ajax-search' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php $this->checkbox( 'show_categories', __( 'Show categories', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->checkbox( 'show_category_images', __( 'show images', 'oli-ajax-search' ), $settings, __( 'show images', 'oli-ajax-search' ), '', true ); ?>
			<?php $this->checkbox( 'show_tags', __( 'Show tags', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'show_brands', __( 'Show brands', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'show_posts', __( 'Show posts', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'show_pages', __( 'Show pages', 'oli-ajax-search' ), $settings, '', '', true ); ?>
		</table>

		<h2 class="title"><?php esc_html_e( 'Extra views', 'oli-ajax-search' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php $this->checkbox( 'show_details_panel', __( 'Show Details Panel', 'oli-ajax-search' ), $settings, '', __( 'The Details Panel is an additional container for extended information. The details change dynamically when the cursor hovers over one of the suggestions.', 'oli-ajax-search' ) ); ?>
			<?php $this->checkbox( 'show_add_to_cart', __( 'Show add to cart in the details panel', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'show_recent', __( 'User search history', 'oli-ajax-search' ), $settings, '', __( 'The current search history is presented when the user clicked/taped on the search bar, but hasn\'t yet typed the query. The history includes the last searched products and phrases.', 'oli-ajax-search' ), true ); ?>
		</table>

		<h2 class="title oli-as-advanced"><?php esc_html_e( 'Suggestions colors', 'oli-ajax-search' ); ?></h2>
		<table class="form-table oli-as-advanced" role="presentation">
			<?php $this->color_row( 'sug_bg_color', __( 'Suggestion background', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->color_row( 'sug_hover_color', __( 'Suggestion selected', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->color_row( 'sug_text_color', __( 'Text color', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->color_row( 'sug_highlight_color', __( 'Highlight color', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->color_row( 'sug_border_color', __( 'Border color', 'oli-ajax-search' ), $settings ); ?>
		</table>
		<?php
	}

	/**
	 * Search config tab.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function render_search_config( $settings ) {
		?>
		<h2 class="title"><?php esc_html_e( 'Products search scope', 'oli-ajax-search' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php $this->checkbox( 'search_content', __( 'Search in description', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->checkbox( 'search_excerpt', __( 'Search in short description', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->checkbox( 'search_sku', __( 'Search in SKU', 'oli-ajax-search' ), $settings, __( 'searching also in variable products SKU', 'oli-ajax-search' ), __( 'In the autocomplete results, a variation may appear when there\'s an exact SKU match.', 'oli-ajax-search' ) ); ?>
			<?php $this->checkbox( 'search_gtin', __( 'Search in GUID', 'oli-ajax-search' ), $settings, __( 'searching also in variable products GUID', 'oli-ajax-search' ), __( 'Make your products searchable by their Global Unique Identifier (GTIN, UPC, EAN or ISBN). In the autocomplete results, a variation may appear when there\'s an exact GUID match.', 'oli-ajax-search' ), true ); ?>
			<?php $this->checkbox( 'search_attributes', __( 'Search in attributes', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->checkbox( 'search_in_categories', __( 'Search in categories', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'search_in_tags', __( 'Search in tags', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'search_taxonomies', __( 'Search in taxonomies', 'oli-ajax-search' ), $settings, '', '', true ); ?>
			<?php $this->checkbox( 'search_custom_fields', __( 'Search in custom fields', 'oli-ajax-search' ), $settings, __( 'select the custom fields you want to add to the search scope', 'oli-ajax-search' ), __( 'Make your custom fields searchable from our search bar.', 'oli-ajax-search' ) ); ?>
			<tr>
				<th scope="row"><label for="oli_as_custom_fields"><?php esc_html_e( 'Custom field keys', 'oli-ajax-search' ); ?></label></th>
				<td><textarea class="large-text" rows="3" id="oli_as_custom_fields" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[custom_fields]"><?php echo esc_textarea( $settings['custom_fields'] ); ?></textarea></td>
			</tr>
			<?php $this->checkbox( 'hide_out_of_stock', __( 'Exclude “out of stock” products', 'oli-ajax-search' ), $settings ); ?>
		</table>

		<h2 class="title"><?php esc_html_e( 'Fuzziness', 'oli-ajax-search' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="oli_as_fuzzy_level"><?php esc_html_e( 'Fuzzy matching', 'oli-ajax-search' ); ?></label></th>
				<td>
					<select id="oli_as_fuzzy_level" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[fuzzy_level]">
						<option value="off" <?php selected( $settings['fuzzy_level'], 'off' ); ?>><?php esc_html_e( '-- Disabled', 'oli-ajax-search' ); ?></option>
						<option value="soft" <?php selected( $settings['fuzzy_level'], 'soft' ); ?>><?php esc_html_e( 'Soft', 'oli-ajax-search' ); ?></option>
						<option value="normal" <?php selected( $settings['fuzzy_level'], 'normal' ); ?>><?php esc_html_e( 'Normal', 'oli-ajax-search' ); ?></option>
						<option value="hard" <?php selected( $settings['fuzzy_level'], 'hard' ); ?>><?php esc_html_e( 'Hard', 'oli-ajax-search' ); ?></option>
					</select>
					<p class="description">
						<strong><?php esc_html_e( 'Increases sales conversions.', 'oli-ajax-search' ); ?></strong>
						<?php echo wp_kses_post( __( 'Returns suggestions based on likely relevance, even though a search keyword may not exactly match. E.g if you type “ipho<b>m</b>e” you get the same results as for “iphone”.', 'oli-ajax-search' ) ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2 class="title oli-as-advanced"><?php esc_html_e( 'Synonyms', 'oli-ajax-search' ); ?></h2>
		<table class="form-table oli-as-advanced" role="presentation">
			<tr>
				<th scope="row"><label for="oli_as_synonyms"><?php esc_html_e( 'Synonyms', 'oli-ajax-search' ); ?></label></th>
				<td>
					<textarea class="large-text" rows="5" id="oli_as_synonyms" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[synonyms]"><?php echo esc_textarea( $settings['synonyms'] ); ?></textarea>
					<p class="description"><?php echo wp_kses_post( __( 'Synonyms should be separated by a comma. Each new synonyms group is entered in a new line. You can use a phrase instead of a single word. <br /> <br />Sample list:<br /> <br /><span>sofa, couch, davenport, divan, settee<br />big, grand, great, large, outsize</span>', 'oli-ajax-search' ) ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_stopwords"><?php esc_html_e( 'Stopwords', 'oli-ajax-search' ); ?></label></th>
				<td>
					<textarea class="large-text" rows="3" id="oli_as_stopwords" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[stopwords]"><?php echo esc_textarea( $settings['stopwords'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Optional. Words removed from queries and the index. Comma-separated.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title oli-as-advanced"><?php esc_html_e( 'Exclude/include products', 'oli-ajax-search' ); ?></h2>
		<table class="form-table oli-as-advanced" role="presentation">
			<tr>
				<th scope="row"><label for="oli_as_filter_mode"><?php esc_html_e( 'Filtering mode', 'oli-ajax-search' ); ?></label></th>
				<td>
					<select id="oli_as_filter_mode" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[filter_mode]">
						<option value="exclude" <?php selected( $settings['filter_mode'], 'exclude' ); ?>><?php esc_html_e( 'Exclude', 'oli-ajax-search' ); ?></option>
						<option value="include" <?php selected( $settings['filter_mode'], 'include' ); ?>><?php esc_html_e( 'Include', 'oli-ajax-search' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Exclude the product group from the search results or allow search only among the indicated product group.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_exclude_ids"><?php esc_html_e( 'Filters', 'oli-ajax-search' ); ?></label></th>
				<td>
					<p class="description"><?php esc_html_e( 'Filters that specify the product group and taxonomy that will be affected by the above mode', 'oli-ajax-search' ); ?></p>
					<p>
						<label><?php esc_html_e( 'Product IDs', 'oli-ajax-search' ); ?>
							<input class="regular-text" type="text" id="oli_as_exclude_ids" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[exclude_ids]" value="<?php echo esc_attr( $settings['exclude_ids'] ); ?>" />
						</label>
					</p>
					<p>
						<label><?php esc_html_e( 'Category IDs', 'oli-ajax-search' ); ?>
							<input class="regular-text" type="text" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[exclude_cat_ids]" value="<?php echo esc_attr( $settings['exclude_cat_ids'] ); ?>" />
						</label>
					</p>
					<?php $this->checkbox( 'filter_on_sale', __( 'On sale', 'oli-ajax-search' ), $settings ); ?>
					<?php $this->checkbox( 'filter_featured', __( 'Featured', 'oli-ajax-search' ), $settings ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Relevance weights', 'oli-ajax-search' ); ?></th>
				<td>
					<?php
					$weights = array(
						'weight_title'     => __( 'Title', 'oli-ajax-search' ),
						'weight_sku'       => __( 'SKU', 'oli-ajax-search' ),
						'weight_excerpt'   => __( 'Excerpt', 'oli-ajax-search' ),
						'weight_content'   => __( 'Content', 'oli-ajax-search' ),
						'weight_attribute' => __( 'Attributes', 'oli-ajax-search' ),
						'weight_meta'      => __( 'Custom fields', 'oli-ajax-search' ),
						'weight_taxonomy'  => __( 'Taxonomies', 'oli-ajax-search' ),
					);
					foreach ( $weights as $key => $label ) {
						printf(
							'<p><label>%1$s <input type="number" min="0" max="50" name="%2$s[%3$s]" value="%4$s" /></label></p>',
							esc_html( $label ),
							esc_attr( Oli_AS_Settings::OPTION ),
							esc_attr( $key ),
							esc_attr( $settings[ $key ] )
						);
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_brands"><?php esc_html_e( 'Brand taxonomies', 'oli-ajax-search' ); ?></label></th>
				<td><input class="regular-text" type="text" id="oli_as_brands" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[brand_taxonomies]" value="<?php echo esc_attr( $settings['brand_taxonomies'] ); ?>" /></td>
			</tr>
			<?php $this->checkbox( 'use_on_results_page', __( 'Use the same relevance on the WooCommerce results page', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->checkbox( 'index_posts', __( 'Index posts', 'oli-ajax-search' ), $settings ); ?>
			<?php $this->checkbox( 'index_pages', __( 'Index pages', 'oli-ajax-search' ), $settings ); ?>
		</table>
		<?php
	}

	/**
	 * Analytics settings.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function render_analytics_settings( $settings ) {
		?>
		<h2 class="title"><?php esc_html_e( 'Search Analytics', 'oli-ajax-search' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php $this->checkbox( 'analytics_enabled', __( 'Enable search analytics', 'oli-ajax-search' ), $settings, '', __( 'Search analytics system helps to eliminate search phrases that don’t return any results.', 'oli-ajax-search' ) ); ?>
			<?php $this->checkbox( 'analytics_widget', __( 'Show widget with critical searches in Dashboard', 'oli-ajax-search' ), $settings ); ?>
		</table>
		<?php
	}

	/**
	 * Indexer settings (scheduler).
	 *
	 * @since 0.2.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function render_indexer( $settings ) {
		?>
		<h2 class="title"><?php esc_html_e( 'Speed up search!', 'oli-ajax-search' ); ?></h2>
		<h2 class="title oli-as-advanced"><?php esc_html_e( 'Scheduling indexing', 'oli-ajax-search' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php $this->checkbox( 'indexer_schedule', __( 'Enable Scheduler', 'oli-ajax-search' ), $settings, '', __( 'In most cases, you don\'t need to use the scheduler because the search index updates when you edit products. If you use import tools or custom code to refresh prices or bulk add/edit products, the indexing scheduler will be helpful.', 'oli-ajax-search' ), true ); ?>
			<tr class="oli-as-advanced">
				<th scope="row"><label for="oli_as_schedule_interval"><?php esc_html_e( 'Interval', 'oli-ajax-search' ); ?></label></th>
				<td>
					<select id="oli_as_schedule_interval" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[indexer_schedule_interval]">
						<option value="daily" <?php selected( $settings['indexer_schedule_interval'], 'daily' ); ?>><?php esc_html_e( 'daily', 'oli-ajax-search' ); ?></option>
						<option value="weekly" <?php selected( $settings['indexer_schedule_interval'], 'weekly' ); ?>><?php esc_html_e( 'weekly', 'oli-ajax-search' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="oli-as-advanced">
				<th scope="row"><label for="oli_as_schedule_time"><?php esc_html_e( 'Schedule time', 'oli-ajax-search' ); ?></label></th>
				<td>
					<select id="oli_as_schedule_time" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[indexer_schedule_start_time]">
						<?php foreach ( Oli_AS_Settings::hours() as $hour => $label ) : ?>
							<option value="<?php echo esc_attr( (string) $hour ); ?>" <?php selected( (int) $settings['indexer_schedule_start_time'], (int) $hour ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
		submit_button();
	}

	/**
	 * Index status UI.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	protected function render_index_status() {
		$status = Oli_AS_Indexer::instance()->get_status();
		$count  = Oli_AS_Indexer::instance()->indexed_count();
		$langs  = Oli_AS_Language::instance()->languages();
		?>
		<h2><?php esc_html_e( 'Index status', 'oli-ajax-search' ); ?></h2>
		<div class="oli-as-index-panel">
			<p>
				<?php
				printf(
					/* translators: 1: document count, 2: language list */
					esc_html__( 'Indexed 100%, %1$d products. Languages: %2$s.', 'oli-ajax-search' ),
					(int) $count,
					esc_html( implode( ', ', $langs ) )
				);
				?>
			</p>
			<p><?php esc_html_e( 'All product changes will be re-indexed automatically.', 'oli-ajax-search' ); ?></p>
			<p>
				<strong><?php esc_html_e( 'Status:', 'oli-ajax-search' ); ?></strong>
				<span id="oli-as-index-state"><?php echo esc_html( (string) $status['state'] ); ?></span>
				— <span id="oli-as-index-message"><?php echo esc_html( (string) $status['message'] ); ?></span>
			</p>
			<p>
				<progress id="oli-as-index-progress" max="<?php echo esc_attr( max( 1, (int) $status['total'] ) ); ?>" value="<?php echo esc_attr( (int) $status['processed'] ); ?>"></progress>
				<span id="oli-as-index-counts"><?php echo esc_html( (int) $status['processed'] . ' / ' . (int) $status['total'] ); ?></span>
			</p>
			<p>
				<button type="button" class="button button-primary" id="oli-as-rebuild"><?php esc_html_e( 'Rebuild index', 'oli-ajax-search' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Analytics tables and CSV.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	protected function render_analytics_tables() {
		$top  = Oli_AS_Analytics::instance()->top_queries( 25 );
		$none = Oli_AS_Analytics::instance()->no_result_queries( 25 );
		?>
		<p><?php esc_html_e( 'Queries are stored on this site only. Nothing is sent to remote services.', 'oli-ajax-search' ); ?></p>
		<p>
			<button type="button" class="button" id="oli-as-clear-analytics"><?php esc_html_e( 'Clear analytics', 'oli-ajax-search' ); ?></button>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=oli_as_export_analytics' ), 'oli_as_export_analytics' ) ); ?>"><?php esc_html_e( 'Export CSV', 'oli-ajax-search' ); ?></a>
		</p>
		<h2><?php esc_html_e( 'Top queries', 'oli-ajax-search' ); ?></h2>
		<?php $this->analytics_table( $top ); ?>
		<h2><?php esc_html_e( 'Queries with no results', 'oli-ajax-search' ); ?></h2>
		<?php $this->analytics_table( $none ); ?>
		<?php
	}

	/**
	 * Troubleshooting tab.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	protected function render_troubleshooting() {
		$checks = Oli_AS_Troubleshooting::instance()->run();
		echo '<h2>' . esc_html__( 'Troubleshooting', 'oli-ajax-search' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Check', 'oli-ajax-search' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'oli-ajax-search' ) . '</th>';
		echo '<th>' . esc_html__( 'Details', 'oli-ajax-search' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $checks as $check ) {
			echo '<tr>';
			echo '<td>' . esc_html( $check['label'] ) . '</td>';
			echo '<td>' . esc_html( $check['ok'] ? __( 'OK', 'oli-ajax-search' ) : __( 'Attention', 'oli-ajax-search' ) ) . '</td>';
			echo '<td>' . esc_html( $check['detail'] ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Renders an analytics table.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, object> $rows Rows.
	 * @return void
	 */
	protected function analytics_table( $rows ) {
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Query', 'oli-ajax-search' ) . '</th>';
		echo '<th>' . esc_html__( 'Language', 'oli-ajax-search' ) . '</th>';
		echo '<th>' . esc_html__( 'Hits', 'oli-ajax-search' ) . '</th>';
		echo '<th>' . esc_html__( 'Last results', 'oli-ajax-search' ) . '</th>';
		echo '<th>' . esc_html__( 'Last hit', 'oli-ajax-search' ) . '</th>';
		echo '</tr></thead><tbody>';
		if ( empty( $rows ) ) {
			echo '<tr><td colspan="5">' . esc_html__( 'No data yet.', 'oli-ajax-search' ) . '</td></tr>';
		} else {
			foreach ( $rows as $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $row->query_text ) . '</td>';
				echo '<td>' . esc_html( $row->language ) . '</td>';
				echo '<td>' . esc_html( (string) $row->hits ) . '</td>';
				echo '<td>' . esc_html( (string) $row->result_count ) . '</td>';
				echo '<td>' . esc_html( $row->last_hit ) . '</td>';
				echo '</tr>';
			}
		}
		echo '</tbody></table>';
	}

	/**
	 * Color field.
	 *
	 * @since 0.2.0
	 *
	 * @param string               $key      Setting key.
	 * @param string               $label    Label.
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function color_row( $key, $label, $settings ) {
		printf(
			'<tr><th scope="row"><label for="oli_as_%1$s">%2$s</label></th><td><input class="oli-as-color" type="text" id="oli_as_%1$s" name="%3$s[%1$s]" value="%4$s" /></td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( Oli_AS_Settings::OPTION ),
			esc_attr( (string) $settings[ $key ] )
		);
	}

	/**
	 * Checkbox helper.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $key         Setting key.
	 * @param string               $label       Label.
	 * @param array<string, mixed> $settings    Settings.
	 * @param string               $desc        Description.
	 * @param string               $help        Help text.
	 * @param bool                 $advanced    Advanced row.
	 * @return void
	 */
	protected function checkbox( $key, $label, $settings, $desc = '', $help = '', $advanced = false ) {
		$checked = ! empty( $settings[ $key ] ) && '0' !== (string) $settings[ $key ];
		echo '<tr' . ( $advanced ? ' class="' . esc_attr( 'oli-as-advanced' ) . '"' : '' ) . '><th scope="row">' . esc_html( $label ) . '</th><td>';
		echo '<input type="hidden" name="' . esc_attr( Oli_AS_Settings::OPTION ) . '[_seen][' . esc_attr( $key ) . ']" value="1" />';
		echo '<label><input type="checkbox" name="' . esc_attr( Oli_AS_Settings::OPTION ) . '[' . esc_attr( $key ) . ']" value="1" ' . checked( $checked, true, false ) . ' /> ' . esc_html( $desc ? $desc : $label ) . '</label>';
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	}
}
