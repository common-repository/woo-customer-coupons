<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_CUSTOM_COUPONS_Admin_Settings {
	protected $settings, $coupon_date, $coupon_code, $coupon_value, $min_spend, $max_spend;

	public function __construct() {
		$this->settings = VI_WOO_CUSTOMER_COUPONS_Data::get_instance();
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'save_data' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 999999 );
		/*preview email*/
		add_action( 'media_buttons', array( $this, 'preview_emails_button' ) );
		add_action( 'wp_ajax_wcc_preview_emails', array( $this, 'preview_emails_ajax' ) );
	}

	public function admin_menu() {
		add_submenu_page(
			'woocommerce-marketing',
			esc_html__( 'Customer Coupons', 'woo-customer-coupons' ),
			esc_html__( 'Customer Coupons', 'woo-customer-coupons' ),
			'manage_options',
			'woo_customer_coupons',
			array( $this, 'setting_callback' )
		);
	}

	public function preview_emails_button( $editor_id ) {
		if ( isset( $_REQUEST['_vi_wcc_option_nonce'] ) && ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_vi_wcc_option_nonce'] ) ), '_vi_wcc_option_nonce_action' ) ) {
			return;
		}
		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
		if ( $page === 'woo_customer_coupons' ) {
			if ( $editor_id === 'wcc_mail_content' ) {
				?>
                <span class="button wcc-preview-emails-button"><?php esc_html_e( 'Preview emails', 'woo-customer-coupons' ) ?></span>
				<?php
			}
		}
	}

	public function preview_emails_ajax() {
		check_ajax_referer( '_vi_wcc_option_nonce_action', 'nonce' );
		$arg               = array();
		$arg['content']    = isset( $_GET['content'] ) ? wp_kses_post( wp_unslash( $_GET['content'] ) ) : '';
		$arg['heading']    = isset( $_GET['heading'] ) ? sanitize_text_field( wp_unslash( $_GET['heading'] ) ) : '';
		$button_shop_title = isset( $_GET['button_shop_title'] ) ? wp_kses_post( wp_unslash( $_GET['button_shop_title'] ) ) : '';
		$button_shop_url   = isset( $_GET['button_shop_url'] ) ? sanitize_text_field( wp_unslash( $_GET['button_shop_url'] ) ) : '';
		add_filter( 'woocommerce_email_styles', function ( $css ) {
			$button_shop_color         = isset( $_GET['button_shop_color'] ) ? sanitize_text_field( wp_unslash( $_GET['button_shop_color'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$button_shop_bg_color      = isset( $_GET['button_shop_bg_color'] ) ? sanitize_text_field( wp_unslash( $_GET['button_shop_bg_color'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$button_shop_size          = isset( $_GET['button_shop_size'] ) ? sanitize_text_field( wp_unslash( $_GET['button_shop_size'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$button_shop_border_radius = isset( $_GET['button_shop_border_radius'] ) ? sanitize_text_field( wp_unslash( $_GET['button_shop_border_radius'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$css                       .= $this->settings::get_email_style( $button_shop_bg_color, $button_shop_color, $button_shop_size, $button_shop_border_radius );
			return $css;
		}, PHP_INT_MAX, 1 );
		$button_shop_now = sprintf( '<a class="wcc-button-shop-now" href="%1s" target="_blank">%2s</a>', esc_url( $button_shop_url ), wp_kses_post( $button_shop_title ) );
		$coupon_value    = '10%';
		$coupon_code     = 'HAPPY';
		$coupon_title    = '10% OFF';
		$coupon_des      = 'Min. Spend $100';
		$date_expires    = strtotime( '+30 days' );
		$site_title      = get_bloginfo( 'name' );
		$date_format     = $this->settings->get_params( 'wcc_date_format' ) ?: get_option( 'date_format', 'F d, Y' );
		$date_expires_t  = date_i18n( $date_format, ( $date_expires ) );
		$last_valid_date = date_i18n( $date_format, ( $date_expires - 86400 ) );
		$coupon_code     = '<span style="font-size: x-large;">' . strtoupper( $coupon_code ) . '</span>';
		$content         = array();
		foreach ( $arg as $key => $value ) {
			$value           = str_replace(
				array( '{coupon_code}', '{coupon_title}', '{coupon_des}', '{coupon_value}', '{date_expires}', '{last_valid_date}', '{site_title}', '{shop_now}' ),
				array( $coupon_code, $coupon_title, $coupon_des, $coupon_value, $date_expires_t, $last_valid_date, $site_title, $button_shop_now ),
				$value
			);
			$content[ $key ] = $value;
		}
		// load the mailer class
		$mailer = WC()->mailer();
		// create a new email
		$email = new WC_Email();
		// wrap the content with the email template and then add styles
		$message = $email->style_inline( $mailer->wrap_message( $content['heading'], $content['content'] ) );
		// print the preview email
		wp_send_json(
			array(
				'html' => $message,
			)
		);
	}

	public function setting_callback() {
		$this->settings = VI_WOO_CUSTOMER_COUPONS_Data::get_instance( true );
		?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Customer Coupons for WooCommerce', 'woo-customer-coupons' ) ?></h2>
            <form class="vi-ui small form" method="post">
				<?php wp_nonce_field( '_vi_wcc_option_nonce_action', '_vi_wcc_option_nonce' ) ?>
                <div class="vi-ui top tabular vi-ui-main attached menu">
                    <a class="item active" data-tab="general"><?php esc_html_e( 'General', 'woo-customer-coupons' ); ?></a>
                    <a class="item" data-tab="email"><?php esc_html_e( 'Email', 'woo-customer-coupons' ); ?></a>
                    <a class="item" data-tab="design"><?php esc_html_e( 'Design', 'woo-customer-coupons' ); ?></a>
                </div>
                <div class="vi-ui bottom attached tab segment active" data-tab="general">
					<?php
					$show_all            = $this->settings->get_params( 'show_all' );
					$show_on_cart        = $this->settings->get_params( 'show_on_cart' );
					$show_on_checkout    = $this->settings->get_params( 'show_on_checkout' );
					$single_pro_page_pos = $this->settings->get_params( 'wcc_coupon-single_pro_page_pos' );
					$wcc_date_format     = $this->settings->get_params( 'wcc_date_format' );
					$date_format         = array(
						'0'      => esc_html__( 'Site\'s date format', 'woo-customer-coupons' ),
						'F j, Y' => esc_html__( 'F j, Y', 'woo-customer-coupons' ),
						'Y-m-d'  => esc_html__( 'Y-MM-DD', 'woo-customer-coupons' ),
						'd-m-Y'  => esc_html__( 'DD-MM-Y', 'woo-customer-coupons' ),
						'm/d/Y'  => esc_html__( 'MM/DD/YY', 'woo-customer-coupons' ),
						'd/m/Y'  => esc_html__( 'DD/MM/YY', 'woo-customer-coupons' ),
					);
					$single_position     = array(
						'0'  => esc_html__( 'Not show', 'woo-customer-coupons' ),
						'5'  => esc_html__( 'Before title', 'woo-customer-coupons' ),
						'10' => esc_html__( 'After title', 'woo-customer-coupons' ),
						'20' => esc_html__( 'After price', 'woo-customer-coupons' ),
						'30' => esc_html__( 'Before cart', 'woo-customer-coupons' ),
						'40' => esc_html__( 'After cart', 'woo-customer-coupons' ),
						'50' => esc_html__( 'After list category', 'woo-customer-coupons' ),
					);
					?>
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="viwcc-show_all-checkbox"><?php esc_html_e( 'Show all coupons', 'woo-customer-coupons' ); ?></label>
                            </th>
                            <td>
                                <div class="vi-ui checkbox toggle">
                                    <input type="hidden" name="show_all" id="viwcc-show_all" value="<?php echo esc_attr( $show_all ); ?>">
                                    <input type="checkbox" id="viwcc-show_all-checkbox" class="viwcc-show_all-checkbox"
										<?php checked( $show_all, 1 ); ?>><label></label>
                                </div>
                                <p class="description">
									<?php esc_html_e( 'Show all applicable coupons outside the frontend', 'woo-customer-coupons' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="viwcc-show_on_cart-checkbox"><?php esc_html_e( 'Show on cart', 'woo-customer-coupons' ); ?></label>
                            </th>
                            <td>
                                <div class="vi-ui checkbox toggle">
                                    <input type="hidden" name="show_on_cart" id="viwcc-show_on_cart" value="<?php echo esc_attr( $show_on_cart ); ?>">
                                    <input type="checkbox" id="viwcc-show_on_cart-checkbox" class="viwcc-show_on_cart-checkbox"
										<?php checked( $show_on_cart, 1 ); ?>><label></label>
                                </div>
                                <p class="description">
									<?php esc_html_e( 'Show all applicable coupons on the cart page', 'woo-customer-coupons' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="viwcc-show_on_checkout-checkbox"><?php esc_html_e( 'Show on checkout', 'woo-customer-coupons' ); ?></label>
                            </th>
                            <td>
                                <div class="vi-ui checkbox toggle">
                                    <input type="hidden" name="show_on_checkout" id="viwcc-show_on_checkout" value="<?php echo esc_attr( $show_on_checkout ); ?>">
                                    <input type="checkbox" id="viwcc-show_on_checkout-checkbox" class="viwcc-show_on_checkout-checkbox"
										<?php checked( $show_on_checkout, 1 ); ?>><label></label>
                                </div>
                                <p class="description">
									<?php esc_html_e( 'Show all applicable coupons on the checkout page', 'woo-customer-coupons' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="viwcc-single_pro_page_pos">
									<?php esc_html_e( 'Position on the single product page', 'woo-customer-coupons' ); ?>
                                </label>
                            </th>
                            <td>
                                <select name="wcc_coupon-single_pro_page_pos" class="viwcc-single_pro_page_pos vi-ui dropdown fluid" id="viwcc-single_pro_page_pos">
									<?php
									foreach ( $single_position as $k => $v ) {
										printf( '<option value="%1s" %2s>%3s</option>', esc_attr( $k ), wp_kses_post( selected( $k, $single_pro_page_pos ) ), esc_html( $v ) );
									}
									?>
                                </select>
                                <p class="description"><?php esc_html_e( 'Please choose the location that shows the coupon voucher', 'woo-customer-coupons' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="viwcc-wcc_date_format"><?php esc_html_e( 'Date format', 'woo-customer-coupons' ); ?></label>
                            </th>
                            <td>
                                <select name="wcc_date_format" class="viwcc-wcc_date_format vi-ui dropdown fluid" id="viwcc-wcc_date_format">
									<?php
									foreach ( $date_format as $k => $v ) {
										printf( '<option value="%1s" %2s>%3s</option>', esc_attr( $k ), wp_kses_post( selected( $k, $wcc_date_format ) ), esc_html( $v ) );
									}
									?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="vi-ui bottom attached tab segment" data-tab="email">
					<?php
					$send_email                        = $this->settings->get_params( 'send_email' );
					$wcc_mail_subject                  = $this->settings->get_params( 'wcc_send-mail-subject' );
					$wcc_mail_heading                  = $this->settings->get_params( 'wcc_mail_heading' );
					$wcc_mail_content                  = $this->settings->get_params( 'wcc_mail_content' );
					$wcc_button_shop_now_url           = $this->settings->get_params( 'wcc_button_shop_now_url' ) ?: wc_get_page_permalink( 'shop' );
					$wcc_button_shop_now_title         = $this->settings->get_params( 'wcc_button_shop_now_title' );
					$wcc_button_shop_now_bg_color      = $this->settings->get_params( 'wcc_button_shop_now_bg_color' );
					$wcc_button_shop_now_color         = $this->settings->get_params( 'wcc_button_shop_now_color' );
					$wcc_button_shop_now_size          = $this->settings->get_params( 'wcc_button_shop_now_size' );
					$wcc_button_shop_now_border_radius = $this->settings->get_params( 'wcc_button_shop_now_border_radius' );
					?>
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="viwcc-send_email-checkbox"><?php esc_html_e( 'Send email', 'woo-customer-coupons' ); ?></label>
                            </th>
                            <td>
                                <div class="vi-ui checkbox toggle">
                                    <input type="hidden" name="send_email" id="viwcc-send_email" value="<?php echo esc_attr( $send_email ); ?>">
                                    <input type="checkbox" id="viwcc-send_email-checkbox" class="viwcc-send_email-checkbox"
										<?php checked( $send_email, 1 ); ?>><label></label>
                                </div>
                                <p class="description">
									<?php esc_html_e( 'Settings for sending the individual email if you check the send email checkbox when public or updating coupon', 'woo-customer-coupons' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="vi_wcc_send-mail-subject"><?php esc_html_e( 'Email subject', 'woo-customer-coupons' ) ?></label>
                            </th>
                            <td>
                                <input type="text" name="wcc_send-mail-subject" id="vi_wcc_send-mail-subject" value="<?php echo wp_kses_post( $wcc_mail_subject ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="wcc_mail_heading"><?php esc_html_e( 'Email heading', 'woo-customer-coupons' ) ?></label>
                            </th>
                            <td>
                                <input type="text" name="wcc_mail_heading" id="wcc_mail_heading" value="<?php echo wp_kses_post( $wcc_mail_heading ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="wcc_mail_content"><?php esc_html_e( 'Email content', 'woo-customer-coupons' ) ?></label>
                            </th>
                            <td>
								<?php
								wp_editor( $wcc_mail_content, 'wcc_mail_content', array( 'editor_height' => 300 ) );
								?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Shortcode', 'woo-customer-coupons' ) ?></th>
                            <td>
								<?php
								$email_shortcode = array(
									'{site_title}'      => esc_html__( 'The title of your website.', 'woo-customer-coupons' ),
									'{coupon_code}'     => esc_html__( 'Discount coupon code will be sent to customer.', 'woo-customer-coupons' ),
									'{coupon_value}'    => esc_html__( 'The value of coupon, can be percentage or currency amount depending on coupon type.', 'woo-customer-coupons' ),
									'{coupon_title}'    => esc_html__( 'The coupon title that you set on the coupon edit page.', 'woo-customer-coupons' ),
									'{coupon_des}'      => esc_html__( 'The description of coupon that you set on the coupon edit page.', 'woo-customer-coupons' ),
									'{date_expires}'    => esc_html__( 'Expiry date of the coupon.', 'woo-customer-coupons' ),
									'{last_valid_date}' => esc_html__( 'The last day that the coupon is valid to use.', 'woo-customer-coupons' ),
									'{shop_now}'        => esc_html__( 'Button redirects to your site to use coupon.', 'woo-customer-coupons' ),
								);
								foreach ( $email_shortcode as $k => $v ) {
									printf( '<p class="description">%1s</p>', wp_kses_post( $k . ' - ' . $v ) );
									if ( $k === '{shop_now}' ) {
										printf( '<a class="wcc-button-shop-now" href="%s" target="_blank" >%s</a>',
											esc_url( $wcc_button_shop_now_url ?: get_bloginfo( 'url' ) ),
											wp_kses_post( $wcc_button_shop_now_title )
										);
									}
								}
								?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="wcc_button_shop_now_title"><?php esc_html_e( 'Button "Shop now" title', 'woo-customer-coupons' ) ?></label>
                            </th>
                            <td>
                                <input type="text" name="wcc_button_shop_now_title" id="wcc_button_shop_now_title"
                                       value="<?php echo wp_kses_post( $wcc_button_shop_now_title ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="wcc_button_shop_now_url"><?php esc_html_e( 'Button "Shop now" url', 'woo-customer-coupons' ) ?></label>
                            </th>
                            <td>
                                <input type="text" name="wcc_button_shop_now_url" id="wcc_button_shop_now_url"
                                       value="<?php echo wp_kses_post( $wcc_button_shop_now_url ) ?>">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="wcc_button_shop_now_bg_color"><?php esc_html_e( 'Button "Shop now" design', 'woo-customer-coupons' ) ?></label>
                            </th>
                            <td>
                                <div class="equal width fields">
                                    <div class="field">
                                        <label for="wcc_button_shop_now_bg_color"><?php esc_html_e( 'Background color', 'woo-customer-coupons' ); ?></label>
                                        <input type="text" name="wcc_button_shop_now_bg_color" id="wcc_button_shop_now_bg_color"
                                               class="color-field"
                                               value="<?php echo esc_attr( $wcc_button_shop_now_bg_color ); ?>">
                                    </div>
                                    <div class="field">
                                        <label for="wcc_button_shop_now_color"><?php esc_html_e( 'Color', 'woo-customer-coupons' ); ?></label>
                                        <input type="text" name="wcc_button_shop_now_color" id="wcc_button_shop_now_color"
                                               class="color-field" value="<?php echo esc_attr( $wcc_button_shop_now_color ); ?>">
                                    </div>
                                </div>
                                <div class="equal width fields">
                                    <div class="field">
                                        <label for="wcc_button_shop_now_size"><?php esc_html_e( 'Font size', 'woo-customer-coupons' ) ?></label>
                                        <div class="vi-ui right labeled input">
                                            <input type="number" name="wcc_button_shop_now_size" id="wcc_button_shop_now_size" min="8"
                                                   value="<?php echo esc_attr( $wcc_button_shop_now_size ); ?>">
                                            <div class="vi-ui label basic"><?php echo esc_html( 'Px' ); ?></div>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="wcc_button_shop_now_border_radius"><?php esc_html_e( 'Border radius', 'woo-customer-coupons' ) ?></label>
                                        <div class="vi-ui right labeled input">
                                            <input type="number" name="wcc_button_shop_now_border_radius" id="wcc_button_shop_now_border_radius"
                                                   value="<?php echo esc_attr( $wcc_button_shop_now_border_radius ); ?>">
                                            <div class="vi-ui label basic"><?php echo esc_html( 'Px' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="vi-ui bottom attached tab segment" data-tab="design">
					<?php
					$wcc_template = $this->settings->get_params( 'wcc_template' );
					$template_arg = array(
						'1' => esc_html__( 'Template one', 'woo-customer-coupons' ),
						'2' => esc_html__( 'Template two', 'woo-customer-coupons' ),
						'3' => esc_html__( 'Template three', 'woo-customer-coupons' ),
						'4' => esc_html__( 'Template four', 'woo-customer-coupons' ),
					);
					$border_arg   = array(
						'dotted' => esc_html__( 'Dotted', 'woo-customer-coupons' ),
						'dashed' => esc_html__( 'Dashed', 'woo-customer-coupons' ),
						'double' => esc_html__( 'Double', 'woo-customer-coupons' ),
						'groove' => esc_html__( 'Groove', 'woo-customer-coupons' ),
						'ridge'  => esc_html__( 'Ridge', 'woo-customer-coupons' ),
						'solid'  => esc_html__( 'Solid', 'woo-customer-coupons' ),
						'outset' => esc_html__( 'Outset', 'woo-customer-coupons' ),
					);
					$coupon_date  = $this->settings->get_params( 'coupon_date' );
					?>
                    <table class="form-table">
                        <tr>
                            <th><label for="viwcc-coupon_date-checkbox"><?php esc_html_e( 'Coupon date', 'woo-customer-coupons' ); ?></label></th>
                            <td>
                                <div class="vi-ui toggle checkbox">
                                    <input type="hidden" name="coupon_date" id="viwcc-coupon_date" value="<?php echo esc_attr( $coupon_date ); ?>">
                                    <input type="checkbox" id="viwcc-coupon_date-checkbox" <?php checked( $coupon_date, 1 ); ?>>
                                </div>
                                <p class="description">
									<?php esc_html_e( 'Allow showing coupon date on frontend', 'woo-customer-coupons' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label class="viwcc-coupon_title"><?php esc_html_e( 'Title default', 'woo-customer-coupons' ); ?></label></th>
                            <td>
                                <input type="text" name="coupon_title" id="viwcc-coupon_title"
                                       value="<?php echo wp_kses_post( $coupon_title = $this->settings->get_params( 'coupon_title' ) ) ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label class="viwcc-coupon_desc"><?php esc_html_e( 'Description default', 'woo-customer-coupons' ); ?></label></th>
                            <td>
                                <input type="text" name="coupon_desc" id="viwcc-coupon_desc"
                                       value="<?php echo wp_kses_post( $coupon_desc = $this->settings->get_params( 'coupon_desc' ) ) ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Shortcode', 'woo-customer-coupons' ) ?></th>
                            <td>
								<?php
								$coupon_shortcode = array(
									'{coupon_code}'  => esc_html__( 'Discount coupon code will be sent to customer.', 'woo-customer-coupons' ),
									'{coupon_value}' => esc_html__( 'The value of coupon, can be percentage or currency amount depending on coupon type.', 'woo-customer-coupons' ),
									'{min_spend}'    => esc_html__( 'The minimum spends that you set on the coupon edit page.', 'woo-customer-coupons' ),
									'{max_spend}'    => esc_html__( 'The maximum spends that you set on the coupon edit page.', 'woo-customer-coupons' ),
								);
								foreach ( $coupon_shortcode as $k => $v ) {
									printf( '<p class="description">%1s</p>', wp_kses_post( $k . ' - ' . $v ) );
								}
								?>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wcc_template"><?php esc_html_e( 'Template', 'woo-customer-coupons' ); ?></label></th>
                            <td>
                                <select name="wcc_template" id="wcc_template" class="wcc_template vi-ui dropdown fluid">
									<?php
									foreach ( $template_arg as $k => $v ) {
										printf( '<option value="%1s" %2s > %3s</option>', esc_attr( $k ), wp_kses_post( selected( $k, $wcc_template ) ), esc_html( $v ) );
									}
									?>
                                </select>
                                <p class="description">
									<?php esc_html_e( 'Please choose the template of the coupons on frontend', 'woo-customer-coupons' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e( 'Design', 'woo-customer-coupons' ); ?></label></th>
                            <td>
                                <div class="field vi_wcc_template_style vi_wcc_template_1<?php echo esc_attr( $wcc_template === '1' ? '' : ' vi_wcc_hidden' ); ?>">
                                    <div class="equal width fields">
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Background color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text" class="color-field wcc-temple-one-background-color"
                                                   name="wcc-template-one[wcc-temple-one-background-color]"
                                                   id="wcc-temple-one-background-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-background-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Linear gradient for content background color', 'woo-customer-coupons' ) ?></label>
                                            <div class="equal width fields">
                                                <div class="field">
                                                    <input type="text"
                                                           class="color-field wcc-temple-one-content-background-color1"
                                                           name="wcc-template-one[wcc-temple-one-content-background-color1]"
                                                           id="wcc-temple-one-content-background-color1"
                                                           value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-background-color1', '' ) ) ?>">
                                                </div>
                                                <div class="field">
                                                    <input type="text"
                                                           class="color-field wcc-temple-one-content-background-color2"
                                                           name="wcc-template-one[wcc-temple-one-content-background-color2]"
                                                           id="wcc-temple-one-content-background-color2"
                                                           value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-background-color2', '' ) ) ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="equal width fields">
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Title color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-one-content-title-color"
                                                   name="wcc-template-one[wcc-temple-one-content-title-color]"
                                                   id="wcc-temple-one-content-title-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-title-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Description color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-one-content-des-color"
                                                   name="wcc-template-one[wcc-temple-one-content-des-color]"
                                                   id="wcc-temple-one-content-des-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-des-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Coupon date color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-one-content-expire-color"
                                                   name="wcc-template-one[wcc-temple-one-content-expire-color]"
                                                   id="wcc-temple-one-content-expire-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-content-expire-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Button background color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-one-button-background-color"
                                                   name="wcc-template-one[wcc-temple-one-button-background-color]"
                                                   id="wcc-temple-one-button-background-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-button-background-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Button text color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-one-button-text-color"
                                                   name="wcc-template-one[wcc-temple-one-button-text-color]"
                                                   id="wcc-temple-one-button-text-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-one', 'wcc-temple-one-button-text-color', '' ) ) ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="field vi_wcc_template_style vi_wcc_template_2<?php echo esc_attr( $wcc_template === '2' ? '' : ' vi_wcc_hidden' ); ?>">
                                    <div class="equal width fields">
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Background', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-two-background-color"
                                                   name="wcc-template-two[wcc-temple-two-background-color]"
                                                   id="wcc-temple-two-background-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-two', 'wcc-temple-two-background-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-two-title-color"
                                                   name="wcc-template-two[wcc-temple-two-title-color]"
                                                   id="wcc-temple-two-title-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-two', 'wcc-temple-two-title-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Border color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-two-border-color"
                                                   name="wcc-template-two[wcc-temple-two-border-color]"
                                                   id="wcc-temple-two-border-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-two', 'wcc-temple-two-border-color', '' ) ) ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="field vi_wcc_template_style vi_wcc_template_3<?php echo esc_attr( $wcc_template === '3' ? '' : ' vi_wcc_hidden' ); ?>">
                                    <div class="equal width fields">
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Background', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-three-background-color"
                                                   name="wcc-template-three[wcc-temple-three-background-color]"
                                                   id="wcc-temple-three-background-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-background-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Title color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-three-title-color"
                                                   name="wcc-template-three[wcc-temple-three-title-color]"
                                                   id="wcc-temple-three-title-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-title-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Terms color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-three-term-color"
                                                   name="wcc-template-three[wcc-temple-three-term-color]"
                                                   id="wcc-temple-three-term-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-term-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Expire color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-three-expire-color"
                                                   name="wcc-template-three[wcc-temple-three-expire-color]"
                                                   id="wcc-temple-three-expire-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-expire-color', '' ) ) ?>">
                                        </div>
                                    </div>
                                    <div class="equal width fields">
										<?php
										$template_3_border_type = $this->settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-border-type', 'dotted' );
										?>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Border type', 'woo-customer-coupons' ) ?></label>
                                            <select name="wcc-template-three[wcc-temple-three-border-type]" class="vi-ui fluid dropdown wcc-temple-three-border-type">
												<?php
												foreach ( $border_arg as $k => $v ) {
													printf( '<option value="%1s" %2s> %3s </option>', esc_attr( $k ), wp_kses_post( selected( $k, $template_3_border_type ) ), esc_html( $v ) );
												}
												?>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Border color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-three-border-color"
                                                   name="wcc-template-three[wcc-temple-three-border-color]"
                                                   id="wcc-temple-three-border-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-three', 'wcc-temple-three-border-color', '' ) ) ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="field vi_wcc_template_style vi_wcc_template_4<?php echo esc_attr( $wcc_template === '4' ? '' : ' vi_wcc_hidden' ); ?>">
                                    <div class="equal width fields">
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Background', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-four-background-color"
                                                   name="wcc-template-four[wcc-temple-four-background-color]"
                                                   id="wcc-temple-four-background-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-background-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Title background color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-four-title-background-color"
                                                   name="wcc-template-four[wcc-temple-four-title-background-color]"
                                                   id="wcc-temple-four-title-background-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-title-background-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Title color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-four-title-color"
                                                   name="wcc-template-four[wcc-temple-four-title-color]"
                                                   id="wcc-temple-four-title-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-title-color', '' ) ) ?>">
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Description color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-four-term-color"
                                                   name="wcc-template-four[wcc-temple-four-term-color]"
                                                   id="wcc-temple-four-term-color"
                                                   value="<?php echo esc_attr( $this->settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-term-color', '' ) ) ?>">
                                        </div>
                                    </div>
                                    <div class="equal width fields">
										<?php
										$template_4_border_type   = $this->settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-border-type', 'none' );
										$template_4_border_radius = $this->settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-border-radius', '3px' );
										$template_4_border_color  = $this->settings->get_current_setting( 'wcc-template-four', 'wcc-temple-four-border-color', '' );
										?>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Border type', 'woo-customer-coupons' ) ?></label>
                                            <select name="wcc-template-four[wcc-temple-four-border-type]" class="vi-ui fluid dropdown wcc-temple-four-border-type">
												<?php
												foreach ( $border_arg as $k => $v ) {
													printf( '<option value="%1s" %2s> %3s </option>', esc_attr( $k ), wp_kses_post( selected( $k, $template_4_border_type ) ), esc_html( $v ) );
												}
												?>
                                                <option value="none"<?php selected( $template_4_border_type, 'none' ) ?> >
													<?php esc_html_e( 'None', 'woo-customer-coupons' ); ?>
                                                </option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Border radius', 'woo-customer-coupons' ) ?></label>
                                            <select name="wcc-template-four[wcc-temple-four-border-radius]" class="vi-ui fluid dropdown wcc-temple-four-border-radius">
                                                <option value="3px"<?php selected( $template_4_border_radius, '3px' ) ?> >
													<?php esc_html_e( '3px', 'woo-customer-coupons' ); ?>
                                                </option>
                                                <option value="5px"<?php selected( $template_4_border_radius, '5px' ) ?> >
													<?php esc_html_e( '5px', 'woo-customer-coupons' ); ?>
                                                </option>
                                                <option value="10px"<?php selected( $template_4_border_radius, '10px' ) ?> >
													<?php esc_html_e( '10px', 'woo-customer-coupons' ); ?>
                                                </option>
                                                <option value="20px"<?php selected( $template_4_border_radius, '20px' ) ?> >
													<?php esc_html_e( '20px', 'woo-customer-coupons' ); ?>
                                                </option>
                                                <option value="0"<?php selected( $template_4_border_radius, '0' ) ?> >
													<?php esc_html_e( 'None', 'woo-customer-coupons' ); ?>
                                                </option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label for=""><?php esc_html_e( 'Border color', 'woo-customer-coupons' ) ?></label>
                                            <input type="text"
                                                   class="color-field wcc-temple-four-border-color"
                                                   name="wcc-template-four[wcc-temple-four-border-color]"
                                                   id="wcc-temple-four-border-color"
                                                   value="<?php echo esc_attr( $template_4_border_color ) ?>">
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td class="viwcc-coupon-preview-wrap">
								<?php
								$coupon_title = str_replace(
									array( '{coupon_code}', '{coupon_value}', '{min_spend}', '{max_spend}' ),
									array( $this->coupon_code, $this->coupon_value, $this->min_spend, $this->max_spend ),
									$coupon_title
								);
								$coupon_desc  = str_replace(
									array( '{coupon_code}', '{coupon_value}', '{min_spend}', '{max_spend}' ),
									array( $this->coupon_code, $this->coupon_value, $this->min_spend, $this->max_spend ),
									$coupon_desc
								);
								for ( $i = 1; $i <= 4; $i ++ ) {
									$class = array( 'viwcc-coupon-preview viwcc-coupon-preview-' . $i );
									if ( $i != $wcc_template ) {
										$class[] = 'vi_wcc_hidden';
									}
									wc_get_template( 'viwcc-template-' . $i . '-html.php',
										array(
											'coupon_date'  => $this->coupon_date,
											'coupon_des'   => $coupon_desc,
											'coupon_title' => $coupon_title,
											'coupon_code'  => $this->coupon_code,
											'available'    => 1,
											'coupon'       => 'design',
											'class'        => $class,
										),
										'woo-customer-coupons' . DIRECTORY_SEPARATOR,
										WOO_CUSTOM_COUPONS_TEMPLATES );
								}
								?>
                            </td>
                        </tr>
                    </table>
                </div>
                <p>
                    <button class="vi-ui labeled icon button primary vi_wcc_settings_save"
                            name="vi_wcc_settings_save">
                        <i class="save icon"></i>
						<?php esc_html_e( 'Save', 'woo-customer-coupons' ) ?>
                    </button>
                    <button type="button" class="vi-ui labeled icon button negative vi_wcc_settings_default"
                            name="vi_wcc_settings_default">
                        <i class="undo icon"></i>
						<?php esc_html_e( 'Reset Settings', 'woo-customer-coupons' ) ?>
                    </button>
                </p>
            </form>
            <div class="preview-emails-html-container vi_wcc_hidden">
                <div class="preview-emails-html-overlay"></div>
                <div class="preview-emails-html"></div>
            </div>
        </div>
		<?php
		do_action( 'villatheme_support_woo-customer-coupons' );
	}

	public function save_data() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( $page !== 'woo_customer_coupons' ) {
			return;
		}
		global $vi_wcc_settings;
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['_vi_wcc_option_nonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['_vi_wcc_option_nonce'] ) ), '_vi_wcc_option_nonce_action' ) ) {
			return;
		}
		if ( isset( $_POST['vi_wcc_settings_default'] ) ) {
			delete_option( 'wcc_options' );
			$vi_wcc_settings = $this->settings->get_default();
			return;
		}
		if ( ! isset( $_POST['vi_wcc_settings_save'] ) ) {
			return;
		}
		$arg      = array();
		$arg_map1 = array(
			'show_all',
			'show_on_cart',
			'show_on_checkout',
			'wcc_coupon-single_pro_page_pos',
			'wcc_date_format',
			'wcc_template',
			'send_email',
			'wcc_button_shop_now_bg_color',
			'wcc_button_shop_now_color',
			'wcc_button_shop_now_size',
			'wcc_button_shop_now_border_radius',
			'wcc_button_shop_now_url',
			'coupon_date',
		);
		$arg_map2 = array(
			'coupon_title',
			'coupon_desc',
			'wcc_send-mail-subject',
			'wcc_mail_heading',
			'wcc_mail_content',
			'wcc_button_shop_now_title',
		);
		$arg_map3 = array(
			'wcc-template-one',
			'wcc-template-two',
			'wcc-template-three',
			'wcc-template-four',
		);
		foreach ( $arg_map1 as $item ) {
			$arg[ $item ] = isset( $_POST[ $item ] ) ? sanitize_text_field( wp_unslash( $_POST[ $item ] ) ) : '';
		}
		foreach ( $arg_map2 as $item ) {
			$arg[ $item ] = isset( $_POST[ $item ] ) ? wp_kses_post( wp_unslash( $_POST[ $item ] ) ) : '';
		}
		foreach ( $arg_map3 as $item ) {
			$arg[ $item ] = isset( $_POST[ $item ] ) ? wc_clean( wp_unslash( $_POST[ $item ] ) ) : array();
		}
		$arg = wp_parse_args( $arg, $vi_wcc_settings );
		update_option( 'wcc_options', $arg );
		$vi_wcc_settings = $arg;
	}

	public function admin_enqueue_scripts() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $page !== 'woo_customer_coupons' ) {
			return;
		}
		/* translators: %1s: coupon valid date */
		$this->coupon_date  = sprintf( esc_html__( 'Valid Till: %1s', 'woo-customer-coupons' ), date( $this->settings->get_params( 'wcc_date_format' ) ?: get_option( 'date_format', 'F d, Y' ), strtotime( '+30 days' ) ) );// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$this->coupon_code  = 'happy';
		$this->coupon_value = '10%';
		$this->min_spend    = wc_price( 100 );
		$this->max_spend    = esc_html__( 'no maximum', 'woo-customer-coupons' );
		$this->settings::remove_other_script();
		$this->settings::enqueue_style(
			array( 'semantic-ui-button', 'semantic-ui-icon', 'semantic-ui-checkbox', 'semantic-ui-dropdown', 'semantic-ui-label', 'semantic-ui-input' ),
			array( 'button', 'icon', 'checkbox', 'dropdown', 'label', 'input' ),
			array( 1, 1, 1, 1, 1, 1 )
		);
		$this->settings::enqueue_style(
			array( 'semantic-ui-segment', 'semantic-ui-form', 'semantic-ui-menu', 'semantic-ui-tab', 'minicolors', 'transition', 'viwcc-admin-settings', 'viwcc-frontend' ),
			array( 'segment', 'form', 'tab', 'menu', 'minicolors', 'transition', 'admin-settings', 'frontend' ),
			array( 1, 1, 1, 1, 1, 1, 0 )
		);
		$this->settings::enqueue_script(
			array( 'semantic-ui-checkbox', 'semantic-ui-dropdown', 'semantic-ui-tab', 'minicolors', 'transition', 'address', 'viwcc-admin-settings' ),
			array( 'checkbox', 'dropdown', 'tab', 'minicolors', 'transition', 'address', 'admin-settings' ),
			array( 1, 1, 1, 1, 1, 1, 0 )
		);
		wp_add_inline_style( 'viwcc-frontend', $this->settings::get_coupon_style() );
		wp_add_inline_style( 'viwcc-admin-settings', $this->settings::get_email_style() );
		$arg = array(
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'setting_default' => esc_html__( 'All settings will be deleted. Are you sure you want to reset yours settings?', 'woo-customer-coupons' ),
			'coupon_code'     => $this->coupon_code,
			'coupon_value'    => $this->coupon_value,
			'min_spend'       => $this->min_spend,
			'max_spend'       => $this->max_spend,
		);
		wp_localize_script( 'viwcc-admin-settings', 'viwcc_admin_settings', $arg );
	}
}