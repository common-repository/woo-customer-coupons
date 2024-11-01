<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_CUSTOM_COUPONS_Admin_Coupons {
	protected $settings;

	public function __construct() {
		$this->settings = VI_WOO_CUSTOMER_COUPONS_Data::get_instance();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		//add new column to list coupons
		add_filter( "manage_edit-shop_coupon_columns", array( $this, 'add_columns' ), 10, 1 );
		add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'column_callback' ), 10, 2 );
		add_action( 'wp_ajax_viwcc_coupon_change_status', array( $this, 'viwcc_coupon_change_status' ) );
		// save data on coupon edit page
		add_filter( 'woocommerce_coupon_data_tabs', array( $this, 'new_coupon_data_tabs' ), 99, 1 );
		add_action( 'woocommerce_coupon_data_panels', array( $this, 'customer_coupon_data_panel' ), 99, 2 );
		$coupon_hooks = array(
			'woocommerce_coupon_options_save',
			'woocommerce_new_coupon',
			'woocommerce_update_coupon',
		);
		foreach ( $coupon_hooks as $hook ) {
			add_action( $hook, array( $this, 'wcc_save_data' ), 10, 1 );
		}
	}

	public function add_columns( $columns ) {
		if ( isset( $_REQUEST['_viwcc_nonce'] ) && ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_viwcc_nonce'] ) ), 'viwcc_nonce' ) ) {
			return $columns;
		}
		if ( ! isset( $_GET['post_status'] ) || wc_clean( wp_unslash( $_GET['post_status'] ) ) !== 'trash' ) {
			$columns['viwcc_coupons'] = esc_html__( 'Show on frontend', 'woo-customer-coupons' );
		}

		return $columns;
	}

	public function column_callback( $column, $post_id ) {
		if ( $column === 'viwcc_coupons' ) {
			global $the_coupon;
			if ( ! $the_coupon ) {
				$the_coupon = new WC_Coupon( $post_id );
			}
			$enable = $the_coupon->get_meta( 'wcc_coupon_enable' ) ?: $this->settings->get_params( 'show_all' );
			printf( '<div class="viwcc-customer-coupon-column" data-coupon_id="%1s">', esc_attr( $post_id ) );
			if ( $enable && $enable !== 'no' ) {
				printf( '<span class="status"><i class="dashicons dashicons-saved"></i></span>' );
				printf( '<div class="row-actions"><span class="enable action vi_wcc_hidden">%1s</span><span class="trash action">%2s</span></div>',
					esc_html__( 'Show on frontend', 'woo-customer-coupons' ),
					esc_html__( 'Hide on frontend', 'woo-customer-coupons' )
				);
			} else {
				printf( '<span class="status">-</span><div class="row-actions"><span class="enable action">%1s</span><span class="trash action vi_wcc_hidden">%2s</span></div>',
					esc_html__( 'Show on frontend', 'woo-customer-coupons' ),
					esc_html__( 'Hide on frontend', 'woo-customer-coupons' )
				);
			}
			printf( '</div>' );
		}
	}

	public function viwcc_coupon_change_status() {
		$result = array(
			'status' => 'error',
		);
		if ( ! check_ajax_referer( 'viwcc_nonce', 'viwcc_nonce', false ) ) {
			$result['message'] = esc_html__( 'Missing nonce', 'woo-customer-coupons' );
			wp_send_json( $result );
		}
		$coupon_id = isset( $_POST['coupon_id'] ) ? wc_clean( wp_unslash( $_POST['coupon_id'] ) ) : '';
		if ( ! $coupon_id ) {
			$result['message'] = esc_html__( 'Missing coupon_id', 'woo-customer-coupons' );
			wp_send_json( $result );
		}
		$coupon = new WC_Coupon( $coupon_id );
		if ( ! $coupon ) {
			$result['message'] = esc_html__( 'Can not found coupon', 'woo-customer-coupons' );
			wp_send_json( $result );
		}
		$type = isset( $_POST['type'] ) ? wc_clean( wp_unslash( $_POST['type'] ) ) : '';
		if ( ! $type ) {
			$result['message'] = esc_html__( 'Missing coupon display status', 'woo-customer-coupons' );
			wp_send_json( $result );
		}
		$result['status'] = 'success';
		if ( $type === 'trash' ) {
			$result['message'] = esc_html__( 'This coupon is hidden on frontend now', 'woo-customer-coupons' );
			$coupon->update_meta_data( 'wcc_coupon_enable', 'no' );
		} else {
			$result['display'] = 1;
			$result['message'] = esc_html__( 'This coupon is shown on frontend now', 'woo-customer-coupons' );
			$coupon->update_meta_data( 'wcc_coupon_enable', 'yes' );
		}
		$coupon->save_meta_data();
		wp_send_json( $result );
	}

	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( $screen->id === 'shop_coupon' || $screen->id === 'edit-shop_coupon' ) {
			$this->settings::enqueue_style( array( 'viwcc-admin-settings', 'viwcc-frontend', 'villatheme-show-message' ), array(
				'admin-settings',
				'frontend',
				'villatheme-show-message'
			) );
			$this->settings::enqueue_script( array( 'viwcc-admin-coupons', 'villatheme-show-message' ), array( 'admin-coupons', 'villatheme-show-message' ) );
			$arg = array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'viwcc_nonce' ),
				'wc_price_format' => str_replace( '%1$s', get_woocommerce_currency_symbol(), get_woocommerce_price_format() ),
				'no_maximum'      => esc_html__( 'no maximum', 'woo-customer-coupons' ),
				'coupon_title'    => $this->settings->get_params( 'coupon_title' ),
				'coupon_des'      => $this->settings->get_params( 'coupon_desc' ),
			);
			wp_localize_script( 'viwcc-admin-coupons', 'viwcc_admin_coupons', $arg );
			wp_add_inline_style( 'viwcc-frontend', $this->settings::get_coupon_style() );
		}
	}

	public function new_coupon_data_tabs( $tabs ) {
		$tabs['viwcc_customer_coupon'] = array(
			'label'  => esc_html__( 'Customer Coupon', 'woo-customer-coupons' ),
			'target' => 'viwcc_customer_coupon_data',
			'class'  => '',
		);
		return $tabs;
	}

	public function customer_coupon_data_panel( $coupon_get_id, $coupon ) {
		if ( ! $coupon_get_id || ! $coupon ) {
			return;
		}
		printf( '<div class="wcc-coupons-field panel woocommerce_options_panel" id="viwcc_customer_coupon_data">' );
		wp_nonce_field( 'viwcc_nonce', '_viwcc_nonce' );
		woocommerce_wp_select(
			array(
				'id'          => 'wcc_coupon_mail_enable',
				'label'       => esc_html__( 'Send email', 'woo-customer-coupons' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Allow sending an email to your customer if this coupon info changes.', 'woo-customer-coupons' ),
				'value'       => $coupon->get_meta( 'wcc_coupon_mail_enable' ),
				'options'     => array(
					''    => esc_html__( 'Global setting', 'woo-customer-coupons' ),
					'yes' => esc_html__( 'Yes', 'woo-customer-coupons' ),
					'no'  => esc_html__( 'No', 'woo-customer-coupons' ),
				)
			)
		);
		woocommerce_wp_select(
			array(
				'id'          => 'wcc_coupon_enable',
				'label'       => esc_html__( 'Show on frontend', 'woo-customer-coupons' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Check this box if this coupon will show on frontend.', 'woo-customer-coupons' ),
				'value'       => $coupon->get_meta( 'wcc_coupon_enable' ),
				'options'     => array(
					''    => esc_html__( 'Global setting', 'woo-customer-coupons' ),
					'yes' => esc_html__( 'Yes', 'woo-customer-coupons' ),
					'no'  => esc_html__( 'No', 'woo-customer-coupons' ),
				)
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'          => 'viwcc_auto_apply',
				'label'       => esc_html__( 'Auto apply coupon', 'woo-customer-coupons' ),
				'description' => esc_html__( 'Automatically apply if all conditions of this coupon are matched.', 'woo-customer-coupons' ),
				'value'       => $coupon->get_meta( 'viwcc_auto_apply' ),
			)
		);
		woocommerce_wp_select(
			array(
				'id'          => 'viwcc_date_enable',
				'label'       => esc_html__( 'Coupon date', 'woo-customer-coupons' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Allow showing coupon date on frontend.', 'woo-customer-coupons' ),
				'value'       => $coupon->get_meta( 'viwcc_date_enable' ),
				'options'     => array(
					''    => esc_html__( 'Global setting', 'woo-customer-coupons' ),
					'yes' => esc_html__( 'Yes', 'woo-customer-coupons' ),
					'no'  => esc_html__( 'No', 'woo-customer-coupons' ),
				)
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => 'wcc_custom_coupon_title',
				'label'       => esc_html__( 'Title', 'woo-customer-coupons' ),
				'placeholder' => '{coupon_value} OFF',
				'desc_tip'    => true,
				'description' => esc_html__( 'The title of the coupon. Leave blank to use settings global', 'woo-customer-coupons' ),
				'value'       => $coupon->get_meta( 'wcc_custom_coupon_title' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => 'wcc_custom_coupon_terms',
				'label'       => esc_html__( 'Description', 'woo-customer-coupons' ),
				'placeholder' => 'Min. Spend {min_spend}',
				'desc_tip'    => true,
				'description' => esc_html__( 'The description of the coupon. Leave blank to use settings global', 'woo-customer-coupons' ),
				'value'       => $coupon->get_meta( 'wcc_custom_coupon_terms' ),
			)
		);
		$coupon_shortcode = array(
			'{coupon_code}'  => esc_html__( 'Discount coupon code will be sent to customer.', 'woo-customer-coupons' ),
			'{coupon_value}' => esc_html__( 'The value of coupon, can be percentage or currency amount depending on coupon type.', 'woo-customer-coupons' ),
			'{min_spend}'    => esc_html__( 'The minimum spends that you set on the coupon edit page.', 'woo-customer-coupons' ),
			'{max_spend}'    => esc_html__( 'The maximum spends that you set on the coupon edit page.', 'woo-customer-coupons' ),
		);
		printf( '<p class="form-field viwcc-coupon-shortcode-wrap"><label>%1s</label><span class="viwcc-coupon-shortcode">', esc_html__( 'Shortcode', 'woo-customer-coupons' ) );
		foreach ( $coupon_shortcode as $k => $v ) {
			printf( '<span>%1s</span>', wp_kses_post( $k . ' - ' . $v ) );
		}
		printf( '</span></p>' );
		woocommerce_wp_text_input(
			array(
				'id'          => 'wcc_custom_coupon_start_date',
				'label'       => esc_html__( 'Display from', 'woo-customer-coupons' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'This coupon will show on your site that day(the default is date create)', 'woo-customer-coupons' ),
				'class'       => 'date-picker',
				'placeholder' => 'YYYY-MM-DD',
				'value'       => $coupon->get_meta( 'wcc_custom_coupon_start_date' ),
			)
		);
		printf( '<div class="form-field viwcc-coupon-preview-wrap">%1$s</div>', wc_get_template_html( 'viwcc-coupons-html.php',// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'settings'        => $this->settings,
				'wrap_class'      => '',
				'coupon_message'  => '',
				'coupons'         => array( $coupon ),
				'check_available' => 0,
			),
			'woo-customer-coupons' . DIRECTORY_SEPARATOR,
			WOO_CUSTOM_COUPONS_TEMPLATES ) );
		do_action( 'woocommerce_coupon_viwcc_customer_coupon', $coupon_get_id, $coupon );
		printf( '</div>' );
	}

	public function wcc_save_data( $coupon_id ) {
		global $viwcc_coupon_event;
		if ( $viwcc_coupon_event || ! $coupon_id ) {
			return;
		}
		$viwcc_coupon_event = 1;
		$coupon             = new WC_Coupon( $coupon_id );
		if ( ! $coupon || ! is_a( $coupon, 'WC_Coupon' ) ) {
			return;
		}
		if ( isset( $_POST['_viwcc_nonce'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_POST['_viwcc_nonce'] ) ), 'viwcc_nonce' ) ) {
			$arg1 = array(
				'wcc_coupon_enable',
				'wcc_coupon_mail_enable',
				'viwcc_date_enable',
				'wcc_custom_coupon_title',
				'wcc_custom_coupon_terms',
				'wcc_custom_coupon_start_date',
			);
			foreach ( $arg1 as $item ) {
				$coupon->update_meta_data( $item, isset( $_POST[ $item ] ) ? wc_clean( wp_unslash( $_POST[ $item ] ) ) : '' );
			}
			if ( isset( $_POST['viwcc_auto_apply'] ) ) {
				$coupon->update_meta_data( 'viwcc_auto_apply', 'yes' );
			} else {
				$coupon->update_meta_data( 'viwcc_auto_apply', 'no' );
			}
			$coupon->save_meta_data();
		}
		$send_mail    = $coupon->get_meta( 'wcc_coupon_mail_enable' ) ?: $this->settings->get_params( 'send_email' );
		$restrictions = $coupon->get_email_restrictions();
		if ( ! $send_mail || $send_mail === 'no' || empty( $restrictions ) ) {
			return;
		}
		$date_expire = $coupon->get_date_expires() ? $coupon->get_date_expires()->getTimestamp() : 0;
		if ( ( $date_expire && $date_expire <= current_time( 'timestamp' ) ) || 'publish' !== $coupon->get_status() ) {
			return;
		}
		$usage_limit = $coupon->get_usage_limit();
		if ( $usage_limit ) {
			$usage_count           = $coupon->get_usage_count();
			$data_store            = $coupon->get_data_store();
			$tentative_usage_count = is_callable( array( $data_store, 'get_tentative_usage_count' ) ) ? $data_store->get_tentative_usage_count( $coupon->get_id() ) : 0;
			if ( ( $usage_count + $tentative_usage_count >= $usage_limit ) ) {
				return;
			}
		}
		$viwcc_coupon_event = 'pending_send_mail';
		foreach ( $restrictions as $customer_email ) {
			if ( ! is_email( $customer_email ) || ( mb_strpos( $customer_email, '*' ) !== false ) ) {
				continue;
			}
			$this->send_mail( $customer_email, $coupon );
		}
		$viwcc_coupon_event = 1;
	}

	public function send_mail( $customer_email, $coupon ) {
		global $viwcc_coupon_event;
		if ( ! $coupon || $viwcc_coupon_event !== 'pending_send_mail' ) {
			return;
		}
		$coupon      = new WC_Coupon( $coupon );
		$date_format = $this->settings->get_params( 'wcc_date_format' ) ?: get_option( 'date_format', 'F d, Y' );
		add_filter( 'woocommerce_email_styles', function ( $css ) {
			$css .= $this->settings::get_email_style();
			return $css;
		}, PHP_INT_MAX, 1 );
		$button_shop_title = $this->settings->get_params( 'wcc_button_shop_now_title' );
		$button_shop_url   = $this->settings->get_params( 'wcc_button_shop_now_url' ) ?: wc_get_page_permalink( 'shop' );
		$button_shop_now   = sprintf( '<a class="wcc-button-shop-now" href="%1s" target="_blank">%2s</a>', esc_url( $button_shop_url ), wp_kses_post( $button_shop_title ) );
		$arg               = array(
			'subject' => $this->settings->get_params( 'wcc_send-mail-subject' ),
			'heading' => $this->settings->get_params( 'wcc_mail_heading' ),
			'content' => $this->settings->get_params( 'wcc_mail_content' ),
		);
		$coupon_code       = '<span style="font-size: x-large;">' . strtoupper( $coupon->get_code() ) . '</span>';
		if ( $coupon->get_date_expires() ) {
			$date_expires    = date( $date_format, $coupon->get_date_expires()->getTimestamp() );// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$last_valid_date = date( $date_format, $coupon->get_date_expires()->getTimestamp() - 86400 );// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		} else {
			$date_expires = $last_valid_date = esc_html__( 'Unlimited time', 'woo-customer-coupons' );
		}
		$coupon_title = $coupon->get_meta( 'wcc_custom_coupon_title' ) ?: $this->settings->get_params( 'coupon_title' );
		$coupon_des   = $coupon->get_meta( 'wcc_custom_coupon_terms' ) ?: $this->settings->get_params( 'coupon_desc' );
		$min_spend    = wc_price( $coupon->get_minimum_amount() );
		$max_spend    = $coupon->get_maximum_amount() ? wc_price( $coupon->get_maximum_amount() ) : esc_html__( 'no maximum', 'woo-customer-coupons' );
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
		$site_title   = get_bloginfo( 'name' );
		$content      = array();
		foreach ( $arg as $key => $value ) {
			$value           = str_replace(
				array( '{coupon_code}', '{coupon_title}', '{coupon_des}', '{coupon_value}', '{date_expires}', '{last_valid_date}', '{site_title}', '{shop_now}' ),
				array( $coupon_code, $coupon_title, $coupon_des, $coupon_value, $date_expires, $last_valid_date, $site_title, $button_shop_now ),
				$value
			);
			$content[ $key ] = $value;
		}
		$mailer  = WC()->mailer();
		$email   = new WC_Email();
		$headers = "Content-Type: text/html\r\n";
		$subject = $content['subject'];
		$message = $email->style_inline( $mailer->wrap_message( $content['heading'], $content['content'] ) );
		$email->send( $customer_email, $subject, $message, $headers, array() );
	}
}