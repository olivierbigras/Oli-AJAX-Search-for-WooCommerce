<?php
/**
 * Simple class autoloader.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Maps Oli_AS_* class names to files.
 *
 * @since 0.1.0
 */
class Oli_AS_Autoloader {

	/**
	 * Registers the autoloader.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Loads a class file when the name uses the plugin prefix.
	 *
	 * @since 0.1.0
	 *
	 * @param string $class_name Class name.
	 * @return void
	 */
	public static function autoload( $class_name ) {
		if ( 0 !== strpos( $class_name, 'Oli_AS_' ) ) {
			return;
		}

		$relative = strtolower( str_replace( '_', '-', $class_name ) );
		$file     = 'class-' . $relative . '.php';

		$paths = array(
			OLI_AS_DIR . 'includes/' . $file,
			OLI_AS_DIR . 'includes/admin/' . $file,
			OLI_AS_DIR . 'includes/compat/' . $file,
		);

		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
