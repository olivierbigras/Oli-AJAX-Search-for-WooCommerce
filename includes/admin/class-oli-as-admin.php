<?php
/**
 * Settings screen.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI for settings, index, and analytics.
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

		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = array(
			'general'    => __( 'General', 'oli-ajax-search' ),
			'search'     => __( 'Search', 'oli-ajax-search' ),
			'appearance' => __( 'Appearance', 'oli-ajax-search' ),
			'index'      => __( 'Index', 'oli-ajax-search' ),
			'analytics'  => __( 'Analytics', 'oli-ajax-search' ),
		);
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'general';
		}

		$settings = Oli_AS_Settings::get_all();
		?>
		<div class="wrap oli-as-admin">
			<h1><?php esc_html_e( 'Oli AJAX Search for WooCommerce', 'oli-ajax-search' ); ?></h1>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=oli-ajax-search&tab=' . $key ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<?php if ( in_array( $tab, array( 'general', 'search', 'appearance' ), true ) ) : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'oli_as_settings_group' ); ?>
					<?php
					if ( 'general' === $tab ) {
						$this->render_general( $settings );
					} elseif ( 'search' === $tab ) {
						$this->render_search( $settings );
					} else {
						$this->render_appearance( $settings );
					}
					submit_button();
					?>
				</form>
			<?php elseif ( 'index' === $tab ) : ?>
				<?php $this->render_index(); ?>
			<?php else : ?>
				<?php $this->render_analytics(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * General settings fields.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function render_general( $settings ) {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="oli_as_placeholder"><?php esc_html_e( 'Placeholder', 'oli-ajax-search' ); ?></label></th>
				<td><input class="regular-text" type="text" id="oli_as_placeholder" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[placeholder]" value="<?php echo esc_attr( $settings['placeholder'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_min_chars"><?php esc_html_e( 'Minimum characters', 'oli-ajax-search' ); ?></label></th>
				<td><input type="number" min="1" max="10" id="oli_as_min_chars" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[min_chars]" value="<?php echo esc_attr( $settings['min_chars'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_debounce"><?php esc_html_e( 'Debounce (ms)', 'oli-ajax-search' ); ?></label></th>
				<td><input type="number" min="50" max="2000" id="oli_as_debounce" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[debounce_ms]" value="<?php echo esc_attr( $settings['debounce_ms'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Maximum results per group', 'oli-ajax-search' ); ?></th>
				<td>
					<?php
					$limits = array(
						'max_products'   => __( 'Products', 'oli-ajax-search' ),
						'max_categories' => __( 'Categories', 'oli-ajax-search' ),
						'max_tags'       => __( 'Tags', 'oli-ajax-search' ),
						'max_brands'     => __( 'Brands', 'oli-ajax-search' ),
						'max_attributes' => __( 'Attributes', 'oli-ajax-search' ),
						'max_posts'      => __( 'Posts', 'oli-ajax-search' ),
						'max_pages'      => __( 'Pages', 'oli-ajax-search' ),
					);
					foreach ( $limits as $key => $label ) {
						printf(
							'<p><label>%1$s <input type="number" min="0" max="30" name="%2$s[%3$s]" value="%4$s" /></label></p>',
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
				<th scope="row"><label for="oli_as_layout"><?php esc_html_e( 'Layout', 'oli-ajax-search' ); ?></label></th>
				<td>
					<select id="oli_as_layout" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[layout]">
						<option value="classic" <?php selected( $settings['layout'], 'classic' ); ?>><?php esc_html_e( 'Classic bar', 'oli-ajax-search' ); ?></option>
						<option value="icon" <?php selected( $settings['layout'], 'icon' ); ?>><?php esc_html_e( 'Icon that expands', 'oli-ajax-search' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_mobile_breakpoint"><?php esc_html_e( 'Mobile breakpoint (px)', 'oli-ajax-search' ); ?></label></th>
				<td><input type="number" min="320" max="1600" id="oli_as_mobile_breakpoint" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[mobile_breakpoint]" value="<?php echo esc_attr( $settings['mobile_breakpoint'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_max_form_width"><?php esc_html_e( 'Maximum form width (px)', 'oli-ajax-search' ); ?></label></th>
				<td>
					<input type="number" min="0" max="1600" id="oli_as_max_form_width" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[max_form_width]" value="<?php echo esc_attr( $settings['max_form_width'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Use 0 for the default width.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_submit_text"><?php esc_html_e( 'Submit button text', 'oli-ajax-search' ); ?></label></th>
				<td>
					<input class="regular-text" type="text" id="oli_as_submit_text" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[submit_text]" value="<?php echo esc_attr( $settings['submit_text'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Leave empty to show a search icon only.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_no_results"><?php esc_html_e( 'No results text', 'oli-ajax-search' ); ?></label></th>
				<td><input class="regular-text" type="text" id="oli_as_no_results" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[no_results_text]" value="<?php echo esc_attr( $settings['no_results_text'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_see_all"><?php esc_html_e( 'See all results text', 'oli-ajax-search' ); ?></label></th>
				<td><input class="regular-text" type="text" id="oli_as_see_all" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[see_all_text]" value="<?php echo esc_attr( $settings['see_all_text'] ); ?>" /></td>
			</tr>
			<?php
			$this->checkbox( 'mobile_overlay', __( 'Use a full-screen overlay on mobile', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'darken_background', __( 'Darken the page behind suggestions', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_submit', __( 'Show a submit button', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_preloader', __( 'Show a loading indicator while fetching suggestions', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'replace_theme_form', __( 'Replace theme and WooCommerce search forms', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_image', __( 'Show product image', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_price', __( 'Show price', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_sku', __( 'Show SKU', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_stock', __( 'Show stock status', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_excerpt', __( 'Show short description', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_details_panel', __( 'Show details panel on hover', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_add_to_cart', __( 'Show add to cart in the details panel', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'show_recent', __( 'Show recent searches', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'use_on_results_page', __( 'Use the same relevance on the WooCommerce results page', 'oli-ajax-search' ), $settings );
			?>
		</table>
		<?php
	}

	/**
	 * Search settings fields.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function render_search( $settings ) {
		?>
		<table class="form-table" role="presentation">
			<?php
			$this->checkbox( 'search_title', __( 'Search titles', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'search_content', __( 'Search content', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'search_excerpt', __( 'Search excerpts', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'search_sku', __( 'Search SKUs', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'search_variation_sku', __( 'Include variation SKUs', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'search_attributes', __( 'Search attributes', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'search_taxonomies', __( 'Search taxonomies', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'search_custom_fields', __( 'Search custom fields', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'search_gtin', __( 'Search GTIN / UPC / EAN / ISBN', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'hide_out_of_stock', __( 'Hide out-of-stock products', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'index_posts', __( 'Index posts', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'index_pages', __( 'Index pages', 'oli-ajax-search' ), $settings );
			$this->checkbox( 'scheduled_rebuild', __( 'Rebuild the index daily during maintenance', 'oli-ajax-search' ), $settings );
			?>
			<tr>
				<th scope="row"><label for="oli_as_fuzzy_level"><?php esc_html_e( 'Typo tolerance', 'oli-ajax-search' ); ?></label></th>
				<td>
					<select id="oli_as_fuzzy_level" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[fuzzy_level]">
						<option value="off" <?php selected( $settings['fuzzy_level'], 'off' ); ?>><?php esc_html_e( 'Off', 'oli-ajax-search' ); ?></option>
						<option value="soft" <?php selected( $settings['fuzzy_level'], 'soft' ); ?>><?php esc_html_e( 'Soft', 'oli-ajax-search' ); ?></option>
						<option value="normal" <?php selected( $settings['fuzzy_level'], 'normal' ); ?>><?php esc_html_e( 'Normal', 'oli-ajax-search' ); ?></option>
						<option value="hard" <?php selected( $settings['fuzzy_level'], 'hard' ); ?>><?php esc_html_e( 'Hard', 'oli-ajax-search' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Soft needs longer words and one typo. Hard allows shorter words and more typos.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_filter_mode"><?php esc_html_e( 'Product filter mode', 'oli-ajax-search' ); ?></label></th>
				<td>
					<select id="oli_as_filter_mode" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[filter_mode]">
						<option value="exclude" <?php selected( $settings['filter_mode'], 'exclude' ); ?>><?php esc_html_e( 'Exclude the IDs below', 'oli-ajax-search' ); ?></option>
						<option value="include" <?php selected( $settings['filter_mode'], 'include' ); ?>><?php esc_html_e( 'Include only the IDs below', 'oli-ajax-search' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_custom_fields"><?php esc_html_e( 'Custom field keys', 'oli-ajax-search' ); ?></label></th>
				<td>
					<textarea class="large-text" rows="3" id="oli_as_custom_fields" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[custom_fields]"><?php echo esc_textarea( $settings['custom_fields'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One meta key per line, or comma-separated.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_synonyms"><?php esc_html_e( 'Synonyms', 'oli-ajax-search' ); ?></label></th>
				<td>
					<textarea class="large-text" rows="5" id="oli_as_synonyms" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[synonyms]"><?php echo esc_textarea( $settings['synonyms'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One group per line, comma-separated. Example: hoodie, sweatshirt, hoody', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_stopwords"><?php esc_html_e( 'Stopwords', 'oli-ajax-search' ); ?></label></th>
				<td>
					<textarea class="large-text" rows="3" id="oli_as_stopwords" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[stopwords]"><?php echo esc_textarea( $settings['stopwords'] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_exclude_ids"><?php esc_html_e( 'Product IDs', 'oli-ajax-search' ); ?></label></th>
				<td>
					<input class="regular-text" type="text" id="oli_as_exclude_ids" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[exclude_ids]" value="<?php echo esc_attr( $settings['exclude_ids'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Comma-separated. Used as include or exclude depending on the filter mode.', 'oli-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="oli_as_exclude_cats"><?php esc_html_e( 'Category IDs', 'oli-ajax-search' ); ?></label></th>
				<td><input class="regular-text" type="text" id="oli_as_exclude_cats" name="<?php echo esc_attr( Oli_AS_Settings::OPTION ); ?>[exclude_cat_ids]" value="<?php echo esc_attr( $settings['exclude_cat_ids'] ); ?>" /></td>
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
		</table>
		<?php
	}

	/**
	 * Appearance fields.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function render_appearance( $settings ) {
		$colors = array(
			'color_bg'        => __( 'Background', 'oli-ajax-search' ),
			'color_text'      => __( 'Text', 'oli-ajax-search' ),
			'color_border'    => __( 'Border', 'oli-ajax-search' ),
			'color_accent'    => __( 'Accent', 'oli-ajax-search' ),
			'color_hover'     => __( 'Suggestion hover', 'oli-ajax-search' ),
			'color_price'     => __( 'Price', 'oli-ajax-search' ),
			'color_highlight' => __( 'Matched text highlight', 'oli-ajax-search' ),
		);
		echo '<table class="form-table" role="presentation">';
		foreach ( $colors as $key => $label ) {
			printf(
				'<tr><th scope="row"><label for="oli_as_%1$s">%2$s</label></th><td><input class="oli-as-color" type="text" id="oli_as_%1$s" name="%3$s[%1$s]" value="%4$s" /></td></tr>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( Oli_AS_Settings::OPTION ),
				esc_attr( $settings[ $key ] )
			);
		}
		echo '</table>';
	}

	/**
	 * Index management.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	protected function render_index() {
		$status = Oli_AS_Indexer::instance()->get_status();
		$count  = Oli_AS_Indexer::instance()->indexed_count();
		$langs  = Oli_AS_Language::instance()->languages();
		?>
		<div class="oli-as-index-panel">
			<p>
				<?php
				printf(
					/* translators: 1: document count, 2: language list */
					esc_html__( 'Indexed documents: %1$d. Languages: %2$s.', 'oli-ajax-search' ),
					(int) $count,
					esc_html( implode( ', ', $langs ) )
				);
				?>
			</p>
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
			<p class="description"><?php esc_html_e( 'The index is built in background batches with Action Scheduler when WooCommerce provides it, otherwise WP-Cron. Product saves and stock changes update the index incrementally. Daily rebuild can be enabled on the Search tab.', 'oli-ajax-search' ); ?></p>
			<?php if ( Oli_AS_Compat_TranslatePress::is_active() ) : ?>
				<p class="description"><?php esc_html_e( 'TranslatePress is active. Product titles and descriptions are indexed per published language and searched in the current language.', 'oli-ajax-search' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Analytics tables.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	protected function render_analytics() {
		$top  = Oli_AS_Analytics::instance()->top_queries( 25 );
		$none = Oli_AS_Analytics::instance()->no_result_queries( 25 );
		?>
		<p><?php esc_html_e( 'Queries are stored on this site only. Nothing is sent to remote services.', 'oli-ajax-search' ); ?></p>
		<p><button type="button" class="button" id="oli-as-clear-analytics"><?php esc_html_e( 'Clear analytics', 'oli-ajax-search' ); ?></button></p>
		<h2><?php esc_html_e( 'Top queries', 'oli-ajax-search' ); ?></h2>
		<?php $this->analytics_table( $top ); ?>
		<h2><?php esc_html_e( 'Queries with no results', 'oli-ajax-search' ); ?></h2>
		<?php $this->analytics_table( $none ); ?>
		<?php
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
	 * Checkbox helper.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $key      Setting key.
	 * @param string               $label    Label.
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	protected function checkbox( $key, $label, $settings ) {
		$checked = ! empty( $settings[ $key ] ) && '0' !== (string) $settings[ $key ];
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		echo '<input type="hidden" name="' . esc_attr( Oli_AS_Settings::OPTION ) . '[_seen][' . esc_attr( $key ) . ']" value="1" />';
		echo '<label><input type="checkbox" name="' . esc_attr( Oli_AS_Settings::OPTION ) . '[' . esc_attr( $key ) . ']" value="1" ' . checked( $checked, true, false ) . ' /> ' . esc_html( $label ) . '</label>';
		echo '</td></tr>';
	}
}
