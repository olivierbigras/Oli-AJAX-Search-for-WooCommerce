<?php
/**
 * Search widget.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Classic widget wrapper around the shortcode.
 *
 * @since 0.1.0
 */
class Oli_AS_Widget extends WP_Widget {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		parent::__construct(
			'oli_as_widget',
			__( 'Oli AJAX Search bar', 'oli-ajax-search' ),
			array(
				'description' => __( 'Live AJAX product search bar.', 'oli-ajax-search' ),
			)
		);
	}

	/**
	 * Front-end output.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $args     Widget args.
	 * @param array<string, mixed> $instance Instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$title       = isset( $instance['title'] ) ? $instance['title'] : '';
		$layout      = isset( $instance['layout'] ) ? $instance['layout'] : 'classic';
		$placeholder = isset( $instance['placeholder'] ) ? $instance['placeholder'] : '';

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$html = Oli_AS_Shortcode::instance()->render(
			array(
				'layout'      => $layout,
				'placeholder' => $placeholder,
			)
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Widget form.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $instance Instance.
	 * @return void
	 */
	public function form( $instance ) {
		$title       = isset( $instance['title'] ) ? $instance['title'] : '';
		$layout      = isset( $instance['layout'] ) ? $instance['layout'] : 'classic';
		$placeholder = isset( $instance['placeholder'] ) ? $instance['placeholder'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'oli-ajax-search' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>"><?php esc_html_e( 'Layout', 'oli-ajax-search' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>">
				<option value="classic" <?php selected( $layout, 'classic' ); ?>><?php esc_html_e( 'Search bar', 'oli-ajax-search' ); ?></option>
				<option value="icon" <?php selected( $layout, 'icon' ); ?>><?php esc_html_e( 'Search icon', 'oli-ajax-search' ); ?></option>
				<option value="icon-flexible" <?php selected( $layout, 'icon-flexible' ); ?>><?php esc_html_e( 'Icon on mobile, search bar on desktop', 'oli-ajax-search' ); ?></option>
				<option value="icon-flexible-inv" <?php selected( $layout, 'icon-flexible-inv' ); ?>><?php esc_html_e( 'Icon on desktop, search bar on mobile', 'oli-ajax-search' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'placeholder' ) ); ?>"><?php esc_html_e( 'Search input placeholder', 'oli-ajax-search' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'placeholder' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'placeholder' ) ); ?>" type="text" value="<?php echo esc_attr( $placeholder ); ?>" />
		</p>
		<?php
	}

	/**
	 * Saves widget settings.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $new_instance New values.
	 * @param array<string, mixed> $old_instance Old values.
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$layouts = array( 'classic', 'icon', 'icon-flexible', 'icon-flexible-inv' );
		$layout  = isset( $new_instance['layout'] ) ? $new_instance['layout'] : 'classic';

		return array(
			'title'       => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'layout'      => in_array( $layout, $layouts, true ) ? $layout : 'classic',
			'placeholder' => isset( $new_instance['placeholder'] ) ? sanitize_text_field( $new_instance['placeholder'] ) : '',
		);
	}
}
