jQuery(document).ready(function ($) {
    'use strict';
    $(document).on('click', '.viwcc-customer-coupon-column .action', function () {
        let button = $(this);
        button.addClass('loading');
        $.ajax({
            url: viwcc_admin_coupons.ajax_url,
            type: 'post',
            dataType: 'JSON',
            data: {
                action: 'viwcc_coupon_change_status',
                coupon_id: button.closest('.viwcc-customer-coupon-column').data('coupon_id') || '',
                type: button.hasClass('trash') ? 'trash' : 'enable',
                viwcc_nonce: viwcc_admin_coupons.nonce,
            },
            success: function (response) {
                button.removeClass('loading');
                if (response.message) {
                    $(document.body).trigger('villatheme_show_message', [response.message, [response.status], '', false, 4500]);
                }
                if (response.status === 'error') {
                    return false;
                }
                button.closest('.viwcc-customer-coupon-column').find('.action').addClass('vi_wcc_hidden');
                if (response.display) {
                    button.closest('.viwcc-customer-coupon-column').find('.status').html('<i class="dashicons dashicons-saved">');
                    button.closest('.viwcc-customer-coupon-column').find('.trash').removeClass('vi_wcc_hidden');
                } else {
                    button.closest('.viwcc-customer-coupon-column').find('.status').html('-');
                    button.closest('.viwcc-customer-coupon-column').find('.enable').removeClass('vi_wcc_hidden');
                }
            },
            error: function (err) {
                console.log(err)
                $(document.body).trigger('villatheme_show_message', [err.responseText == -1 ? err.statusText : err.responseText, ['error'], '', false, 4500]);
                button.removeClass('loading');
            }
        });
    });
    $(document).on('keyup change', '#wcc_custom_coupon_title, #wcc_custom_coupon_terms', function () {
        let val = $(this).val() || ($(this).attr('id') === 'wcc_custom_coupon_title' ? viwcc_admin_coupons.coupon_title : viwcc_admin_coupons.coupon_des);
        if (val) {
            let coupon_value = $('#coupon_amount').val() || 0;
            if ($('#discount_type').val() === 'percent') {
                coupon_value += '%';
            } else {
                coupon_value = viwcc_admin_coupons.wc_price_format.replace('%2$s', coupon_value)
            }
            val = val.replaceAll('{coupon_code}', $('[name="post_title"]').val())
                .replaceAll('{coupon_value}', coupon_value)
                .replaceAll('{min_spend}', viwcc_admin_coupons.wc_price_format.replace('%2$s', $('#minimum_amount').val() || 0))
                .replaceAll('{max_spend}', $('#maximum_amount').val() !== '' ? viwcc_admin_coupons.wc_price_format.replace('%2$s', $('#maximum_amount').val()) : viwcc_admin_coupons.no_maximum);
        }
        if ($(this).attr('id') === 'wcc_custom_coupon_title') {
            $('.viwcc-coupon-title').html(val);
        } else {
            $('.viwcc-coupon-des').html(val);
        }
    });
    $(document).on('click', '.viwcc_customer_coupon_tab', function () {
        $('#wcc_custom_coupon_title, #wcc_custom_coupon_terms').trigger('change');
    });
});