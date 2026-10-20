<?php
/**
 * Gutenberg block.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the search form block.
 *
 * @since 0.1.0
 */
class Oli_AS_Block {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Block|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Block
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers the block.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registers block.json.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register() {
		wp_register_script(
			'oli-as-block-editor',
			OLI_AS_URL . 'assets/block/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			OLI_AS_VERSION,
			true
		);

		register_block_type(
			OLI_AS_DIR . 'assets/block',
			array(
				'editor_script'   => 'oli-as-block-editor',
				'render_callback' => array( $this, 'render' ),
			)
		);

		wp_register_script(
			'oli-as-block-nav-editor',
			OLI_AS_URL . 'assets/block-nav/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			OLI_AS_VERSION,
			true
		);

		register_block_type(
			OLI_AS_DIR . 'assets/block-nav',
			array(
				'editor_script'   => 'oli-as-block-nav-editor',
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Server-side block render.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		$layout      = isset( $attributes['layout'] ) ? $attributes['layout'] : 'classic';
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';

		return Oli_AS_Shortcode::instance()->render(
			array(
				'layout'      => $layout,
				'placeholder' => $placeholder,
			)
		);
	}
}
