<?php
/*
 * Plugin Name: SMSGlobal SMS Plugin MKII
 * Plugin URI: https://wordpress.org/plugins/smsglobal-sms-plugin/
 * Description: SMSGlobal SMS Integration. Woocommerce support included.
 * Version: 3.2.1
 * Requires at least: 4.6
 * Requires PHP: 5.6
 * Author: SMSGlobal
 * Author URI: https://smsglobal.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lib Directory Path Constant
define( 'PLUGIN_LIB_PATH', dirname( __FILE__ ) . '/lib' );
define( 'SMSGLOBAL_PLUGIN_VERSION', smsglobal_get_version() );

// Require settings api
require_once PLUGIN_LIB_PATH . '/class.settings-api.php';

function smsglobal_get_version() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
	$plugin_file   = basename( ( __FILE__ ) );

	return $plugin_folder[ $plugin_file ]['Version'];
}

function smsglobal_get_option( $option, $section, $default = '' ) {
	$options = get_option( $section );

	if ( isset( $options[ $option ] ) ) {
		return $options[ $option ];
	}

	return $default;
}

function get_smsglobal_template( $filepath, $datas ) {
    $fullFilepath = plugin_dir_path( __FILE__ ) . $filepath;
    if (file_exists($fullFilepath)) {
        ob_start();
        extract($datas);
        include($fullFilepath);

        return ob_get_clean();
    }

    return '';
}


class SMSGlobal_SMS {
	/**
	 * Constructor for the SMSGlobal_SMS class
	 *
	 * Sets up all the appropriate hooks and actions
	 * within our plugin.
	 *
	 * @uses is_admin()
	 * @uses add_action()
	 */
	public function __construct() {
		// Instantiate necessary class
		$this->instantiate();

		// Localize our plugin
		add_action( 'init', array( $this, 'localization_setup' ) );
		add_action( 'init', array( $this, 'register_hook_send_sms' ) );

		add_action( 'um_post_registration_approved_hook', array( $this, 'smsglobal_after_user_register' ), 10, 2 );

		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'buyer_notification_update_order_meta' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'trigger_after_order_place' ), 10, 3 );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_send_sms_meta_box' ) );
			add_action( 'wp_ajax_smsglobal_sms_send_order_sms', array( $this, 'send_custom_sms' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'woocommerce_new_customer_note', array( $this, 'trigger_new_customer_note' ), 10 );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta_link' ), 10, 4 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
		}
	}

	/**
	 * Instantiate necessary Class
	 * @return void
	 */
	function instantiate() {
		spl_autoload_register( array( $this, 'smsglobal_sms_autoload' ) );
		new SMSGlobal_Settings();
	}

	/**
	 * Autoload class files on demand
	 *
	 * @param string $class requested class name
	 */
	function smsglobal_sms_autoload( $class ) {
		require_once 'helper/sessionVars.php';
		require_once 'helper/utility.php';
		require_once 'helper/constants.php';
		require_once 'helper/messages.php';
		require_once 'helper/curl.php';
		require_once 'classes/smsglobal-settings.php';
	}

	/**
	 * Initializes the SMSGlobal_SMS() class
	 *
	 * Checks for an existing SMSGlobal_SMS() instance
	 * and if it doesn't find one, creates it.
	 */
	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new SMSGlobal_SMS();
		}

		return $instance;
	}

	/**
	 * Initialize plugin for localization
	 *
	 * @uses load_plugin_textdomain()
	 */
	public static function localization_setup() {
		load_plugin_textdomain( 'smsglobal-sms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/*send sms on user registration dated 30-01-2019*/
	function smsglobal_after_user_register( $user_id, $billing_phone ) {

		$smsglobal_reg_notify = smsglobal_get_option( 'registration_msg', 'smsglobal_general', 'off' );
		$sms_body_new_user    = smsglobal_get_option( 'sms_body_registration_msg', 'smsglobal_message', SMSGlobalMessages::DEFAULT_NEW_USER_REGISTER );

		$smsglobal_reg_admin_notify = smsglobal_get_option( 'admin_registration_msg', 'smsglobal_general', 'off' );
		$sms_admin_body_new_user    = smsglobal_get_option( 'sms_body_registration_admin_msg', 'smsglobal_message', SMSGlobalMessages::DEFAULT_ADMIN_NEW_USER_REGISTER );
		$admin_phone_number         = smsglobal_get_option( 'sms_admin_phone', 'smsglobal_message', '' );
		$user                       = get_userdata( $user_id );

		/*let's send message to user on new registration*/
		if ( $smsglobal_reg_notify == 'on' && !empty($billing_phone['billing_phone'])) {
			$search = array(
				'[username]',
				'[store_name]',
				'[email]',
				'[billing_phone]'
			);

			$replace = array(
				$user->user_login,
				get_bloginfo(),
				$user->user_email,
				$billing_phone['billing_phone']
			);

			$sms_body_new_user          = str_replace( $search, $replace, $sms_body_new_user );
			$buyer_sms_data['number']   = $billing_phone['billing_phone'];
			$buyer_sms_data['sms_body'] = $sms_body_new_user;
			$buyer_response             = SMSGlobalAPI::sendsms( $buyer_sms_data );
		}
	}


	function fn_sa_send_sms( $number, $content ) {
		$obj             = array();
		$obj['number']   = $number;
		$obj['sms_body'] = $content;
		$response        = SMSGlobalAPI::sendsms( $obj );

		return $response;
	}

	function register_hook_send_sms() {
		add_action( 'sa_send_sms', array( $this, 'fn_sa_send_sms' ), 10, 2 );
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'admin-smsalert-styles', plugins_url( 'css/admin.css', __FILE__ ), false, date( 'Ymd' ) );
		wp_enqueue_script( 'admin-smsalert-scripts', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), false, true );
		wp_enqueue_script( 'admin-smsalert-scriptss', plugins_url( 'js/chosen.jquery.min.js', __FILE__ ), array( 'jquery' ), false, false );
	}

	public function plugin_row_meta_link( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( isset( $plugin_data['slug'] ) && ( $plugin_data['slug'] == 'smsglobal-sms' ) && ! defined( 'smsglobal_DIR' ) ) {
			$plugin_meta[] = '<a href="https://www.smsglobal.com/" target="_blank">Documentation</a>';
			$plugin_meta[] = '<a href="https://wordpress.org/support/plugin/smsglobal-sms/reviews/#postform" target="_blank" class="wc-rating-link">★★★★★</a>';
		}

		return $plugin_meta;
	}

	function add_action_links( $links ) {
		$links[] = sprintf( '<a href="%s">Settings</a>', admin_url( 'admin.php?page=smsglobal-sms' ) );

		return $links;
	}

	function add_send_sms_meta_box() {
		add_meta_box(
			'smsglobal_send_sms_meta_box',
			'SMSGlobal (Send SMS)',
			array( $this, 'display_send_sms_meta_box' ),
			'shop_order',
			'side',
			'default'
		);
	}

	function display_send_sms_meta_box( $data ) {
		global $woocommerce, $post;
		$order    = new WC_Order( $post->ID );
		$order_id = $post->ID;
		?>
        <p><textarea type="text" name="smsglobal_sms_order_message" id="smsglobal_sms_order_message"
                     class="input-text" style="width: 100%;" rows="4" value=""></textarea></p>
        <input type="hidden" class="smsglobal_order_id" id="smsglobal_order_id" value="<?php echo $order_id; ?>">
        <p><a class="button tips" id="smsglobal_sms_order_send_message"
              data-tip="Send an SMS to the billing phone number for this order.">Send SMS</a>
            <span id="smsglobal_sms_order_message_char_count"
                  style="color: green; float: right; font-size: 16px;">0</span></p>
		<?php
	}

	/**
	 * Update Order buyer notify meta in checkout page
	 *
	 * @param  integer $order_id
	 *
	 * @return void
	 */
	function buyer_notification_update_order_meta( $order_id ) {
		if ( ! empty( $_POST['buyer_sms_notify'] ) ) {
			update_post_meta( $order_id, '_buyer_sms_notify', sanitize_text_field( $_POST['buyer_sms_notify'] ) );
		}
	}

	public function trigger_after_order_place( $order_id, $old_status, $new_status ) {
		$order = new WC_Order( $order_id );

		if ( ! $order_id ) {
			return;
		}
		$admin_sms_data = $buyer_sms_data = array();

		$order_status_settings = smsglobal_get_option( 'order_status', 'smsglobal_general', array() );
		$admin_phone_number    = smsglobal_get_option( 'sms_admin_phone', 'smsglobal_message', '' );

		if ( count( $order_status_settings ) < 0 ) {
			return;
		}

		if ( in_array( $new_status, $order_status_settings ) ) {
			$default_buyer_sms = defined( 'SMSGlobalMessages::DEFAULT_BUYER_SMS_' . str_replace( " ", "_", strtoupper( $new_status ) ) ) ? constant( 'SMSGlobalMessages::DEFAULT_BUYER_SMS_' . str_replace( " ", "_", strtoupper( $new_status ) ) ) : SMSGlobalMessages::DEFAULT_BUYER_SMS_STATUS_CHANGED;
			$buyer_sms_body             = smsglobal_get_option( 'sms_body_' . $new_status, 'smsglobal_message', $default_buyer_sms );
			$buyer_sms_data['number']   = get_post_meta( $order_id, '_billing_phone', true );
			$buyer_sms_data['sms_body'] = $this->pharse_sms_body( $buyer_sms_body, $new_status, $order, '' );
			$buyer_response             = SMSGlobalAPI::sendsms( $buyer_sms_data );
			$response = $buyer_response['response'];

			if ( $response['code'] >= 200 && $response['code'] <= 299 ) {
				$order->add_order_note( __( 'SMS Send to buyer Successfully.', 'smsalert' ) );
			} else {
				if ( isset( $response['message'] ) ) {
					$order->add_order_note( __( 'SMSGlobal: ' . $response['message'], 'smsalert' ) );
				}
			}
		}

		if ( smsglobal_get_option( 'admin_notification_' . $new_status, 'smsglobal_general', 'on' ) == 'on' && $admin_phone_number != '' ) {
			//send sms to post author
			if ( strpos( $admin_phone_number, 'post_author' ) !== false ) {
				$order_items        = $order->get_items();
				$first_item         = current( $order_items );
				$prod_id            = $first_item['product_id'];
				$product            = wc_get_product( $prod_id );
				$author_no          = get_the_author_meta( 'billing_phone', get_post( $prod_id )->post_author );
				$admin_phone_number = str_replace( 'post_author', $author_no, $admin_phone_number );
			}

			$default_admin_sms = defined( 'SMSGlobalMessages::DEFAULT_ADMIN_SMS_' . str_replace( " ", "_", strtoupper( $new_status ) ) ) ? constant( 'SMSGlobalMessages::DEFAULT_ADMIN_SMS_' . str_replace( " ", "_", strtoupper( $new_status ) ) ) : SMSGlobalMessages::DEFAULT_ADMIN_SMS_STATUS_CHANGED;

			$admin_sms_body             = smsglobal_get_option( 'admin_sms_body_' . $new_status, 'smsglobal_message', $default_admin_sms );
			$admin_sms_data['number']   = $admin_phone_number;
			$admin_sms_data['sms_body'] = $this->pharse_sms_body( $admin_sms_body, $new_status, $order, '' );
			$admin_response             = SMSGlobalAPI::sendsms( $admin_sms_data );
			$response                   = $admin_response['response'];

			if ( $response['code'] >= 200 && $response['code'] <= 299 ) {
				$order->add_order_note( __( 'SMS Sent Successfully.', 'smsalert' ) );
			} else {
				if ( isset( $response['message'] ) ) {
					$order->add_order_note( __( 'SMSGlobal: ' . $response['message'], 'smsalert' ) );
				}
			}
		}
	}

	function trigger_new_customer_note( $data ) {
		if ( smsglobal_get_option( 'buyer_notification_notes', 'smsglobal_general' ) == "on" ) {
			$order_id                   = $data['order_id'];
			$order                      = new WC_Order( $order_id );
			$buyer_sms_body             = smsglobal_get_option( 'sms_body_new_note', 'smsglobal_message', SMSGlobalMessages::DEFAULT_BUYER_NOTE );
			$buyer_sms_data             = array();
			$buyer_sms_data['number']   = get_post_meta( $data['order_id'], '_billing_phone', true );
			$buyer_sms_data['sms_body'] = $this->pharse_sms_body( $buyer_sms_body, $order->get_status(), $order, $data['customer_note'] );
			$buyer_response             = SMSGlobalAPI::sendsms( $buyer_sms_data );
			$response                   = $buyer_response['response'];

			if ( $response['code'] >= 200 && $response['code'] <= 299 ) {
				$order->add_order_note( __( 'Order note SMS Sent to buyer', 'smsalert' ) );
			} else {
				if ( isset( $response['message'] ) ) {
					$order->add_order_note( __( 'SMSGlobal: ' . $response['message'], 'smsalert' ) );
				}
			}
		}
	}

	public function pharse_sms_body( $content, $order_status, $order, $order_note, $rma_id = '' ) {
		$order_id           = is_callable( array( $order, 'get_id' ) ) ? $order->get_id() : $order->id;
		$order_variables    = get_post_custom( $order_id );
		$order_items        = $order->get_items();
		$item_name          = implode( ", ", array_map( function ( $o ) {
			return $o['name'];
		}, $order_items ) );
		$item_name_with_qty = implode( ", ", array_map( function ( $o ) {
			return sprintf( "%s [%u]", $o['name'], $o['qty'] );
		}, $order_items ) );
		$store_name         = get_bloginfo();
		$tracking_number    = '';
		$tracking_provider  = '';
		$tracking_link      = '';

		if (
			( strpos( $content, '[tracking_number]' ) !== false ) ||
			( strpos( $content, '[tracking_provider]' ) !== false ) ||
			( strpos( $content, '[tracking_link]' ) !== false )
		)//fetch from database only if tracking plugin is installed
		{
			if ( is_plugin_active( 'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php' ) ) {
				$tracking_info = get_post_meta( $order_id, '_wc_shipment_tracking_items', true );
				if ( sizeof( $tracking_info ) > 0 ) {
					$t_info            = array_shift( $tracking_info );
					$tracking_number   = $t_info['tracking_number'];
					$tracking_provider = ( $t_info['tracking_provider'] != '' ) ? $t_info['tracking_provider'] : $t_info['custom_tracking_provider'];
					$tracking_link     = $t_info['custom_tracking_link'];
				}
			}
		}

		$find    = array(
			'[order_id]',
			'[order_status]',
			'[rma_status]',
			'[first_name]',
			'[item_name]',
			'[item_name_qty]',
			'[order_amount]',
			'[note]',
			'[rma_number]',
			'[store_name]',
			'[tracking_number]',
			'[tracking_provider]',
			'[tracking_link]',
		);
		$replace = array(
			$order->get_order_number(),
			$order_status,
			$order_status,
			'[billing_first_name]',
			$item_name,
			$item_name_with_qty,
			$order->get_total(),
			$order_note,
			$rma_id,
			$store_name,
			$tracking_number,
			$tracking_provider,
			$tracking_link,
		);
		$content = str_replace( $find, $replace, $content );

		foreach ( $order_variables as &$value ) {
			$value = $value[0];
		}
		unset( $value );

		$order_variables = array_combine(
			array_map( function ( $key ) {
				return '[' . ltrim( $key, '_' ) . ']';
			}, array_keys( $order_variables ) ),
			$order_variables
		);
		$content         = str_replace( array_keys( $order_variables ), array_values( $order_variables ), $content );

		return $content;
	}



	public function send_custom_sms( $data ) {
		$order                         = new WC_Order( $_POST['order_id'] );
		$sms_body                      = $_POST['sms_body'];
		$buyer_sms_data                = array();
		$buyer_sms_data['destination'] = get_post_meta( $_POST['order_id'], '_billing_phone', true );
		$buyer_sms_data['message']     = $this->pharse_sms_body( $sms_body, $order->get_status(), $order, '' );
		$url                           = 'https://api.smsglobal.com/v2/sms/';
		$method                        = 'POST';

        $sms_sent_from                 = smsglobal_get_option( 'sms_sent_from', 'smsglobal_message', '' );
        if (!empty($sms_sent_from)) {
            $buyer_sms_data['origin'] = substr($sms_sent_from, 0, 11);
        }

		$body = json_encode( $buyer_sms_data );

		$headers['Accept']       = 'application/json';
		$headers['Content-Type'] = 'application/json';
		$buyer_response          = SMSGlobalAPI::makeRequest( $body, $method, $url, $headers );

		return $buyer_response;
	}

} // smsglobal_SMS

/**
 * Loaded after all plugin initialize
 */
add_action( 'plugins_loaded', 'load_SMSGlobal_SMS' );

function load_SMSGlobal_SMS() {
	SMSGlobal_SMS::init();
}

?>
