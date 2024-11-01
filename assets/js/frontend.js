jQuery(document).ready(function ($) {
    'use strict';
    $(document.body).on('removed_coupon removed_coupon_in_checkout', function (e, coupon) {
        $(`.viwcc-coupon-wrap[data-coupon_code="${coupon}"]`).removeClass('viwcc_hidden');
    });
    $(document).on('click', '.viwcc-coupon-wrap-available:not(.viwcc-coupon-wrap-loading)', function (e) {
        e.preventDefault();
        e.stopPropagation();
        let coupon = $(this), coupon_code = $(this).data('coupon_code');
        if (!coupon_code) {
            return false;
        }
        $.ajax({
            url: viwcc_param.wc_ajax_url.toString().replace('%%endpoint%%', 'viwcc_apply_coupon'),
            type: "post",
            data: {
                coupon_code: coupon_code,
                viwcc_nonce: viwcc_param.viwcc_nonce
            },
            beforeSend: function () {
                coupon.addClass('viwcc-coupon-wrap-loading');
            },
            success: function (response) {
                if (response.message) {
                    $('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();
                    if ($('.woocommerce-notices-wrapper').length && !$('.vi-wcaio-sidebar-cart-checkout-wrap').length) {
                        $('.woocommerce-notices-wrapper').last().html(response.message);
                        $.scroll_to_notices($('.woocommerce-notices-wrapper'));
                    } else {
                        $(document.body).trigger('villatheme_show_message', [response.message, [response.status], '', false, 4500]);
                    }
                }
                if (typeof wc_cart_params !== 'undefined') {
                    $(document.body).trigger('applied_coupon', [coupon_code]);
                    if ($('[name="update_cart"]').length && $('[name="update_cart"]').closest('form').length) {
                        $('[name="update_cart"]').removeAttr('disabled').trigger('click');
                    } else {
                        location.reload();
                    }
                } else if (typeof wc_checkout_params !== 'undefined') {
                    $(document.body).trigger('applied_coupon_in_checkout', [coupon_code]);
                    $(document.body).trigger('update_checkout', {update_shipping_method: false});
                } else {
                    $(document.body).trigger('applied_coupon', [coupon_code]);
                }
                coupon.removeClass('viwcc-coupon-wrap-loading').addClass('viwcc_hidden');
            },
            error: function (err) {
                console.log(err);
                coupon.removeClass('viwcc-coupon-wrap-loading');
            }
        });
    });
});