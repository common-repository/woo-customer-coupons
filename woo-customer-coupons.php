<?php
/**
 * Plugin Name: Customer Coupons for WooCommerce
 * Plugin URI:https://villatheme.com/extensions/woocommerce-customer-coupons/
 * Description: Customer Coupons for WooCommerce helps you display your coupons on the website.
 * Author: VillaTheme
 * Author URI:https://villatheme.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.3.3
 * Text Domain: woo-customer-coupons
 * Domain Path: /languages
 * Copyright 2019-2024 VillaTheme.com. All rights reserved.
 * Requires Plugins: woocommerce
 * Requires PHP: 7.0
 * Requires at least: 5.0
 * Tested up to: 6.5
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

/**
 * Class VIWCC_WOO_CUSTOM_COUPONS
 */
class VIWCC_WOO_CUSTOM_COUPONS {

	public function __construct() {
		$this->define();
		add_action( 'activated_plugin', array( $this, 'activated_plugin' ),10,2 );
		//compatible with 'High-Performance order storage (COT)'
		add_action( 'before_woocommerce_init', array( $this, 'before_woocommerce_init' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	protected function define() {
		define( 'WOO_CUSTOM_COUPONS_VERSION', '1.3.3' );
		define( 'WOO_CUSTOM_COUPONS_DIR', plugin_dir_path( __FILE__ ) );
		define( 'WOO_CUSTOM_COUPONS_LANGUAGES', WOO_CUSTOM_COUPONS_DIR . "languages" . DIRECTORY_SEPARATOR );
		define( 'WOO_CUSTOM_COUPONS_INCLUDES', WOO_CUSTOM_COUPONS_DIR . "includes" . DIRECTORY_SEPARATOR );
		define( 'WOO_CUSTOM_COUPONS_ADMIN', WOO_CUSTOM_COUPONS_INCLUDES . "admin" . DIRECTORY_SEPARATOR );
		define( 'WOO_CUSTOM_COUPONS_FRONTEND', WOO_CUSTOM_COUPONS_INCLUDES . "frontend" . DIRECTORY_SEPARATOR );
		define( 'WOO_CUSTOM_COUPONS_TEMPLATES', WOO_CUSTOM_COUPONS_INCLUDES . "templates" . DIRECTORY_SEPARATOR );
		$plugin_url = plugins_url( 'assets/', __FILE__ );
		define( 'WOO_CUSTOM_COUPONS_CSS', $plugin_url . "css/" );
		define( 'WOO_CUSTOM_COUPONS_JS', $plugin_url . "js/" );
		define( 'WOO_CUSTOM_COUPONS_IMG', $plugin_url . "images/" );
	}
	public function init() {
		$include_dir = plugin_dir_path( __FILE__ ) . 'includes/';
		if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
			include_once $include_dir . 'support.php';
		}

		$environment = new VillaTheme_Require_Environment( [
				'plugin_name'     => 'Customer Coupons for WooCommerce',
				'php_version'     => '7.0',
				'wp_version'      => '5.0',
				'require_plugins' => [
					[
						'slug' => 'woocommerce',
						'name' => 'WooCommerce',
						'required_version' => '7.0',
					]
				]
			]
		);

		if ( $environment->has_error() ) {
			return;
		}

		$this->includes();
	}

	protected function includes() {
		$files = array(
			WOO_CUSTOM_COUPONS_INCLUDES . 'data.php',
			WOO_CUSTOM_COUPONS_INCLUDES . 'functions.php',
			WOO_CUSTOM_COUPONS_INCLUDES . 'support.php',
		);
		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}

		vi_include_folder( WOO_CUSTOM_COUPONS_ADMIN, 'WOO_CUSTOM_COUPONS_Admin_' );
		vi_include_folder( WOO_CUSTOM_COUPONS_FRONTEND, 'WOO_CUSTOM_COUPONS_Frontend_' );
	}


	public function activated_plugin($plugin, $network_wide ) {
		if ($plugin !== 'woo-customer-coupons/woo-customer-coupons.php' ){
			return;
		}
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
	}
	public function before_woocommerce_init() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
}

new VIWCC_WOO_CUSTOM_COUPONS();