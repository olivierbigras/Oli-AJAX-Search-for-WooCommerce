<?php
/**
 * Theme search form replacement.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Replaces core and common-theme search forms.
 *
 * @since 0.1.0
 */
class Oli_AS_Form_Replacer {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Form_Replacer|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Form_Replacer
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers replacement filters.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		if ( ! Oli_AS_Settings::enabled( 'replace_theme_form' ) ) {
			return;
		}

		add_filter( 'get_search_form', array( $this, 'replace_form' ), 20 );
		add_filter( 'get_product_search_form', array( $this, 'replace_form' ), 20 );
		add_filter( 'storefront_product_search_form', array( $this, 'replace_form' ), 20 );
		add_filter( 'astra_get_search_form', array( $this, 'replace_form' ), 20 );
		add_filter( 'kadence_search_form', array( $this, 'replace_form' ), 20 );
		add_filter( 'generate_navigation_search_output', array( $this, 'replace_form' ), 20 );
		add_filter( 'ocean_get_search_form', array( $this, 'replace_form' ), 20 );
		add_filter( 'blocksy:header:search:form', array( $this, 'replace_form' ), 20 );
	}

	/**
	 * Returns the plugin search form.
	 *
	 * @since 0.1.0
	 *
	 * @param string $form Existing form.
	 * @return string
	 */
	public function replace_form( $form ) {
		unset( $form );
		return Oli_AS_Shortcode::instance()->render();
	}
}
