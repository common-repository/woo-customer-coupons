<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_CUSTOM_COUPONS_Frontend_Frontend {
	protected static $settings, $available_coupons;

	public function __construct() {
		self::$settings = VI_WOO_CUSTOMER_COUPONS_Data::get_instance();
		//create new endpoint woo
		add_filter( 'woocommerce_get_query_vars', array( $this, 'viwcc_woocommerce_get_query_vars' ), PHP_INT_MAX, 1 );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_custom_link_to_my_account' ), PHP_INT_MAX, 1 );
		//show list available coupons
		$available_hooks = array(
			'woocommerce_account_viwcc_coupons_endpoint'
		);
		if ( self::$settings->get_params( 'show_on_cart' ) ) {
			$available_hooks[] = apply_filters( 'viwcc-cart-hook', 'woocommerce_after_cart_table' );
		}
		if ( self::$settings->get_params( 'show_on_checkout' ) ) {
			$available_hooks[] = apply_filters( 'viwcc-checkout-hook', 'woocommerce_before_checkout_form' );
		}
		foreach ( $available_hooks as $hook ) {
			add_action( $hook, array( __CLASS__, 'display_available_coupons' ), 11 );
		}
		if ( self::$settings->get_params( 'wcc_coupon-single_pro_page_pos' ) ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'display_promotions' ), self::$settings->get_params( 'wcc_coupon-single_pro_page_pos' ) );
		}
		$hooks = array(
			'woocommerce_cart_loaded_from_session',
			'woocommerce_after_calculate_totals',
		);
		foreach ( $hooks as $hook ) {
			add_action( $hook, array( $this, 'auto_apply_coupons' ) );
		}
		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'remove_link' ), PHP_INT_MAX, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ), 99 );
		self::add_ajax_events();
	}

	public static function add_ajax_events() {
		$ajax_events = array(
			'viwcc_apply_coupon' => true,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			$call_back = str_replace( 'viwcc_', '', $ajax_event );
			add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $call_back ) );
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $call_back ) );
				// WC AJAX can be used for frontend ajax requests
				add_action( 'wc_ajax_' . $ajax_event, array( __CLASS__, $call_back ) );
			}
		}
	}

	public static function apply_coupon() {
		$result = array(
			'status' => 'error',
		);
		if ( ! check_ajax_referer( 'viwcc_nonce', 'viwcc_nonce', false ) ) {
			$result['message'] = esc_html__( 'Error: missing nonce', 'woo-customer-coupons' );
			wp_send_json( $result );
		}
		$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '';
		if ( $coupon_code ) {
			WC()->cart->add_discount( wc_format_coupon_code( $coupon_code ) );
			$notices = WC()->session->get( 'wc_notices', array() );
			if ( empty( $notices['error'] ) ) {
				$result['status'] = 'success';
			}
		} else {
			wc_add_notice( WC_Coupon::get_generic_coupon_error( WC_Coupon::E_WC_COUPON_PLEASE_ENTER ), 'error' );
		}
		$result['message'] = wc_print_notices( true );
		wp_send_json( $result );
	}

	public function auto_apply_coupons( $cart ) {
		if ( ! $cart || $cart->is_empty() ) {
			return;
		}
		$coupons         = self::get_available_coupon();
		$applied_coupons = $cart->get_applied_coupons();
		foreach ( $coupons as $coupon ) {
			$coupon      = new WC_Coupon( $coupon );
			$coupon_code = $coupon->get_code();
			if ( $coupon->get_meta( 'viwcc_auto_apply' ) !== 'yes' || ( is_array( $applied_coupons ) && in_array( $coupon_code, $applied_coupons ) ) ) {
				continue;
			}
			$wc_discount = new WC_Discounts( $cart );
			if ( is_wp_error( $wc_discount->is_coupon_valid( $coupon ) ) ) {
				continue;
			}
			$cart->add_discount( wc_format_coupon_code( $coupon_code ) );
		}
	}

	public function remove_link( $html, $coupon, $discount_amount_html ) {
		$coupon = new WC_Coupon( $coupon );
		if ( ! $coupon ) {
			return $html;
		}
		if ( $coupon->get_meta( 'viwcc_auto_apply' ) === 'yes' ) {
			$html = $discount_amount_html;
		}
		return $html;
	}

	public function enqueue_script() {
//		if ( ! is_account_page() && ! is_cart() && ! is_checkout() && ! is_product() && ! is_single() ) {
//			return;
//		}
//		if ( ( is_cart() && ! self::$settings->get_params( 'show_on_cart' ) ) ||
//		     ( is_checkout() && ! self::$settings->get_params( 'show_on_checkout' ) ) ||
//		     ( is_product() && ! self::$settings->get_params( 'wcc_coupon-single_pro_page_pos' ) ) ) {
//			return;
//		}
		self::$settings::enqueue_style( array( 'viwcc-frontend', 'villatheme-show-message' ), array( 'frontend', 'villatheme-show-message' ),array(),'register' );
		self::$settings::enqueue_script( array( 'viwcc-frontend', 'villatheme-show-message' ), array( 'frontend', 'villatheme-show-message' ),array(), 'register' );
		wp_add_inline_style( 'viwcc-frontend', self::$settings::get_coupon_style() );
		wp_localize_script( 'viwcc-frontend', 'viwcc_param', array(
			'wc_ajax_url' => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'viwcc_nonce' => wp_create_nonce( 'viwcc_nonce' ),
		) );
	}

	public function viwcc_woocommerce_get_query_vars( $query ) {
		$query['viwcc_coupons'] = 'shop_coupons';
		return $query;
	}

	public function add_custom_link_to_my_account( $item ) {
		$item['viwcc_coupons'] = esc_html__( 'Coupons', 'woo-customer-coupons' );
		return $item;
	}

	public static function display_available_coupons() {
		$coupons       = self::get_available_coupon();
		$total_coupons = is_array( $coupons ) ? count( $coupons ) : 0;
		if ( $total_coupons ) {
			if ( is_account_page() ) {
				$coupon_message = esc_html__( 'Available coupons', 'woo-customer-coupons' );
				$wrap_class     = '';
			} elseif ( is_cart() ) {
				$coupon_message = esc_html__( 'Shop voucher', 'woo-customer-coupons' );
				$wrap_class     = ' viwcc-coupons-scroll-wrap';
			} else {
				$coupon_message = '';
				$wrap_class     = ' viwcc-coupons-scroll-wrap viwcc-coupons-scroll-wrap1 checkout_coupon';
			}
			wc_get_template( 'viwcc-coupons-html.php',
				array(
					'settings'        => self::$settings,
					'wrap_class'      => $wrap_class,
					'coupon_message'  => apply_filters( 'viwcc_coupon_message', $coupon_message ),
					'coupons'         => $coupons,
					'check_available' => 1,
				),
				'woo-customer-coupons' . DIRECTORY_SEPARATOR,
				WOO_CUSTOM_COUPONS_TEMPLATES );
		} elseif ( is_account_page() ) {
			wc_add_notice( esc_html__( 'Unfortunately, there are no available coupons now.', 'woo-customer-coupons' ), 'error' );
			woocommerce_output_all_notices();
		}
	}

	public static function display_promotions() {
		global $product;
		$coupons = self::get_available_coupon();
		if ( ! is_array( $coupons ) || ! $product ) {
			return;
		}
		$temp = array();
		foreach ( $coupons as $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );
			if ( $coupon->is_type(wc_get_product_coupon_types() ) && ! $coupon->is_valid_for_product( $product ) ) {
				continue;
			}
			$temp[] = $coupon_id;
		}
		$coupons       = $temp;
		$total_coupons = is_array( $coupons ) ? count( $coupons ) : 0;
		if ( ! $total_coupons ) {
			return;
		}
		printf( '<div class="viwcc-promotions-wrap">' );
		printf( '<div class="viwcc-promotions-list-wrap">' );
		printf( '<div class="viwcc-promotions-list-title">%1$s</div>', esc_html__( 'Promotions', 'woo-customer-coupons' ) );
		printf( '<div class="viwcc-promotion-list-content">' );
		$promotions_title = apply_filters( 'viwcc_get_promotion_title', esc_html__( '{coupon_value} OFF', 'woo-customer-coupons' )  );
		foreach ( $coupons as $coupon ) {
			$coupon = new WC_Coupon( $coupon );
			if ( $coupon->get_discount_type() === 'percent' ) {
				$coupon_value = $coupon->get_amount() . '%';
			} else {
				$coupon_value = wc_price( $coupon->get_amount() );
			}
			$coupon_title = str_replace(
				array( '{coupon_code}', '{coupon_value}', '{min_spend}', '{max_spend}' ),
				array(
					$coupon->get_code(),
					$coupon_value,
					wc_price( $coupon->get_minimum_amount() ),
					$coupon->get_maximum_amount() ? wc_price( $coupon->get_maximum_amount() ) : esc_html__( 'no maximum', 'woo-customer-coupons' )
				),
				$promotions_title
			);
			printf( '<div class="viwcc-promotion-coupon">%1$s</div>', wp_kses_post( $coupon_title ) );
		}
		printf( '</div></div>' );
		printf( '<div class="viwcc-promotions-content-wrap">' );
		printf( '<div class="viwcc-promotions-content-info"><div>%1$s</div></div>', esc_html__( 'Promotions', 'woo-customer-coupons' ) );
		$coupon_message = '';
		$wrap_class     = ' viwcc-coupons-scroll-wrap';
		wc_get_template( 'viwcc-coupons-html.php',
			array(
				'settings'        => self::$settings,
				'wrap_class'      => $wrap_class,
				'coupon_message'  => apply_filters( 'viwcc_coupon_message', $coupon_message ),
				'coupons'         => $coupons,
				'check_available' => 1,
			),
			'woo-customer-coupons' . DIRECTORY_SEPARATOR,
			WOO_CUSTOM_COUPONS_TEMPLATES );
		printf( '</div></div>' );
	}

	public static function get_available_coupon( $per_page = - 1, $paged = 1 ) {
		if (self::$available_coupons !== null){
			return self::$available_coupons;
		}
		$args = array(
			'post_type'      => 'shop_coupon',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'fields'         => 'ids',
			'meta_query'     => array(// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'     => 'wcc_coupon_enable',
					'compare' => '=',
					'value'   => 'yes',
				),
			),
		);
		if ( self::$settings->get_params( 'show_all' ) ) {
			$args['meta_query'] = array(// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => 'wcc_coupon_enable',
						'compare' => 'NOT EXISTS',
						'value'   => '',
					),
					array(
						'key'     => 'wcc_coupon_enable',
						'compare' => '!=',
						'value'   => 'no',
					)
				)
			);
		}
		$now                  = current_time( 'timestamp' );
		$args['meta_query'][] = array(
			'relation' => 'OR',
			array(
				'key'     => 'wcc_custom_coupon_start_date',
				'compare' => 'NOT EXISTS',
				'value'   => '',
			),
			array(
				'key'     => 'wcc_custom_coupon_start_date',
				'compare' => '=',
				'value'   => '',
			),
			array(
				'key'     => 'wcc_custom_coupon_start_date',
				'compare' => '<=',
				'value'   => date( 'Y-m-d', $now ),// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			)
		);
		global $user_email;
		$customer_email = array(
			$user_email,
		);
		if ( ! empty( WC()->checkout()->get_value( 'billing_email' ) ) ) {
			$customer_email[] = WC()->checkout()->get_value( 'billing_email' );
		}
		$email_check    = array();
		$customer_email = array_unique( $customer_email );
		foreach ( $customer_email as $email ) {
			if ( ! $email ) {
				continue;
			}
			$email_check[] = $email;
			$t             = explode( '@', $email );
			if ( ! empty( $t[1] ) ) {
				$email_check[] = '*@' . $t[1];
			}
		}
		$email_check = array_unique( $email_check );
		if ( ! empty( $email_check ) ) {
			$t = array(
				'relation' => 'OR',
				array(
					'key'     => 'customer_email',
					'compare' => 'NOT EXISTS',
					'value'   => '',
				)
			);
			foreach ( $email_check as $email ) {
				$t[] = array(
					'key'     => 'customer_email',
					'compare' => 'LIKE',
					'value'   => $email,
				);
			}
			$args['meta_query'][] = $t;
		}
		$args['viwcc_get_available_coupons']=1;
		add_filter('posts_join', array(__CLASS__, 'posts_join'), 10,2);
		add_filter('posts_where', array(__CLASS__, 'posts_where'), 10,2);
		$the_query = new WP_Query( apply_filters( 'viwcc_get_available_coupon_args', $args ) );
		self::$available_coupons = $the_query->get_posts();
		wp_reset_postdata();
		remove_filter('posts_join',array(__CLASS__,'posts_join'));
		remove_filter('posts_where',array(__CLASS__,'posts_where'));
		return self::$available_coupons ;
	}
	public static function posts_join($join, $query){
		if (!empty($query->query_vars['viwcc_get_available_coupons']) ){
			global $wpdb;
			$join .= "LEFT JOIN $wpdb->postmeta AS viwcc_date_expires ON ( $wpdb->posts.ID = viwcc_date_expires.post_id AND viwcc_date_expires.meta_key = 'date_expires' )" ;
		}
		return $join;
	}
	public static function posts_where($where, $query){
		if (!empty($query->query_vars['viwcc_get_available_coupons']) ){
			$now                  = current_time( 'timestamp' );
			$searches      = array(
				"viwcc_date_expires.post_id IS NULL" ,
				"( viwcc_date_expires.meta_key = 'date_expires' AND viwcc_date_expires.meta_value IS NULL )" ,
				"( viwcc_date_expires.meta_key = 'date_expires' AND viwcc_date_expires.meta_value = '' )" ,
				 "( viwcc_date_expires.meta_key = 'date_expires' AND viwcc_date_expires.meta_value > $now )" ,
			);
			$where .=' AND (' . implode( ' OR ', $searches ) . ')';
		}
		return $where;
	}
}