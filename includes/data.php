<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VI_WOO_CUSTOMER_COUPONS_Data {
	private $params, $default;
	protected static $instance = null;

	/**
	 * VI_WOO_CUSTOMER_COUPONS_Data constructor.
	 * Init setting
	 */
	public function __construct() {
		global $vi_wcc_settings;
		if ( ! $vi_wcc_settings ) {
			$vi_wcc_settings = get_option( 'wcc_options', array() );
		}
		$this->default = array(
			'show_all' => 0,
			'show_on_cart' => 1,
			'show_on_checkout' => 1,
			'wcc_coupon-single_pro_page_pos' => 20,
			'wcc_date_format'                => 0,
			'coupon_title'                   => '{coupon_value} OFF',
			'coupon_desc'                   => 'Min. Spend {min_spend}',
			'coupon_date'                   => 1,
			'wcc_template'                   => '1',
			'wcc-template-one'               => array(
				'wcc-temple-one-background-color'          => '#ffffff',
				'wcc-temple-one-content-background-color1' => 'rgba(255, 187, 0, 0.67)',
				'wcc-temple-one-content-background-color2' => '#f47b7b',
				'wcc-temple-one-content-title-color'       => '#ffffff',
				'wcc-temple-one-content-des-color'         => '#fde7e7',
				'wcc-temple-one-content-expire-color'      => '#f5f5f5',
				'wcc-temple-one-button-background-color'   => 'rgba(245, 226, 160, 0.34)',
				'wcc-temple-one-button-text-color'         => '#320463'
			),
			'wcc-template-two'               => array(
				'wcc-temple-two-background-color' => '#4bbb89',
				'wcc-temple-two-title-color'      => '#1d0b0b',
				'wcc-temple-two-border-color'     => '#0a0a46',
			),
			'wcc-template-three'             => array(
				'wcc-temple-three-background-color' => '#fff4f4',
				'wcc-temple-three-title-color'      => '#d0011b',
				'wcc-temple-three-term-color'       => 'rgba(208, 1, 27, 0.83)',
				'wcc-temple-three-expire-color'     => 'rgba(0, 0, 0, 0.54)',
				'wcc-temple-three-border-color'     => '#d0011b',
				'wcc-temple-three-border-type'      => 'dotted',
			),
			'wcc-template-four'              => array(
				'wcc-temple-four-background-color'       => '#ffffff',
				'wcc-temple-four-title-color'            => '#f7f7f7',
				'wcc-temple-four-title-background-color' => '#d14b1b',
				'wcc-temple-four-term-color'             => '#6f6060',
				'wcc-temple-four-border-color'           => '#d14b1b',
				'wcc-temple-four-border-type'            => 'none',
				'wcc-temple-four-border-radius'          => '5px'
			),
			'send_email' => 0,
			'wcc_send-mail-subject'          => 'Discount Coupon From {site_title}',
			'wcc_mail_heading'               => 'Congratulations! You received a {coupon_value} discount for your next purchase.',
			'wcc_mail_content'               => "Dear,
We'd like to offer you this discount coupon for shopping.

Coupon code: {coupon_code}.
Valid till: {last_valid_date}.

{shop_now}

Don't miss out on this great chance at our shop. 
Yours sincerely!",

			'wcc_button_shop_now_title'         => 'Shop Now',
			'wcc_button_shop_now_url'           => '',
			'wcc_button_shop_now_bg_color'      => '#9976c2',
			'wcc_button_shop_now_color'         => '#fff',
			'wcc_button_shop_now_size'          => '16',
			'wcc_button_shop_now_border_radius' => '3',
		);
		$this->params  = apply_filters( 'viwcc_settings_args', wp_parse_args( $vi_wcc_settings, $this->default ) );
	}
	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function get_params( $name = "" ) {
		if ( ! $name ) {
			return $this->params;
		} else{
			return apply_filters( 'viwcc_settings-' . $name, $this->params[ $name ] ?? null);
		}
	}

	public function get_default( $name = "" ) {
		if ( ! $name ) {
			return $this->default;
		}else {
			return apply_filters( 'viwcc_settings_default-' . $name, $this->default[ $name ] ?? null );
		}
	}
	public function get_current_setting( $name = "", $i = 0, $default = null ) {
		if ( ! $name ) {
			return false;
		}
		if ( $default !== null ) {
			$result = $this->get_params( $name )[ $i ] ?? $default;
		} else {
			$result = $this->get_params( $name )[ $i ] ?? $this->get_default( $name )[ $i ] ?? null;
		}

		return $result;
	}
	public static function remove_other_script() {
		global $wp_scripts;
		if ( isset( $wp_scripts->registered['jquery-ui-accordion'] ) ) {
			unset( $wp_scripts->registered['jquery-ui-accordion'] );
			wp_dequeue_script( 'jquery-ui-accordion' );
		}
		if ( isset( $wp_scripts->registered['accordion'] ) ) {
			unset( $wp_scripts->registered['accordion'] );
			wp_dequeue_script( 'accordion' );
		}
		$scripts = $wp_scripts->registered;
		foreach ( $scripts as $k => $script ) {
			if ( in_array( $script->handle, array( 'query-monitor', 'uip-app', 'uip-vue', 'uip-toolbar-app' ) ) ) {
				continue;
			}
			preg_match( '/\/wp-/i', $script->src, $result );
			if ( count( array_filter( $result ) ) ) {
				preg_match( '/(\/wp-content\/plugins|\/wp-content\/themes)/i', $script->src, $result1 );
				if ( count( array_filter( $result1 ) ) ) {
					wp_dequeue_script( $script->handle );
				}
			} else {
				wp_dequeue_script( $script->handle );
			}
		}
	}

	public static function enqueue_style( $handles = array(), $srcs = array(), $is_suffix = array(), $des = array(), $type = 'enqueue' ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'enqueue' ? 'wp_enqueue_style' : 'wp_register_style';
		$suffix = WP_DEBUG ? '' : '.min';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$suffix_t = ! empty( $is_suffix[ $i ] ) ? '.min' : $suffix;
			$action( $handle, WOO_CUSTOM_COUPONS_CSS . $srcs[ $i ] . $suffix_t . '.css', ! empty( $des[ $i ] ) ? $des[ $i ] : array(), WOO_CUSTOM_COUPONS_VERSION );
		}
	}

	public static function enqueue_script( $handles = array(), $srcs = array(), $is_suffix = array(), $des = array(), $type = 'enqueue', $in_footer = false ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'register' ? 'wp_register_script' : 'wp_enqueue_script';
		$suffix = WP_DEBUG ? '' : '.min';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$suffix_t = ! empty( $is_suffix[ $i ] ) ? '.min' : $suffix;
			$action( $handle, WOO_CUSTOM_COUPONS_JS . $srcs[ $i ] . $suffix_t . '.js', ! empty( $des[ $i ] ) ? $des[ $i ] : array( 'jquery' ), WOO_CUSTOM_COUPONS_VERSION, $in_footer );
		}
	}
	public static function get_email_style($bg_color = null, $color = null, $font_size=null, $border_radius=null){
		$css = '.wcc-button-shop-now{';
		$css .='text-decoration:none;display:inline-block;padding:10px 30px;margin:10px 0;';
		$css .='background:'. ($bg_color ?? self::get_instance()->get_params('wcc_button_shop_now_bg_color')).';';
		$css .='color:'. ($color ?? self::get_instance()->get_params('wcc_button_shop_now_color')) .';';
		$css .='font-size:'.($font_size ?? self::get_instance()->get_params('wcc_button_shop_now_size')) .'px;';
		$css .='border-radius:'.($border_radius ?? self::get_instance()->get_params('wcc_button_shop_now_border_radius')) .'px;';
		$css .='}';
		return $css;
	}
	public static function get_coupon_style(){
		$settings = self::get_instance();
		//css - template 1
		$css ='.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content{';
		$css .='background-color: '.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-background-color1'). ';';
		$css .='background-image: linear-gradient(315deg, '.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-background-color1'). ' 0%, '.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-background-color2').' 85%);';
		$css .= '}';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content .viwcc-coupon-title{';
		$css .='color:'.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-title-color').' }';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content .viwcc-coupon-des{';
		$css .='color:'.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-des-color');
		$css .= '}';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content .viwcc-coupon-date{';
		$css .='color:'.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-expire-color');
		$css .= '}';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-button{';
		$css .='background:'.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-button-background-color').';';
		$css .='color:'.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-button-text-color').';';
		$css .= '}';
		//css - template 2
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-2{';
		$css .='background:'.$settings->get_current_setting( 'wcc-template-two', 'wcc-temple-two-background-color' ).';';
		$css .= '}';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-2 .viwcc-coupon-content{';
		$css .='border: 1px solid '.$settings->get_current_setting( 'wcc-template-two', 'wcc-temple-two-border-color').';';
		$css .='color:'.$settings->get_current_setting( 'wcc-template-two', 'wcc-temple-two-title-color').';';
		$css .= '}';
		//css - template 3
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-3{';
		$css .='border: 1px '.$settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-border-type').' '.$settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-border-color').';';
		$css .='background:'.$settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-background-color').';';
		$css .= '}';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-3 .viwcc-coupon-content .viwcc-coupon-title{';
		$css .='color: '.$settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-title-color').';';
		$css .= '}';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-3 .viwcc-coupon-content .viwcc-coupon-des{';
		$css .='color: '.$settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-term-color').';';
		$css .= '}';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-3 .viwcc-coupon-content .viwcc-coupon-date{';
		$css .='color: '.$settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-expire-color').';';
		$css .= '}';
		//css - template 4
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-4{';
		$css .='background:'.$settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-background-color').';';
		$template4_border_radius = $settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-border-radius');
		$css .='border-radius:'.$template4_border_radius.';';
		$template4_border_type = $settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-border-type');
		if ($template4_border_type ==='none'){
			$css .='box-shadow: inset 0 0 10px '.$settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-border-color').';';
		}else{
			$css .='box-shadow: unset;';
		}
		$css .='}';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-content{';
		$css .='box-shadow: 0px 1px 1px;';
		$css .='color:'.$settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-term-color');
		$css .='border-color:'.$settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-border-color').';';
		$css .='border-style:'.$settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-border-type').';';
		$css .='border-bottom-left-radius:'.$template4_border_radius.';';
		$css .='border-bottom-right-radius:'.$template4_border_radius.';';
		$css .='}';
		$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-title{';
		$css .='color:'.$settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-title-color').';';
		$css .='background:'.$settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-title-background-color').';';
		$css .='}';
		if (!is_admin()){
			$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content .viwcc-coupon-title *{';
			$css .='color:'.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-title-color').' }';
			$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content .viwcc-coupon-des *{';
			$css .='color:'.$settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-des-color');
			$css .= '}';
			$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-2 .viwcc-coupon-content *{';
			$css .='color:'.$settings->get_current_setting( 'wcc-template-two', 'wcc-temple-two-title-color').';';
			$css .='}';
			$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-3 .viwcc-coupon-content .viwcc-coupon-title *{';
			$css .='color: '.$settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-title-color').';';
			$css .='}';
			$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-3 .viwcc-coupon-content .viwcc-coupon-des *{';
			$css .='color: '.$settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-term-color').';';
			$css .='}';
			$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-content *{';
			$css .='color:'.$settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-term-color');
			$css .='}';
			$css .='.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-title *{';
			$css .='color:'.$settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-title-color');
			$css .='}';
		}

		return $css;
	}
}