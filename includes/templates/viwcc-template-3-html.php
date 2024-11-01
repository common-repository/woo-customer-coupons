<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( empty( $coupon ) ) {
	return;
}
$class   = $class ?? array();
$class[] = 'viwcc-coupon-wrap viwcc-coupon-wrap-3 viwcc-coupon-button';
if ( ! empty( $available ) ) {
	$class[] = 'viwcc-coupon-wrap-available';
}
?>
<div class="<?php echo esc_attr( trim( implode( ' ', $class ) ) ) ?>" data-coupon_code="<?php echo esc_attr( $coupon_code ) ?>"
     title="<?php esc_attr_e( 'Click to apply this coupon.', 'woo-customer-coupons' ) ?>">
    <div class="viwcc-coupon-content">
        <div class="viwcc-coupon-title"> <?php echo wp_kses_post( $coupon_title ); ?></div>
        <div class="viwcc-coupon-des"> <?php echo wp_kses_post( $coupon_des ); ?></div>
        <div class="viwcc-coupon-date"><?php echo wp_kses_post( $coupon_date ); ?></div>
    </div>
</div>
