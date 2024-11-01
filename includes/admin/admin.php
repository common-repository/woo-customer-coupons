<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WOO_CUSTOM_COUPONS_Admin_Admin {
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'plugin_action_links_woo-customer-coupons/woo-customer-coupons.php', array( $this, 'settings_link', ) );

	}

	public function settings_link( $links ) {
		$settings_link = sprintf( '<a href="%s?page=woo_customer_coupons" title="%s">%s</a>', esc_attr( admin_url( 'admin.php' ) ),
			esc_attr__( 'Settings', 'woo-customer-coupons' ),
			esc_html__( 'Settings', 'woo-customer-coupons' )
		);
		array_unshift( $links, $settings_link );

		return $links;
	}

	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'woo-customer-coupons' );
		load_textdomain( 'woo-customer-coupons', WOO_CUSTOM_COUPONS_LANGUAGES . "woo-customer-coupons-$locale.mo" );
		load_plugin_textdomain( 'woo-customer-coupons', false, WOO_CUSTOM_COUPONS_LANGUAGES );
	}

	public function init() {
		$this->load_plugin_textdomain();
		if ( class_exists( 'VillaTheme_Support' ) ) {
			new VillaTheme_Support(
				array(
					'support'    => 'https://wordpress.org/support/plugin/woo-customer-coupons/',
					'docs'       => 'https://docs.villatheme.com/?item=woo_customer_coupons',
					'review'     => 'https://wordpress.org/support/plugin/woo-customer-coupons/reviews/?rate=5#rate-response',
					'pro_url'    => '',
					'css'        => WOO_CUSTOM_COUPONS_CSS,
					'image'      => WOO_CUSTOM_COUPONS_IMG,
					'slug'       => 'woo-customer-coupons',
					'menu_slug'  => 'woo_customer_coupons',
					'survey_url' => 'https://script.google.com/macros/s/AKfycbxZN3xYNW08a-ui-zSx_XMmtSAkQZGivPTfxAH4jwCYd-u-KBioH4XoK7RhfvXDhtyJ/exec',
					'version'    => WOO_CUSTOM_COUPONS_VERSION
				)
			);
		}
	}

}