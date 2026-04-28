<?php
/**
 * Plugin Name: WC Auto Product Filters
 * Description: Auto-generated WooCommerce product filters with per-category context and admin controls.
 * Version: 0.1.0
 * Author: Fine Studio
 * Text Domain: wc-auto-product-filters
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCAPF_VERSION', '0.1.0' );
define( 'WCAPF_FILE', __FILE__ );
define( 'WCAPF_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCAPF_URL', plugin_dir_url( __FILE__ ) );

require_once WCAPF_PATH . 'includes/helpers.php';
require_once WCAPF_PATH . 'includes/class-compat-wpml.php';
require_once WCAPF_PATH . 'includes/class-compat-polylang.php';
require_once WCAPF_PATH . 'includes/class-filter-discovery.php';
require_once WCAPF_PATH . 'includes/class-filter-renderer.php';
require_once WCAPF_PATH . 'includes/class-query.php';
require_once WCAPF_PATH . 'includes/class-ajax.php';
require_once WCAPF_PATH . 'includes/class-shortcode.php';
require_once WCAPF_PATH . 'includes/class-admin.php';
require_once WCAPF_PATH . 'includes/class-plugin.php';

function wcapf_boot_plugin() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$plugin = new WC_Auto_Product_Filters_Plugin();
	$plugin->init();
}
add_action( 'plugins_loaded', 'wcapf_boot_plugin' );
