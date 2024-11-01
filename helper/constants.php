<?php

class SMSGlobalConstants
{
	const SUCCESS				= "SUCCESS";
	const FAILURE				= "FAILURE";
	const TEXT_DOMAIN 			= "smsglobal-sms";
	const PATTERN_PHONE			= '/^(\+)?(country_code)?0?\d{10}$/'; //'/^\d{10}$/';//'/\d{10}$/';
	const ERROR_JSON_TYPE 		= 'error';
	const SUCCESS_JSON_TYPE 	= 'success';
	const DEFAULT_SMS_TEMPLATE  = "Dear Customer, Your OTP is ##otp##. Use this Passcode to complete your transaction. Thank you.";
	const RCON_TEMPLATE			= "YOUR CODE IS : ##otp##";
	const USERPRO_AJAX_CHECK	= "mo_phone_validation";
	const USERPRO_VER_FIELD_META= "verification_form";	
	
	function __construct()
	{
		$this->define_global();
	}
	

	
	function define_global()
	{
		global $phoneLogic;
		define('MOV_DIR', plugin_dir_path(dirname(__FILE__)));
		define('MOV_URL', plugin_dir_url(dirname(__FILE__)));
		define('MOV_CSS_URL', MOV_URL . 'css/sms_alert_customer_validation_style.css');
		define('MOV_LOADER_URL', MOV_URL . 'images/ajax-loader.gif');
	}
}
new SMSGlobalConstants;