jQuery(document).ready(function ($) {
    'use strict';
    $('.vi-ui.vi-ui-main.tabular.menu .item').vi_tab({history: true, historyType: 'hash'});
    $('.vi-ui.dropdown').unbind().dropdown();
    $('.vi-ui.checkbox').unbind().checkbox();
    $('input[type="checkbox"]').unbind().on('change', function () {
        if ($(this).prop('checked')) {
            $(this).parent().find('input[type="hidden"]').val('1');
            if ($(this).is('#viwcc-coupon_date-checkbox')) {
                $('.viwcc-coupon-date').removeClass('vi_wcc_hidden');
            }
        } else {
            $(this).parent().find('input[type="hidden"]').val('');
            if ($(this).is('#viwcc-coupon_date-checkbox')) {
                $('.viwcc-coupon-date').addClass('vi_wcc_hidden');
            }
        }
    });
    $('input[type="number"]').on('change', function () {
        switch ($(this).attr('id')) {
            case 'wcc_button_shop_now_size':
                $('.wcc-button-shop-now').css({fontSize: $(this).val() + 'px'});
                break;
            case 'wcc_button_shop_now_border_radius':
                $('.wcc-button-shop-now').css({borderRadius: $(this).val() + 'px'});
                break;
        }
    });
    $('#wcc_button_shop_now_url').on('change', function () {
        $('.wcc-button-shop-now').attr('href', $(this).val());
    });
    $('.wcc_template').dropdown({
        onChange: function (val) {
            $('.vi_wcc_template_style, .viwcc-coupon-preview').addClass('vi_wcc_hidden');
            $('.vi_wcc_template_style.vi_wcc_template_' + val + ', .viwcc-coupon-preview-' + val).removeClass('vi_wcc_hidden');
        }
    });
    $('.color-field').each(function () {
        $(this).css({backgroundColor: $(this).val()});
    }).unbind().minicolors({
        change: function (value, opacity) {
            let field = $(this).parent().find('.color-field');
            field.css({backgroundColor: value});
            switch (field.attr('id')) {
                case 'wcc_button_shop_now_bg_color':
                    $('.wcc-button-shop-now').css({backgroundColor: value});
                    break;
                case 'wcc_button_shop_now_color':
                    $('.wcc-button-shop-now').css({color: value});
                    break;
                case 'wcc-temple-four-background-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4').css({backgroundColor: value});
                    break;
                case 'wcc-temple-four-title-background-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-title').css({backgroundColor: value});
                    break;
                case 'wcc-temple-four-title-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-title').css({color: value});
                    break;
                case 'wcc-temple-four-term-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-content').css({color: value});
                    break;
                case 'wcc-temple-four-border-color':
                    let border_type = $('.wcc-temple-four-border-type').dropdown('get value');
                    if (border_type === 'none') {
                        $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4').css({boxShadow: 'inset 0 0 10px ' + value});
                    } else {
                        $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-content').css({borderStyle: border_type, borderColor: value, borderWidth: '0 1px 1px'});
                    }
                    break;
                case 'wcc-temple-three-background-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-3').css({backgroundColor: value});
                    break;
                case 'wcc-temple-three-background-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-3').css({backgroundColor: value});
                    break;
                case 'wcc-temple-three-title-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-3 .viwcc-coupon-content .viwcc-coupon-title').css({color: value});
                    break;
                case 'wcc-temple-three-term-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-3 .viwcc-coupon-content .viwcc-coupon-des').css({color: value});
                    break;
                case 'wcc-temple-three-expire-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-3 .viwcc-coupon-content .viwcc-coupon-date').css({color: value});
                    break;
                case 'wcc-temple-three-border-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-3').css({border: '1px ' + $('.wcc-temple-three-border-type').dropdown('get value') + ' ' + value});
                    break;
                case 'wcc-temple-two-background-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-2').css({backgroundColor: value});
                    break;
                case 'wcc-temple-two-title-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-2 .viwcc-coupon-content').css({color: value});
                    break;
                case 'wcc-temple-two-border-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-2 .viwcc-coupon-content').css({border: '1px dotted ' + value});
                    break;
                case 'wcc-temple-one-background-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content').css({backgroundColor: value});
                    break;
                case 'wcc-temple-one-content-background-color1':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content').css({backgroundImage: 'linear-gradient(315deg,' + value + ', ' + $('#wcc-temple-one-content-background-color2').val() + ' 85%)'});
                    break;
                case 'wcc-temple-one-content-background-color2':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content').css({backgroundImage: 'linear-gradient(315deg,' + $('#wcc-temple-one-content-background-color1').val() + ', ' + value + ' 85%)'});
                    break;
                case 'wcc-temple-one-content-title-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content .viwcc-coupon-title').css({color: value});
                    break;
                case 'wcc-temple-one-content-des-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content .viwcc-coupon-des').css({color: value});
                    break;
                case 'wcc-temple-one-content-expire-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-content .viwcc-coupon-date').css({color: value});
                    break;
                case 'wcc-temple-one-button-background-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-button').css({backgroundColor: value});
                    break;
                case 'wcc-temple-one-button-text-color':
                    $('.viwcc-coupon-wrap.viwcc-coupon-wrap-1 .viwcc-coupon-button').css({color: value});
                    break;
            }
        },
        animationSpeed: 50,
        animationEasing: 'swing',
        changeDelay: 0,
        control: 'wheel',
        defaultValue: '',
        format: 'rgb',
        hide: null,
        hideSpeed: 100,
        inline: false,
        keywords: '',
        letterCase: 'lowercase',
        opacity: true,
        position: 'bottom left',
        show: null,
        showSpeed: 100,
        theme: 'default',
        swatches: []
    });
    $('.wcc-temple-three-border-type ').dropdown({
        onChange: function (val) {
            $('.viwcc-coupon-wrap.viwcc-coupon-wrap-3').css({border: '1px ' + val + ' ' + $('#wcc-temple-three-border-color').val()});
        }
    });
    $('.wcc-temple-four-border-radius ').dropdown({
        onChange: function (val) {
            $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4').css({borderRadius: val});
            $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-content').css({borderBottomLeftRadius: val, borderBottomRightRadius: val});
        }
    });
    $('.wcc-temple-four-border-type').dropdown({
        onChange: function (val) {
            let border_color = $('#wcc-temple-four-border-color').val();
            if (val === 'none') {
                $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4').css({boxShadow: 'inset 0 0 10px ' + border_color});
            } else {
                $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4').css({boxShadow: 'unset'});
                $('.viwcc-coupon-wrap.viwcc-coupon-wrap-4 .viwcc-coupon-content').css({borderStyle: val, borderColor: border_color, borderWidth: '0 1px 1px'});
            }
        }
    });
    $(document).on('keyup', '#viwcc-coupon_title, #viwcc-coupon_desc', function () {
        let val = $(this).val();
        if (val) {
            val = val.replaceAll('{coupon_code}', viwcc_admin_settings.coupon_code)
                .replaceAll('{coupon_value}', viwcc_admin_settings.coupon_value)
                .replaceAll('{min_spend}', viwcc_admin_settings.min_spend)
                .replaceAll('{max_spend}', viwcc_admin_settings.max_spend);
        }
        if ($(this).attr('id') === 'viwcc-coupon_title') {
            $('.viwcc-coupon-title').html(val);
        } else {
            $('.viwcc-coupon-des').html(val);
        }
    });
    /*preview email*/
    $('.preview-emails-html-overlay').on('click', function () {
        $('.preview-emails-html-container').addClass('vi_wcc_hidden');
    })
    $('.wcc-preview-emails-button').on('click', function () {
        $(this).html('Please wait...');
        $.ajax({
            url: viwcc_admin_settings.ajax_url,
            type: 'GET',
            dataType: 'JSON',
            data: {
                action: 'wcc_preview_emails',
                nonce: $('#_vi_wcc_option_nonce').val(),
                heading: $('#wcc_mail_heading').val(),
                content: tinyMCE.get('wcc_mail_content') ? tinyMCE.get('wcc_mail_content').getContent() : $('#wcc_mail_content').val(),
                button_shop_title: $('#wcc_button_shop_now_title').val(),
                button_shop_url: $('#wcc_button_shop_now_url').val(),
                button_shop_bg_color: $('#wcc_button_shop_now_bg_color').val(),
                button_shop_color: $('#wcc_button_shop_now_color').val(),
                button_shop_size: $('#wcc_button_shop_now_size').val(),
                button_shop_border_radius: $('#wcc_button_shop_now_border_radius').val(),
            },
            success: function (response) {
                $('.wcc-preview-emails-button').html('Preview emails');
                if (response) {
                    $('.preview-emails-html').html(response.html);
                    $('.preview-emails-html-container').removeClass('vi_wcc_hidden');
                }
            },
            error: function (err) {
                $('.wcb-preview-emails-button').html('Preview emails');
            }
        })
    });
    //reset settings
    $('.vi_wcc_settings_default').unbind().click(function () {
        if (confirm(viwcc_admin_settings.setting_default)) {
            $(this).attr('type', 'submit');
        }
    });
});