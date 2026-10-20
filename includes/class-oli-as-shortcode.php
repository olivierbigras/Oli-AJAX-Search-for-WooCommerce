<?php
/**
 * Search form shortcode.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders [oli_ajax_search].
 *
 * @since 0.1.0
 */
class Oli_AS_Shortcode {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Shortcode|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Shortcode
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers the shortcode.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_shortcode( 'oli_ajax_search', array( $this, 'render' ) );
	}

	/**
	 * Renders the form.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'layout'      => (string) Oli_AS_Settings::get( 'layout', 'classic' ),
				'placeholder' => (string) Oli_AS_Settings::get( 'placeholder', '' ),
			),
			is_array( $atts ) ? $atts : array(),
			'oli_ajax_search'
		);

		$layout      = in_array( $atts['layout'], array( 'classic', 'icon' ), true ) ? $atts['layout'] : 'classic';
		$placeholder = sanitize_text_field( $atts['placeholder'] );
		$lang        = Oli_AS_Language::instance()->current();
		$action      = Oli_AS_Language::instance()->url( home_url( '/' ), $lang );
		$uid         = wp_unique_id( 'oli-as-' );
		$show_submit = Oli_AS_Settings::enabled( 'show_submit' );
		$submit_text = trim( (string) Oli_AS_Settings::get( 'submit_text', '' ) );

		ob_start();
		?>
		<div class="oli-as-root oli-as-layout-<?php echo esc_attr( $layout ); ?>" data-oli-as-root="1" data-layout="<?php echo esc_attr( $layout ); ?>">
			<?php if ( 'icon' === $layout ) : ?>
				<button type="button" class="oli-as-icon-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $uid ); ?>-panel">
					<span class="screen-reader-text"><?php esc_html_e( 'Open search', 'oli-ajax-search' ); ?></span>
					<span class="oli-as-icon" aria-hidden="true"></span>
				</button>
			<?php endif; ?>
			<form id="<?php echo esc_attr( $uid ); ?>-panel" class="oli-as-form" role="search" method="get" action="<?php echo esc_url( $action ); ?>">
				<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>-input"><?php esc_html_e( 'Search', 'oli-ajax-search' ); ?></label>
				<input
					id="<?php echo esc_attr( $uid ); ?>-input"
					class="oli-as-input"
					type="search"
					name="s"
					value=""
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					autocomplete="off"
					autocorrect="off"
					autocapitalize="none"
					spellcheck="false"
				/>
				<input type="hidden" name="post_type" value="product" />
				<input type="hidden" name="oli_as" value="1" />
				<?php if ( Oli_AS_Settings::enabled( 'show_preloader' ) ) : ?>
					<span class="oli-as-preloader" hidden></span>
				<?php endif; ?>
				<?php if ( $show_submit ) : ?>
					<button type="submit" class="oli-as-submit<?php echo '' !== $submit_text ? ' has-text' : ''; ?>">
						<?php if ( '' !== $submit_text ) : ?>
							<?php echo esc_html( $submit_text ); ?>
						<?php else : ?>
							<span class="screen-reader-text"><?php esc_html_e( 'Search', 'oli-ajax-search' ); ?></span>
							<span class="oli-as-icon" aria-hidden="true"></span>
						<?php endif; ?>
					</button>
				<?php endif; ?>
			</form>
			<div class="oli-as-panel" hidden>
				<div class="oli-as-suggestions" role="listbox"></div>
				<aside class="oli-as-details" hidden></aside>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
