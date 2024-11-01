jQuery(document).ready(function ($) {
    var coupon_amount = $('#coupon_amount').val() || 0,
        discount_type;
    switch ($('#discount_type').val()) {
        case 'percent':
            discount_type = '%';
            break;
        case 'fixed_cart':
            discount_type = vi_wcc_coupon.currency;
            break;
        case 'fixed_product':
            discount_type = vi_wcc_coupon.currency;
            break;
    }
    //title

    $('#coupon_amount').change(function () {
        if (!$('#wcc_custom_coupon_title').val()) {
            coupon_amount = $(this).val() || 0;
            let coupon_term = coupon_amount + discount_type;
            $('#wcc_custom_coupon_title').val(coupon_term + ' OFF');
            $('.vi_wcc_woo_custumer_coupon-title').html(coupon_term + ' OFF');
        }
    });
    $('#discount_type').change(function () {
        if (!$('#wcc_custom_coupon_title').val()) {
            switch ($(this).val()) {
                case 'percent':
                    discount_type = '%';
                    break;
                case 'fixed_cart':
                    discount_type = vi_wcc_coupon.currency;
                    break;
                case 'fixed_product':
                    discount_type = vi_wcc_coupon.currency;
                    break;
            }
            let coupon_term = coupon_amount + discount_type;
            $('#wcc_custom_coupon_title').val(coupon_term + ' OFF');
            $('.vi_wcc_woo_custumer_coupon-title').html(coupon_term + ' OFF');
        }
    });
    //terms

    $('#minimum_amount').change(function () {
        $('#wcc_custom_coupon_terms').attr('value', 'Minimum spend ' + $(this).val() + vi_wcc_coupon.currency + ' Maximum spend ' + $('#maximum_amount').val() + vi_wcc_coupon.currency);
        $('.vi_wcc_woo_custumer_coupon-des').html('Minimum spend ' + $(this).val() + vi_wcc_coupon.currency + ' Maximum spend ' + $('#maximum_amount').val() + vi_wcc_coupon.currency);
    });
    $('#minimum_amount').mousemove(function () {

    });
    $('#maximum_amount').change(function () {
        $('#wcc_custom_coupon_terms').attr('value', 'Minimum spend ' + $('#minimum_amount').val() + vi_wcc_coupon.currency + ' Maximum spend ' + $(this).val() + vi_wcc_coupon.currency);
        $('.vi_wcc_woo_custumer_coupon-des').html('Minimum spend ' + $('#minimum_amount').val() + vi_wcc_coupon.currency + ' Maximum spend ' + $(this).val() + vi_wcc_coupon.currency);
    });


    //chang template
    if (!$('.vi_wcc_woo_custumer_coupon-title').html()) {
        if (!$('#wcc_custom_coupon_title').val()) {
            $('#wcc_custom_coupon_title').val('{' + coupon_amount + discount_type + '}');
        } else {
            $('.vi_wcc_woo_custumer_coupon-title').html($('#wcc_custom_coupon_title').val());
        }
    }
    $('#expiry_date').change(function () {
        if ($(this).val()) {
            $('.vi_wcc_coupon_content_expire span').html('Expires on: ' + $(this).val());
        }

    });

    $('#wcc_custom_coupon_title').change(function () {
        if (!$(this).val()) {
            $('.vi_wcc_woo_custumer_coupon-title').html('No title');
        } else {
            $('.vi_wcc_woo_custumer_coupon-title').html($(this).val());
        }
    });
    $('#wcc_custom_coupon_terms').change(function () {
        $('.vi_wcc_woo_custumer_coupon-des').html($(this).val());
    });

});