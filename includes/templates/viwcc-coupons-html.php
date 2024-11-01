<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! is_array( $coupons ) || empty( $coupons ) ) {
	return;
}
$settings   = $settings ?: VI_WOO_CUSTOMER_COUPONS_Data::get_instance();
$wrap_class = 'viwcc-coupons-wrap' . $wrap_class;
printf( '<div class="%1s">', esc_attr( $wrap_class ) );
if ( $coupon_message ) {
	printf( '<p class="viwcc-coupons-notice">%1$s</p>', wp_kses_post( $coupon_message ) );
}
printf( '<div class="viwcc-coupons-content-wrap"><div class="viwcc-coupons-content">' );
$template    = $settings->get_params( 'wcc_template' ) ?: 1;
$date_format = $settings->get_params( 'wcc_date_format' ) ?: get_option( 'date_format', 'F d, Y' );
foreach ( $coupons as $coupon ) {
	$class  = array();
	$coupon = new WC_Coupon( $coupon );
	$enable = $coupon->get_meta( 'wcc_coupon_enable' ) ?: $settings->get_params( 'show_all' );
	if ( ! $enable || $enable === 'no' ) {
		continue;
	}
	$show_date = $coupon->get_meta( 'viwcc_date_enable' ) ?: $settings->get_params( 'coupon_date' );
	if ( $show_date ) {
		if ( $coupon->get_date_expires() ) {
			$date_expires = $coupon->get_date_expires()->getTimestamp();
			$now          = $now ?? current_time( 'timestamp' );
			$valid_time   = $date_expires - $now;
			if ( $valid_time <= 0) {
				/* translators: %1s: coupon expired date */
				$coupon_date = sprintf( esc_html__( 'Expired on: %1s', 'woo-customer-coupons' ), date( $date_format, $date_expires ) );// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			}elseif ( $valid_time < 86400 ) {
				$coupon_date = esc_html__( 'Use in: Today', 'woo-customer-coupons' );
			} elseif ( $valid_time <= 86400 * 2 ) {
				$coupon_date = esc_html__( 'Use in: 2 days', 'woo-customer-coupons' );
			} else {
				/* translators: %1s: coupon valid date */
				$coupon_date = sprintf( esc_html__( 'Valid Till: %1s', 'woo-customer-coupons' ), date( $date_format, $date_expires - 86400 ) );// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			}
		} else {
			$coupon_date = esc_html__( 'Unlimited time', 'woo-customer-coupons' );
		}
	} else {
		$coupon_date = '';
	}
	$coupon_title = $coupon->get_meta( 'wcc_custom_coupon_title' ) ?: $settings->get_params( 'coupon_title' );
	$coupon_des   = $coupon->get_meta( 'wcc_custom_coupon_terms' ) ?: $settings->get_params( 'coupon_desc' );
	$min_amount   = $coupon->get_minimum_amount();
	$max_amount   = $coupon->get_maximum_amount();
	$coupon_code  = $coupon->get_code();
	$min_spend    = wc_price( $min_amount );
	$max_spend    = $max_amount ? wc_price( $max_amount ) : esc_html__( 'no maximum', 'woo-customer-coupons' );
	if ( $coupon->get_discount_type() === 'percent' ) {
		$coupon_value = $coupon->get_amount() . '%';
	} else {
		$coupon_value = wc_price( $coupon->get_amount() );
	}
	$coupon_title = str_replace(
		array( '{coupon_code}', '{coupon_value}', '{min_spend}', '{max_spend}' ),
		array( $coupon_code, $coupon_value, $min_spend, $max_spend ),
		$coupon_title
	);
	$coupon_des   = str_replace(
		array( '{coupon_code}', '{coupon_value}', '{min_spend}', '{max_spend}' ),
		array( $coupon_code, $coupon_value, $min_spend, $max_spend ),
		$coupon_des
	);
	$available    = true;
	if ( ! empty( $check_available ) && $available && isset( WC()->cart ) && ( ! is_admin() || wp_doing_ajax() ) ) {
		$discount       = $discount ?? new WC_Discounts( WC()->cart );
		$available      = ! is_wp_error( $discount->is_coupon_valid( $coupon ) );
		$applied_coupon = $applied_coupon ?? WC()->cart->get_applied_coupons();
		if ( $available && is_array( $applied_coupon ) && in_array( $coupon_code, $applied_coupon ) ) {
			$class[] = 'viwcc_hidden';
		}
	}
	wc_get_template( 'viwcc-template-' . $template . '-html.php',
		array(
			'coupon_date'  => apply_filters( 'viwcc-coupon-get-date', $coupon_date, $coupon ),
			'coupon_des'   => $coupon_des,
			'coupon_title' => $coupon_title,
			'coupon_code'  => $coupon_code,
			'available'    => $available,
			'class'        => $class,
			'coupon'       => $coupon,
		),
		'woo-customer-coupons' . DIRECTORY_SEPARATOR,
		WOO_CUSTOM_COUPONS_TEMPLATES );
}
printf( '</div></div></div>' );
?>