<?php

class SMSGlobal_Settings {
	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 */
	public static function init() {
		if ( is_plugin_active( 'ultimate-member/ultimate-member.php' ) ) //>= UM version 2.0.17
		{
			add_filter( 'um_predefined_fields_hook', __CLASS__ . '::my_predefined_fields' );
		}

		add_action( 'admin_menu', __CLASS__ . '::smsglobal_submenu' );

		add_action( 'login', __CLASS__ . '::action_woocommerce_admin_field_verify_sms_alert_user' );
		add_action( 'admin_post_save_smsglobal_settings', __CLASS__ . '::save' );

		if (!empty($_GET['page']) && ($_GET['page'] == 'smsglobal-sms-send' || $_GET['page'] == 'smsglobal-sms-log')) {
		    if (!self::isUserAuthorised()) {
				wp_redirect(admin_url('admin.php?page=smsglobal-sms&display-error=not-authorized'));
				exit;
			}
		}

		if ( ! self::isUserAuthorised() ) {
			add_action( 'admin_notices', __CLASS__ . '::show_admin_notice__success' );
		}

		self::smsglobal_dashboard_setup();

		if ( array_key_exists( 'option', $_GET ) && $_GET['option'] ) {
			switch ( trim( $_GET['option'] ) ) {
				case 'login':
				    if (!empty($_POST['api_key']) && !empty($_POST['secret'])) {
                        SMSGlobalAPI::login($_POST['api_key'], $_POST['secret']);
                    }
					exit;
				case 'send':
					$sms_data = array(
						'sms_body' => $_POST['message'],
						'number'   => $_POST['recipients'],
						'senderid' => $_POST['senderid'],
						'date'     => $_POST['dateScheduled'],
						'time'     => $_POST['timeScheduled']
					);
					if ( trim( $sms_data['number'] ) == '' || trim( $sms_data['sms_body'] ) == '' ) {
						wp_redirect( admin_url( 'admin.php?page=smsglobal-sms-send&status=Fill-all-data.' ) );
						exit;
					}
					$params   = SMSGlobalAPI::sendsms( $sms_data );
                    if($params == false){
	                    wp_redirect( admin_url( 'admin.php?page=smsglobal-sms-send&status=Failed-Invalid-Date-Time.' ) );
	                    exit;
                    }
					$response = json_decode( $params['body'] );
					if ( is_object( $response ) && property_exists( $response, 'messages' ) ) {
						$status = $response->messages[0]->status;
					} else {
						$status = "failed";
					}
					wp_redirect( admin_url( 'admin.php?page=smsglobal-sms-send&status=' . $status ) );
					exit;
					break;
				case 'logout':
					echo self::logout();
					break;
				case 'save':
					self::save();
					break;
			}
		}
	}

	/*add smsalert phone button in ultimate form*/
	public static function my_predefined_fields( $predefined_fields ) {
		$fields            = array(
			'billing_phone' => array(
				'title'    => 'SMS Alert Phone',
				'metakey'  => 'billing_phone',
				'type'     => 'text',
				'label'    => 'Mobile Number',
				'required' => 0,
				'public'   => 1,
				'editable' => 1,
				'validate' => 'billing_phone',
				'icon'     => 'um-faicon-mobile',
			)
		);
		$predefined_fields = array_merge( $predefined_fields, $fields );

		return $predefined_fields;
	}


	public static function smsglobal_submenu() {
		add_submenu_page( 'woocommerce', 'SMSGlobal', 'SMSGlobal', 'manage_options', 'smsglobal-sms', __CLASS__ . '::settings_tab' );
		add_submenu_page( 'edit.php?post_type=download', 'SMSGlobal', 'SMSGlobal', 'manage_options', 'smsglobal-sms', __CLASS__ . '::settings_tab' );
		add_submenu_page( 'ultimatemember', __( 'SMSGlobal', 'ultimatemember' ), __( 'SMSGlobal', 'ultimatemember' ), 'manage_options', 'smsglobal-sms', __CLASS__ . '::settings_tab' );

		add_menu_page( 'SMSGlobal', 'SMSGlobal', 'manage_options', 'smsglobal-sms', __CLASS__ . '::settings_tab' );
		add_submenu_page( 'smsglobal-sms', 'SMSGlobal', 'Send', 'manage_options', 'smsglobal-sms-send', __CLASS__ . '::send_page' );
		add_submenu_page( 'smsglobal-sms', 'SMSGlobal', 'SMS Log', 'manage_options', 'smsglobal-sms-log', __CLASS__ . '::sms_log' );
	}

	public static function send_page() {
		$params = array();
		echo get_smsglobal_template( 'views/sms-page.php', $params );
	}

	public static function getOutgoingMessages( $limit, $offset ) {
		$url                     = 'https://api.smsglobal.com/v2/sms/';
		$method                  = 'GET';
		$data                    = [];
		$data['offset']          = $offset;
		$data['limit']           = $limit;
		$headers['Accept']       = 'application/json';
		$headers['Content-Type'] = 'application/json';
		$response                = SMSGlobalAPI::makeRequest( $data, $method, $url, $headers );
		$outgoingMessages        = [];

		if ( ! $response ) {
            $response = $response->getErrors();
            $outgoingMessages['error'] = $response;
        } elseif ($response['response']['code'] != 200 && $response['response']['code'] != 202) {
		    $outgoingMessages['error'] = $response['body'];
		} else {
			$content                      = json_decode( $response['body'] );
			$outgoingMessages['total']    = $content->total;
			$outgoingMessages['messages'] = $content->messages;
		}

		return $outgoingMessages;
	}

	public static function sms_log() {
		$error      = null;
		$total      = null;
		$messages   = null;
		$pageNumber = null;
		$limit      = 50;
		$offset     = 1;
		$max_offset = 50;

		if ( isset( $_GET['offset'] ) ) {
			$offset = $_GET['offset'];
		}

		$outgoingMessages = self::getOutgoingMessages( $limit, $offset );

		if ( isset( $outgoingMessages['error'] ) ) {
			$error = $outgoingMessages['error'];
		} else {
			$total      = $outgoingMessages['total'] > $max_offset ? $max_offset : $outgoingMessages['total'];
			$messages   = $outgoingMessages['messages'];
			$pageNumber = floor( $total / $limit );
		}

		$args = [
			'error'      => $error,
			'messages'   => $messages,
			'pageNumber' => $pageNumber,
			'offset'     => $offset,
		];
		echo get_smsglobal_template( 'views/sms-log.php', $args );
	}


	public static function smsglobal_dashboard_setup() {
		add_action( 'dashboard_glance_items', __CLASS__ . '::smsglobal_add_dashboard_widgets', 10, 1 );
	}

	//warranty

	public static function show_admin_notice__success() {
		?>
        <div class="notice notice-warning is-dismissible">
            <p><?php _e( '<a href="admin.php?page=smsglobal-sms"> Login to SMSGlobal</a> to configure SMS Notifications', 'smsalert' ); ?></p>
        </div>
		<?php
	}

	public static function isUserAuthorised() {
		$islogged = true;
		$key      = get_option( 'smsglobal_key' );
		$secret   = get_option( 'smsglobal_secret' );
		if ( $key == '' || $secret == '' ) {
			$islogged = false;
		}

		return $islogged;
	}

	public static function smsglobal_add_dashboard_widgets( $items = array() ) {
		if ( self::isUserAuthorised() ) {
			$credits = json_decode(SMSGlobalAPI::get_credits(), true);
			if ( !empty($credits['description']) && is_array( $credits['description'] ) && array_key_exists( 'routes', $credits['description'] ) ) {
				foreach ( $credits['description']['routes'] as $credit ) {
					$items[] = sprintf( '<a href="%1$s" class="smsalert-credit"><strong>%2$s SMS</strong> : %3$s</a>', admin_url( 'admin.php?page=smsglobal-sms' ), ucwords( $credit['route'] ), $credit['credits'] ) . '<br />';
				}
			}
		}

		return $items;
	}

	public static function logout() {
		    delete_option( 'smsglobal_key' );
		    delete_option( 'smsglobal_secret' );
			delete_option( 'smsglobal_name' );
			delete_option( 'smsglobal_countryCode' );
			delete_option( 'smsglobal_balance' );
			delete_option( 'smsglobal_timezone' );
			delete_option( 'smsglobal_currency' );
			delete_option( 'smsglobal_paymentType' );
			return true;
	}

	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function settings_tab() {
		self::get_settings();

	}

	public static function save() {
		self::save_settings( $_POST );
	}

	public static function save_settings( $options ) {
		$order_statuses = is_plugin_active( 'woocommerce/woocommerce.php' ) ? wc_get_order_statuses() : array();

		if ( empty( $_POST ) ) {
			return false;
		}

		$defaults = array(
			'smsglobal_gateway' => array(
				'smsglobal_name'     => '',
				'smsglobal_password' => '',
				'smsglobal_api'      => '',
			),
			'smsglobal_message' => array(
				'sms_admin_phone'                 => '',
				'sms_sent_from'                   => '',
				'group_auto_sync'                 => '',
				'sms_body_new_note'               => '',
				'sms_body_registration_msg'       => '',
				'sms_body_registration_admin_msg' => '',
				'sms_otp_send'                    => '',
			),
			'smsglobal_general' => array(
				'buyer_checkout_otp'           => 'off',
				'buyer_signup_otp'             => 'off',
				'buyer_login_otp'              => 'off',
				'buyer_notification_notes'     => 'off',
				'allow_multiple_user'          => 'off',
				'admin_bypass_otp_login'       => 'off',
				'checkout_show_otp_button'     => 'off',
				'checkout_show_otp_guest_only' => 'off',
				'checkout_otp_popup'           => 'off',
				'allow_query_sms'              => 'on',
				'daily_bal_alert'              => 'off',
				'auto_sync'                    => 'off',
				'low_bal_alert'                => 'off',
				'alert_email'                  => '',
				'checkout_payment_plans'       => '',
				'otp_for_selected_gateways'    => 'off',
				'otp_resend_timer'             => '15',
				'otp_verify_btn_text'          => 'Click here to verify your Phone',
				'default_country_code'         => '91',
				'login_with_otp'               => 'off',
				'validate_before_send_otp'     => 'off',
				'registration_msg'             => 'off',
				'admin_registration_msg'       => 'off',

			),


			'smsglobal_sync'                 => array(
				'last_sync_userId' => '3'
			),
			'smsglobal_background_task'      => array(
				'last_updated_lBal_alert' => '',
			),
			'smsglobal_background_dBal_task' => array(
				'last_updated_dBal_alert' => '',
			),
			'smsglobal_edd_general'          => array(),
		);

		foreach ( $order_statuses as $ks => $vs ) {
			$prefix = 'wc-';
			if ( substr( $ks, 0, strlen( $prefix ) ) == $prefix ) {
				$ks = substr( $ks, strlen( $prefix ) );
			}
			$defaults['smsglobal_general'][ 'admin_notification_' . $ks ] = 'off';
			$defaults['smsglobal_general']['order_status'][ $ks ]         = '';
			$defaults['smsglobal_message'][ 'admin_sms_body_' . $ks ]     = '';
			$defaults['smsglobal_message'][ 'sms_body_' . $ks ]           = '';
		}


		$_POST['smsglobal_general']['checkout_payment_plans'] = isset( $_POST['smsglobal_general']['checkout_payment_plans'] ) ? maybe_serialize( $_POST['smsglobal_general']['checkout_payment_plans'] ) : array();
		$options                                              = array_replace_recursive( $defaults, array_intersect_key( $_POST, $defaults ) );

		foreach ( $options as $name => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $k => $v ) {
				    if ($k === 'sms_sent_from') {
				        $v = substr($v, 0, 11);
                    }

					if ( ! is_array( $v ) ) {
						$value[ $k ] = stripcslashes( $v );
					}
				}
			}

			update_option( $name, $value );
		}
		//return true;
		wp_redirect( admin_url( 'admin.php?page=smsglobal-sms&m=1' ) );
		exit;
	}

	public static function getvariables() {
		$variables = array(
			'[order_id]'      => 'Order Id',
			'[order_status]'  => 'Order Status',
			'[order_amount]'  => 'Order Amount',
			'[store_name]'    => 'Store Name',
			'[item_name]'     => 'Product Name',
			'[item_name_qty]' => 'Product Name with Quantity',

			'[billing_first_name]' => 'Billing First Name',
			'[billing_last_name]'  => 'Billing Last Name',
			'[billing_company]'    => 'Billing Company',
			'[billing_address_1]'  => 'Billing Address 1',
			'[billing_address_2]'  => 'Billing Address 2',
			'[billing_city]'       => 'Billing City',
			'[billing_state]'      => 'Billing State',
			'[billing_postcode]'   => 'Billing Postcode',
			'[billing_country]'    => 'Billing Country',
			'[billing_email]'      => 'Billing Email',
			'[billing_phone]'      => 'Billing Phone',

			'[shipping_first_name]' => 'Shipping First Name',
			'[shipping_last_name]'  => 'Shipping Last Name',
			'[shipping_company]'    => 'Shipping Company',
			'[shipping_address_1]'  => 'Shipping Address 1',
			'[shipping_address_2]'  => 'Shipping Address 2',
			'[shipping_city]'       => 'Shipping City',
			'[shipping_state]'      => 'Shipping State',
			'[shipping_postcode]'   => 'Shipping Postcode',
			'[shipping_country]'    => 'Shipping Country',

			'[order_currency]'       => 'Order Currency',
			'[payment_method]'       => 'Payment Method',
			'[payment_method_title]' => 'Payment Method Title',
			'[shipping_method]'      => 'Shipping Method',
		);

		if ( is_plugin_active( 'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php' ) ) {
			$wc_shipment_variables = array(
				'[tracking_number]'   => 'Tracking Number',
				'[tracking_provider]' => 'Tracking Provider',
				'[tracking_link]'     => 'Tracking Link',
			);
			$variables             = array_merge( $variables, $wc_shipment_variables );
		}


		$ret_string = '';
		foreach ( $variables as $vk => $vv ) {
			$ret_string .= sprintf( "<a href='#' val='%s'>%s</a> | ", $vk, __( $vv, SMSGlobalConstants::TEXT_DOMAIN ) );
		}

		return $ret_string;
	}


	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public static function get_settings() {
		global $current_user;
		wp_get_current_user();

		$hasWoocommerce = is_plugin_active( 'woocommerce/woocommerce.php' );
		$hasWPmembers   = is_plugin_active( 'wp-members/wp-members.php' );
		$hasUltimate    = ( is_plugin_active( 'ultimate-member/ultimate-member.php' ) || is_plugin_active( 'ultimate-member/index.php' ) ) ? true : false;

		$hasWPAM = ( is_plugin_active( 'affiliates-manager/boot-strap.php' ) ) ? true : false;

		$sms_admin_phone  = smsglobal_get_option( 'sms_admin_phone', 'smsglobal_message', '' );
		$sms_sent_from  = smsglobal_get_option( 'sms_sent_from', 'smsglobal_message', '' );
		$group_auto_sync  = smsglobal_get_option( 'group_auto_sync', 'smsglobal_general', '' );
		$sms_body_on_hold = smsglobal_get_option( 'sms_body_on-hold', 'smsglobal_message', SMSGlobalMessages::DEFAULT_BUYER_SMS_ON_HOLD );

		$sms_body_processing = smsglobal_get_option( 'sms_body_processing', 'smsglobal_message', SMSGlobalMessages::DEFAULT_BUYER_SMS_PROCESSING );

		$sms_body_completed = smsglobal_get_option( 'sms_body_completed', 'smsglobal_message', SMSGlobalMessages::DEFAULT_BUYER_SMS_COMPLETED );
		$sms_body_cancelled = smsglobal_get_option( 'sms_body_cancelled', 'smsglobal_message', SMSGlobalMessages::DEFAULT_BUYER_SMS_CANCELLED );
		$sms_body_new_note  = smsglobal_get_option( 'sms_body_new_note', 'smsglobal_message', SMSGlobalMessages::DEFAULT_BUYER_NOTE );

		$sms_body_registration_msg       = smsglobal_get_option( 'sms_body_registration_msg', 'smsglobal_message', SMSGlobalMessages::DEFAULT_NEW_USER_REGISTER );
		$sms_body_registration_admin_msg = smsglobal_get_option( 'sms_body_registration_admin_msg', 'smsglobal_message', SMSGlobalMessages::DEFAULT_ADMIN_NEW_USER_REGISTER );

		$sms_otp_send = smsglobal_get_option( 'sms_otp_send', 'smsglobal_message', SMSGlobalMessages::DEFAULT_BUYER_OTP );

		$smsglobal_notification_status = smsglobal_get_option( 'order_status', 'smsglobal_general', '' );

		$smsglobal_notification_onhold = ( is_array( $smsglobal_notification_status ) && array_key_exists( 'on-hold', $smsglobal_notification_status ) ) ? $smsglobal_notification_status['on-hold'] : 'on-hold';
		$smsglobal_notification_processing = ( is_array( $smsglobal_notification_status ) && array_key_exists( 'processing', $smsglobal_notification_status ) ) ? $smsglobal_notification_status['processing'] : 'processing';
		$smsglobal_notification_completed = ( is_array( $smsglobal_notification_status ) && array_key_exists( 'completed', $smsglobal_notification_status ) ) ? $smsglobal_notification_status['completed'] : 'completed';
		$smsglobal_notification_cancelled = ( is_array( $smsglobal_notification_status ) && array_key_exists( 'cancelled', $smsglobal_notification_status ) ) ? $smsglobal_notification_status['cancelled'] : 'cancelled';

		$smsglobal_notification_checkout_otp  = smsglobal_get_option( 'buyer_checkout_otp', 'smsglobal_general', 'on' );
		$smsglobal_notification_signup_otp    = smsglobal_get_option( 'buyer_signup_otp', 'smsglobal_general', 'on' );
		$smsglobal_notification_login_otp     = smsglobal_get_option( 'buyer_login_otp', 'smsglobal_general', 'on' );
		$smsglobal_notification_notes         = smsglobal_get_option( 'buyer_notification_notes', 'smsglobal_general', 'on' );
		$smsglobal_notification_reg_msg       = smsglobal_get_option( 'registration_msg', 'smsglobal_general', 'on' );
		$smsglobal_notification_reg_admin_msg = smsglobal_get_option( 'admin_registration_msg', 'smsglobal_general', 'on' );
		$smsglobal_allow_multiple_user        = smsglobal_get_option( 'allow_multiple_user', 'smsglobal_general', 'on' );
		$smsglobal_allow_query_sms            = smsglobal_get_option( 'allow_query_sms', 'smsglobal_general', 'on' );
		$islogged                             = self::isUserAuthorised();
		$smsglobal_helper                     = ( ! $islogged ) ? sprintf( 'Please enter your MXT <a href="https://mxt.smsglobal.com/integrations" target="_blank">mxt.smsglobal.com</a> API details to link it with <b>' . get_bloginfo() . '</b>' ) : '';

        if (!empty($_GET['display-error'])): ?>
            <div class="notice notice-error is-dismissible">
                <p><strong><?php echo ucwords(str_replace('-', ' ', $_GET['display-error'])); ?></strong></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>?option=save">
            <div class="smsglobal_box smsglobal_settings_box">
                <div class="smsglobal_nav_tabs">
					<?php
					$params = array(
						'hasWoocommerce'     => $hasWoocommerce,
						'hasWPmembers'       => $hasWPmembers,
						'hasUltimate'        => $hasUltimate,
						'hasWPAM'            => $hasWPAM,
						'islogged'           => $islogged,
						'smsglobal_password' => '',
						'smsglobal_name'     => get_option( 'smsglobal_name' ),
						'smsglobal_balance'  => get_option( 'smsglobal_balance' ),
						'smsglobal_currency' => get_option( 'smsglobal_currency' ),
						'smsglobal_type'     => get_option( 'smsglobal_paymentType' ),
						'smsglobal_timezone' => get_option( 'smsglobal_timezone' ),
						'smsglobal_country'  => get_option( 'smsglobal_countryCode' ),
					);
					echo get_smsglobal_template( 'views/smsglobal_nav_tabs.php', $params );
					?>
                </div>
                <div>
                    <div class="smsglobal_nav_box smsglobal_nav_global_box smsglobal_active general"><!--general tab-->
						<?php
						$params = array
						(
							'smsglobal_helper'   => $smsglobal_helper,
							'islogged'           => $islogged,
							'smsglobal_name'     => '',
							'smsglobal_password' => '',
							'smsglobal_name'     => get_option( 'smsglobal_name' ),
							'smsglobal_balance'  => get_option( 'smsglobal_balance' ),
							'smsglobal_currency' => get_option( 'smsglobal_currency' ),
							'smsglobal_type'     => get_option( 'smsglobal_paymentType' ),
							'smsglobal_timezone' => get_option( 'smsglobal_timezone' ),
							'smsglobal_country'  => get_option( 'smsglobal_countryCode' ),
						);
						echo get_smsglobal_template( 'views/smsglobal_general_tab.php', $params );
						?>
                    </div><!--/-general tab-->

                    <div class="smsglobal_nav_box smsglobal_nav_css_box customertemplates"><!--customertemplates tab-->

						<?php
						$order_statuses = is_plugin_active( 'woocommerce/woocommerce.php' ) ? wc_get_order_statuses() : array();
						?>
						<?php
						$params = array(
							'order_statuses'                  => $order_statuses,
							'smsglobal_notification_status'   => $smsglobal_notification_status,
							'getvariables'                    => self::getvariables(),
							'hasWoocommerce'                  => $hasWoocommerce,
							'smsglobal_notification_notes'    => $smsglobal_notification_notes,
							'smsglobal_notification_reg_msg'  => $smsglobal_notification_reg_msg,
							'sms_body_new_note'               => $sms_body_new_note,
							'sms_body_registration_msg'       => $sms_body_registration_msg,
							'sms_body_registration_admin_msg' => $sms_body_registration_admin_msg,
							'hasWPmembers'                    => $hasWPmembers,
							'hasUltimate'                     => $hasUltimate,
							'hasWPAM'                         => $hasWPAM,
						);
						echo get_smsglobal_template( 'views/wc-customer-template.php', $params );
						?>


                    </div><!--/-customertemplates tab-->

                    <div class="smsglobal_nav_box smsglobal_nav_admintemplates_box admintemplates">
                        <!--admintemplates tab-->
						<?php
						$params = array(
							'order_statuses'                       => $order_statuses,
							'hasWoocommerce'                       => $hasWoocommerce,
							'hasUltimate'                          => $hasUltimate,
							'smsglobal_notification_reg_admin_msg' => $smsglobal_notification_reg_admin_msg,
							'sms_body_registration_admin_msg'      => $sms_body_registration_admin_msg,
							'getvariables'                         => self::getvariables(),
						);
						echo get_smsglobal_template( 'views/wc-admin-template.php', $params );
						?>
                    </div><!--/-admintemplates tab-->

                    <!--Edd download customer templates-->
                    <div class="smsglobal_nav_box smsglobal_nav_eddcsttemplates_box eddcsttemplates">
						<?php
						$edd_order_statuses = is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ? edd_get_payment_statuses() : array();
						?>
						<?php
						$params = array( 'edd_order_statuses' => $edd_order_statuses );
						echo get_smsglobal_template( 'views/edd_customer_template.php', $params );
						?>
                    </div>
                    <!--/--Edd download customer templates-->

                    <!--EDD admintemplates tab-->
                    <div class="smsglobal_nav_box smsglobal_nav_eddadmintemplates_box eddadmintemplates">
                        <!-- Admin-accordion -->
						<?php
						$params = array( 'edd_order_statuses' => $edd_order_statuses );
						echo get_smsglobal_template( 'views/edd_admin_template.php', $params );
						?>
                    </div>
                    <!--/-EDD admintemplates tab-->


                    <!--Wp Affiliate Manager tabs-->
					<?php if ( $hasWPAM ) { ?>
						<?php
						$wpam_statuses    = AffiliateManagerForm::get_affiliate_statuses();
						$wpam_transaction = AffiliateManagerForm::get_affiliate_transaction();
						$params           = array(
							'wpam_statuses'    => $wpam_statuses,
							'wpam_transaction' => $wpam_transaction,
						);
						?>
                        <!--Wp Affiliate Manager Customer templates-->

                        <div class="smsglobal_nav_box smsglobal_nav_wpamcsttemplates_box wpamcsttemplates">

							<?php
							echo get_smsglobal_template( 'views/affiliate_customer_template.php', $params );
							?>
                        </div>
                        <!--/--Wp Affiliate Manager Customer templates-->
                        <!--Wp Affiliate Manager Admin templates-->

                        <div class="smsglobal_nav_box smsglobal_nav_wpamadmintemplates_box wpamadmintemplates">
							<?php
							echo get_smsglobal_template( 'views/affiliate_admin_template.php', $params );
							?>
                        </div>
                        <!--/--Wp Affiliate Manager Admin templates-->
					<?php } ?>
                    <!--/-Wp Affiliate Manager tabs-->

                    <div class="smsglobal_nav_box smsglobal_nav_callbacks_box otp"><!--otp tab-->
                        <style>.top-border {
                                border-top: 1px dashed #b4b9be;
                            }</style>
                        <table class="form-table">

							<?php if ( $hasWoocommerce || $hasWPAM ) { ?>
                                <tr valign="top">
                                    <th scrope="row"><?php _e( 'Send Admin SMS To', SMSGlobalConstants::TEXT_DOMAIN ) ?>
                                        <span class="tooltip"
                                              data-title="Please make sure that the number must be without country code (e.g.: 8010551055)"><span
                                                    class="dashicons dashicons-info"></span></span>
                                    </th>
                                    <td>
                                        <select id="send_admin_sms_to" onchange="toggle_send_admin_alert(this);">
                                            <option value="">Other</option>
                                            <option value="post_author" <?php echo ( trim( $sms_admin_phone ) == 'post_author' ) ? 'selected="selected"' : ''; ?>>
                                                Post Author
                                            </option>
                                        </select>
                                        <script>
                                            function toggle_send_admin_alert(obj) {
                                                var value = jQuery(obj).val();
                                                jQuery('.admin_no').val(value);
                                                if (value == 'post_author')
                                                    jQuery('.admin_no').attr('readonly', 'readonly');
                                                else
                                                    jQuery('.admin_no').removeAttr('readonly');
                                            }
                                        </script>
                                        <input type="text" name="smsglobal_message[sms_admin_phone]" class="admin_no"
                                               id="smsglobal_message[sms_admin_phone]" <?php echo ( trim( $sms_admin_phone ) == 'post_author' ) ? 'readonly="readonly"' : ''; ?>
                                               value="<?php echo $sms_admin_phone; ?>">
                                        <br/><span><?php _e( 'Admin order sms notifications will be sent to this number.', SMSGlobalConstants::TEXT_DOMAIN ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scrope="row"><?php _e( 'Send SMS From', SMSGlobalConstants::TEXT_DOMAIN ) ?></th>
                                    <td>
                                        <input type="text" maxlength="11" name="smsglobal_message[sms_sent_from]" id="smsglobal_message[sms_sent_from]" value="<?php echo $sms_sent_from; ?>">
                                        <br><span><?php _e('Number or alpha word to use as sender (maximum 11 characters)', SMSGlobalConstants::TEXT_DOMAIN); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php submit_button( __( 'Save Changes' ), 'primary', 'Update' ); ?>
                                    </td>
                                </tr>
							<?php } ?>


                        </table>
                    </div><!--/-otp tab-->


                    <div class="smsglobal_nav_box smsglobal_nav_support_box support"><!--support tab-->
                        <table class="form-table">
                            <tr valign="top">
                                <td>
                                    <p><b>Need more credits? See our </b> <a href="https://www.smsglobal.com/pricing/"
                                                                            target="_blank">Pricing</a> to purchase.</p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <td>
                                    <div class="col-lg-12 creditlist">
                                        <div class="col-lg-8 route">
                                            <h3><?php _e( 'Email Support:', SMSGlobalConstants::TEXT_DOMAIN ) ?> <a
                                                        href="mailto:support@smsglobal.com" target="_blank">support@smsglobal.com</a>
                                            </h3>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="top">
                                <td>
                                    <div class="col-lg-12 creditlist">
                                        <div class="col-lg-8 route">
                                            <h3><?php _e( 'Phone Support:', SMSGlobalConstants::TEXT_DOMAIN ) ?> 1300
                                                883 400</h3>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div><!--/-support tab-->

                    <script>


                        function insertAtCaret(textFeildValue, txtbox_id) {
                            var textObj = document.getElementById(txtbox_id);
                            if (document.all) {
                                if (textObj.createTextRange && textObj.caretPos) {
                                    var caretPos = textObj.caretPos;
                                    caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? textFeildValue + ' ' : textFeildValue;
                                } else {
                                    textObj.value = textObj.value + textFeildValue;
                                }
                            } else {
                                if (textObj.setSelectionRange) {
                                    var rangeStart = textObj.selectionStart;
                                    var rangeEnd = textObj.selectionEnd;
                                    var tempStr1 = textObj.value.substring(0, rangeStart);
                                    var tempStr2 = textObj.value.substring(rangeEnd);

                                    textObj.value = tempStr1 + textFeildValue + tempStr2;
                                } else {
                                    alert("This version of Mozilla based browser does not support setSelectionRange");
                                }
                            }
                        }

                        jQuery(document).ready(function () {
                            function close_accordion_section() {
                                jQuery('.cvt-accordion .expand_btn').removeClass('active');
                                jQuery('.cvt-accordion .cvt-accordion-body-content').slideUp(300).removeClass('open');
                            }

                            jQuery('.expand_btn').click(function (e) {
                                var currentAttrValue = jQuery(this).parent().attr('data-href');
                                if (jQuery(e.target).is('.active')) {
                                    close_accordion_section();
                                } else {
                                    close_accordion_section();
                                    jQuery(this).addClass('active');
                                    jQuery('.cvt-accordion ' + currentAttrValue).slideDown(300).addClass('open');
                                }

                                e.preventDefault();
                            });

                            jQuery('.smsglobal_tokens a').click(function () {
                                insertAtCaret(jQuery(this).attr('val'), jQuery(this).parents('td').find('textarea').attr('id'));
                                return false;
                            });
                        });

                        //checkbox click function
                        jQuery('.cvt-accordion-body-title input[type="checkbox"]').click(function (e) {

                            var childdiv = jQuery(this).parent().attr('data-href');   //if child div have multiple checkbox

                            if (!jQuery(this).is(':checked')) {
                                //select all child div checkbox
                                jQuery(childdiv).find('.notify_box').each(function () {
                                    this.checked = false;
                                });

                                jQuery(this).parent().find('.expand_btn.active').trigger('click'); //expand accordion

                            } else {
                                //uncheck all child  div checkbox
                                jQuery(childdiv).find('.notify_box').each(function () {
                                    this.checked = true;
                                });

                                jQuery(this).parent().find('.expand_btn').not('.active').trigger('click');  //expand accordion

                            }
                        });


                        // on checkbox toggle readonly input
                        function toggleReadonly(obj, type) {

                            for (var e = jQuery('.smsglobal_box input[type="checkbox"]').length, t = 0; e > t; t++)
                                jQuery('.smsglobal_box input[type="checkbox"]').eq(t).is(":checked") === !1 ? jQuery('.smsglobal_box input[type="checkbox"]').eq(t).parent().parent().find(type).attr("readonly", !0) : jQuery('.smsglobal_box input[type="checkbox"]').eq(t).parent().parent().find(type).removeAttr("readonly");
                        }

                        // on checkbox enable-disable select
                        function toggleDisabled(obj) {

                            for (var e = jQuery('.smsglobal_box input[type="checkbox"]').length, t = 0; e > t; t++)
                                if (jQuery('.smsglobal_box input[type="checkbox"]').eq(t).is(":checked") === !1) {

                                    //make disabled
                                    jQuery('.smsglobal_box input[type="checkbox"]').eq(t).parent().parent().find("select").attr("disabled", !0); //for select
                                    jQuery('.smsglobal_box input[type="checkbox"]').eq(t).parent().parent().find("#create_group").addClass("anchordisabled"); //for anchor
                                } else {
                                    //remove disabled
                                    jQuery('.smsglobal_box input[type="checkbox"]').eq(t).parent().parent().find("select").removeAttr("disabled");//for select
                                    jQuery('.smsglobal_box input[type="checkbox"]').eq(t).parent().parent().find("#create_group").removeClass("anchordisabled"); //for anchor
                                    jQuery(".chosen-select").trigger("chosen:updated");
                                }

                            /*jQuery('.smsglobal_box input[type="checkbox"]').eq(t).is(":checked") === !1 ? jQuery('.smsglobal_box input[type="checkbox"]').eq(t).parent().parent().find("select").attr("disabled", !0) : jQuery('.smsglobal_box input[type="checkbox"]').eq(t).parent().parent().find("select").removeAttr("disabled"); */
                        }

                        toggleReadonly(jQuery('.smsglobal_box input[type="checkbox"]'), 'input[type="number"]'); //init on input type number
                        toggleDisabled(jQuery('.smsglobal_box select')); //init on select


                    </script>

                </div>
            </div>
        </form>
		<?php
		return apply_filters( 'smsglobal_setting', array() );
	}


	public static function action_woocommerce_admin_field_verify_sms_alert_user( $value ) {
		global $current_user;
		wp_get_current_user();
		$smsglobal_name     = smsglobal_get_option( 'smsglobal_name', 'smsglobal_gateway', '' );
		$smsglobal_password = smsglobal_get_option( 'smsglobal_password', 'smsglobal_gateway', '' );
		$hidden             = '';
		if ( $smsglobal_name != '' && $smsglobal_password != '' ) {
			$credits = json_decode( SMSGlobalAPI::get_credits(), true );
			if ( $credits['status'] == 'success' || ( is_array( $credits['description'] ) && $credits['description']['desc'] == 'no senderid available for your account' ) ) {
				$hidden = 'hidden';
			}
		}
		?>
        <tr valign="top" class="<?php echo $hidden ?>">
            <th>&nbsp;</th>
            <td>
                <button class="button-primary woocommerce-save-button" onclick="verifyUser(this); return false;">Login</button>
                Don't have an account on SMSGlobal? <a href="https://www.smsglobal.com/mxt-sign-up/" target="_blank">Signup
                    here for FREE</a>
                <div id="verify_status"></div>
            </td>
        </tr>
		<?php
	}

}

SMSGlobal_Settings::init();