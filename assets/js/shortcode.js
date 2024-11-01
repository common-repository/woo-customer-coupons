jQuery(document).ready(function ($) {
    var isMobile = false;

    if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
        || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0, 4))) {
        isMobile = true;
    }
    $('.vi_wcc_coupon_terms').each(function () {
        $(this).parent().find('.vi_wcc_woo_custumer_coupon').css({'pointer-events': 'none'});
    });
    $(document).on('click','.vi_wcc_woo_custumer_coupon-wrap:not(.vi_wcc_woo_custumer_coupon-wrap-clicking)',function () {
        if ($(this).find('.vi_wcc_coupon_terms').length) {
            return false;
        }
        console.log($(this));
        $(this).addClass('vi_wcc_woo_custumer_coupon-wrap-clicking').find('button[name="vi_wcc_woo_customer_coupon_button_click"]').trigger('click');
    });
    $(document).on('click','button[name="vi_wcc_woo_customer_coupon_button_click"]',function (e) {
        if ($(this).closest('.vi_wcc_woo_custumer_coupon-wrap').find('.vi_wcc_coupon_terms').length) {
            return false;
        }
        if (!coupon_wcc_ajax.is_cart) {
            let coupon_item = $(this).closest('.vi_wcc_woo_custumer_coupon-wrap');
            let coupon_load = '.vi_wcc_lds-dual-ring-' + $(this).val();
            let coupon_code = $(this).val();
            $.ajax({
                url: coupon_wcc_ajax.ajax_url,
                type: "post",
                data: {action: 'vi_wcc_apply_coupon', code: coupon_code},
                beforeSend: function (result) {
                    $(coupon_load).addClass('vi_wcc_lds-dual-ring-show');
                    console.log(coupon_code);
                    console.log(coupon_item);
                },
                complete: function (result) {
                    $(coupon_load).removeClass('vi_wcc_lds-dual-ring-show');
                },
                success: function (result) {
                    console.log(result);
                    if (result.shop_url && result.status === 'error') {
                        window.location = result.shop_url;
                    } else {
                        $('.woocommerce-message').remove();
                        $('.vi_wcc_coupon-notices').html(result.message);
                        if (isMobile) {
                            $(coupon_item).parent().parent().removeClass('slick-slide-show').addClass('slick-slide-disable');
                            console.log($(coupon_item).parent().parent().attr('class'))
                        } else {
                            $(coupon_item).hide();
                        }
                        $(document.body).trigger('update_checkout');
                        $('.vi_wcc_woo_custumer_coupon-wrap-clicking').removeClass('vi_wcc_woo_custumer_coupon-wrap-clicking');
                    }
                },
                error: function (err) {
                    console.log(err.responseText.replace(/<\/?[^>]+(>|$)/g, ""));
                }
            });
            e.stopPropagation();
        }else if (!$(this).closest('form').length && $(this).val()) {
            $('[name=coupon_code]').val($(this).val());
            $('[name=apply_coupon]').trigger('click');
            $(this).closest('.vi_wcc_woo_custumer_coupon-wrap').remove();
        }
    });
    $('button[name="apply_coupon"]').click(function () {
        var a = $('.vi_wcc_lds-dual-ring-' + $('input[name="coupon_code"]').val()).closest('.vi_wcc_woo_custumer_coupon-wrap');

        if (a.length) {
            if (isMobile) {
                a.parent().parent().removeClass('slick-slide-show ').addClass('slick-slide-disable');
            } else {
                a.hide();
            }
        }
    });
    $(document).on('click', 'a.woocommerce-remove-coupon',
        function (evt) {
            // evt.preventDefault();
            var coupon = $(evt.currentTarget).attr('data-coupon');
            var a = $('.vi_wcc_lds-dual-ring-' + coupon).closest('.vi_wcc_woo_custumer_coupon-wrap');

            if (a.length) {
                if (isMobile) {
                    a.parent().parent().removeClass('slick-slide-disable').addClass('slick-slide-show ');
                } else {
                    a.show();
                }
            }

        });


    function vi_wcc_slick_slide_coupon() {
        var slide_width = $('.vi_wcc-account-list-coupon-content').width();

        if (slide_width >= 825) {
            $('.vi_wcc-account-list-coupon-content').slick({
                prevArrow: "<button type='button' class='slick-prev '><span class=\"dashicons dashicons-arrow-left-alt2\"></span></button>",
                nextArrow: "<button type='button' class='slick-next '><span class=\"dashicons dashicons-arrow-right-alt2\"></span></button>",
                dots: false,
                autoplay: true,

                slidesToShow: 4,
                slidesToScroll: 1,
                arrows: true,
                autoplaySpeed: 5000,
                cssEase: 'linear'
            });
        }
        if (slide_width >= 605 && slide_width < 825) {
            $('.vi_wcc-account-list-coupon-content').slick({
                prevArrow: "<button type='button' class='slick-prev '><span class=\"dashicons dashicons-arrow-left-alt2\"></span></button>",
                nextArrow: "<button type='button' class='slick-next '><span class=\"dashicons dashicons-arrow-right-alt2\"></span></button>",
                dots: false,
                autoplay: true,

                slidesToShow: 3,
                slidesToScroll: 1,
                arrows: true,
                autoplaySpeed: 5000,
                cssEase: 'linear'
            });
        }
        if (slide_width >= 420 && slide_width < 605) {
            $('.vi_wcc-account-list-coupon-content').slick({
                prevArrow: "<button type='button' class='slick-prev '><span class=\"dashicons dashicons-arrow-left-alt2\"></span></button>",
                nextArrow: "<button type='button' class='slick-next '><span class=\"dashicons dashicons-arrow-right-alt2\"></span></button>",
                dots: false,
                autoplay: true,

                slidesToShow: 2,
                slidesToScroll: 1,
                arrows: true,
                autoplaySpeed: 5000,
                cssEase: 'linear'
            });
        }
        if (slide_width < 420) {
            $('.vi_wcc-account-list-coupon-content').slick({
                prevArrow: "<button type='button' class='slick-prev '><span class=\"dashicons dashicons-arrow-left-alt2\"></span></button>",
                nextArrow: "<button type='button' class='slick-next '><span class=\"dashicons dashicons-arrow-right-alt2\"></span></button>",
                dots: false,
                autoplay: true,

                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: true,
                autoplaySpeed: 5000,
                cssEase: 'linear'
            });
        }
        $('.slick-slide').addClass('slick-slide-show');


    }

    if (isMobile) {
        vi_wcc_slick_slide_coupon();
        if (coupon_wcc_ajax.is_cart) {
            $(document).ajaxComplete(function () {
                if ($('.vi_wcc-account-list-coupon-content').hasClass('slick-initialized')) {
                    $('.vi_wcc-account-list-coupon-content').slick('unslick');
                }
                vi_wcc_slick_slide_coupon();
            });
        }
    }

});