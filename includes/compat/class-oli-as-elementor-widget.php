<?php
/**
 * Elementor search widget.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Elementor widget wrapping the search shortcode.
 *
 * @since 0.2.0
 */
class Oli_AS_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'oli_ajax_search';
	}

	/**
	 * Widget title.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Oli AJAX Search', 'oli-ajax-search' );
	}

	/**
	 * Widget icon.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-search';
	}

	/**
	 * Widget categories.
	 *
	 * @since 0.2.0
	 *
	 * @return array<int, string>
	 */
	public function get_categories() {
		return array( 'woocommerce-elements', 'general' );
	}

	/**
	 * Widget keywords.
	 *
	 * @since 0.2.0
	 *
	 * @return array<int, string>
	 */
	public function get_keywords() {
		return array( 'search', 'woocommerce', 'ajax', 'oli' );
	}

	/**
	 * Registers controls.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Search bar', 'oli-ajax-search' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'oli-ajax-search' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'classic',
				'options' => array(
					'classic'           => __( 'Search bar', 'oli-ajax-search' ),
					'icon'              => __( 'Search icon', 'oli-ajax-search' ),
					'icon-flexible'     => __( 'Icon on mobile, search bar on desktop', 'oli-ajax-search' ),
					'icon-flexible-inv' => __( 'Icon on desktop, search bar on mobile', 'oli-ajax-search' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Renders the widget.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$layout   = isset( $settings['layout'] ) ? $settings['layout'] : 'classic';

		$html = Oli_AS_Shortcode::instance()->render(
			array(
				'layout' => $layout,
			)
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
