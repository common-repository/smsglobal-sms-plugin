<?php
class SmsAlertUtility
{	
	public static function get_hidden_phone($phone)
	{
		$hidden_phone = 'xxxxxxx' . substr($phone,strlen($phone) - 3);
		return $hidden_phone;
	}
	
	public static function isBlank( $value )
	{
		if( ! isset( $value ) || empty( $value ))
		//if((! isset( $value ) || empty( $value )) || (is_array($value) && in_array('',$value)))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function _create_json_response($message,$type)
	{
		return array( 'message' => $message, 'result' => $type);
	}
	
	public static function mo_is_curl_installed()
	{
		if  (in_array  ('curl', get_loaded_extensions()))
			return 1;
		else 
			return 0;
	}

	public static function currentPageUrl()
	{
		$pageURL = 'http';

		if ((isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on"))
			$pageURL .= "s";

		$pageURL .= "://";

		if ($_SERVER["SERVER_PORT"] != "80")
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];

		else
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

		if ( function_exists('apply_filters') ) apply_filters('wppb_curpageurl', $pageURL);

        return $pageURL;
	}
	
	public static function mo_get_hiden_email($email)
	{
        if(!isset($email) || trim($email)===''){
			return "";
		}
		$emailsize = strlen($email);
		$partialemail = substr($email,0,1);
		$temp = strrpos($email,"@");
		$endemail = substr($email,$temp-1,$emailsize);
		for($i=1;$i<$temp;$i++){
			$partialemail = $partialemail . 'x';
		}
		$hiddenemail = $partialemail . $endemail;
               
        return $hiddenemail;
    }
	
	public static function checkSession()
	{
		if (session_id() == '' || !isset($_SESSION)){
			session_start();
		}
	}

	public static function initialize_transaction($form,$sessionValue = true)
	{
		SmsAlertUtility::checkSession();
		$reflect = new ReflectionClass('FormSessionVars');
		foreach ($reflect->getConstants()  as $key => $value)
			unset($_SESSION[$value]);
		$_SESSION[$form] = $sessionValue;
	}

	public static function _get_invalid_otp_method()
	{
		return SMSGlobalMessages::showMessage('INVALID_OTP');
	}
}
