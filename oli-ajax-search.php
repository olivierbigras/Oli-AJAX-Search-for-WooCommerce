<?php
/**
 * Plugin Name:       Oli AJAX Search for WooCommerce
 * Plugin URI:        https://github.com/olivierbigras/Oli-AJAX-Search-for-WooCommerce
 * Description:       Fast live AJAX search for WooCommerce with a custom index, typo tolerance, and multilingual support.
 * Version:           0.1.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Olivier Bigras
 * Author URI:        https://olivierbigras.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       oli-ajax-search
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 9.0
 * WC tested up to:   11.2
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

define( 'OLI_AS_VERSION', '0.1.1' );
define( 'OLI_AS_FILE', __FILE__ );
define( 'OLI_AS_DIR', plugin_dir_path( __FILE__ ) );
define( 'OLI_AS_URL', plugin_dir_url( __FILE__ ) );
define( 'OLI_AS_BASENAME', plugin_basename( __FILE__ ) );

require_once OLI_AS_DIR . 'includes/class-oli-as-autoloader.php';

Oli_AS_Autoloader::register();

/**
 * Declares WooCommerce feature compatibility.
 *
 * @since 0.1.0
 *
 * @return void
 */
function oli_as_declare_wc_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', OLI_AS_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', OLI_AS_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'oli_as_declare_wc_compatibility' );

/**
 * Boots the plugin after WooCommerce is available.
 *
 * @since 0.1.0
 *
 * @return void
 */
function oli_as_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'oli_as_missing_woocommerce_notice' );
		return;
	}

	Oli_AS_Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'oli_as_bootstrap' );

/**
 * Shows an admin notice when WooCommerce is missing.
 *
 * @since 0.1.0
 *
 * @return void
 */
function oli_as_missing_woocommerce_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Oli AJAX Search for WooCommerce requires WooCommerce to be installed and active.', 'oli-ajax-search' );
	echo '</p></div>';
}

register_activation_hook( __FILE__, array( 'Oli_AS_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Oli_AS_Install', 'deactivate' ) );
