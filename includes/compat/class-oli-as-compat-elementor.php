<?php
/**
 * Elementor widget registration.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Oli AJAX Search Elementor widget when Elementor is active.
 *
 * @since 0.2.0
 */
class Oli_AS_Compat_Elementor {

	/**
	 * Registers Elementor hooks.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! did_action( 'elementor/loaded' ) && ! defined( 'ELEMENTOR_VERSION' ) ) {
			add_action( 'elementor/widgets/register', array( __CLASS__, 'register' ) );
			add_action( 'elementor/widgets/widgets_registered', array( __CLASS__, 'register' ) );
			return;
		}

		add_action( 'elementor/widgets/register', array( __CLASS__, 'register' ) );
		add_action( 'elementor/widgets/widgets_registered', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers the widget class.
	 *
	 * @since 0.2.0
	 *
	 * @param object|null $widgets_manager Manager.
	 * @return void
	 */
	public static function register( $widgets_manager = null ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once OLI_AS_DIR . 'includes/compat/class-oli-as-elementor-widget.php';

		if ( $widgets_manager && method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( new Oli_AS_Elementor_Widget() );
			return;
		}

		if ( $widgets_manager && method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( new Oli_AS_Elementor_Widget() );
		}
	}
}
