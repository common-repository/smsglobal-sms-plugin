<?php

class SMSGlobalAPI {

	public static function checkPhoneNos( $nos = null ) {
		$country_code = smsglobal_get_option( 'default_country_code', 'smsglobal_general' );

		$nos      = explode( ',', $nos );
		$valid_no = array();
		if ( is_array( $nos ) ) {

			foreach ( $nos as $no ) {
				$valid_no[] = $no;
			}
		}

		if ( sizeof( $valid_no ) > 0 ) {
			$nos = implode( ',', $valid_no );

			return $nos;
		} else {
			return false;
		}
	}

	public static function sendsms( $sms_data ) {
		$response = false;
		$key      = get_option( 'smsglobal_key' );
		$secret   = get_option( 'smsglobal_secret' );
        $origin   = smsglobal_get_option( 'sms_sent_from', 'smsglobal_message', '' );
        $senderid = array_key_exists('senderid',$sms_data) ? $sms_data['senderid'] : (!empty($origin) ? $origin : '');
		$phone    = $sms_data['number'];
		$date     = array_key_exists('date',$sms_data) ? $sms_data['date'] : '';
		$time     = array_key_exists('time',$sms_data) ? $sms_data['time'] : '';


		$text = htmlspecialchars_decode( $sms_data['sms_body'] );

		//bail out if nothing provided
		if ( empty( $key ) || empty( $secret ) || empty( $text ) ) {
			return false;
		}

		//send login request
		$url    = 'https://api.smsglobal.com/v2/sms/';
		$method = 'POST';

        $fields = array(
            'origin'      => substr($senderid, 0, 11),
            'message'     => $text
        );

		if ( strstr( $phone, "," ) ) {
			$fields['destinations'] = explode( ",", $phone );
		} else {
			$fields['destination'] = $phone;
		}

		if ( $time !== "" && $date == "" ) {
			return false;
		}
		if ( $date !== "" ) {
			if ( $time == "" ) {
				$time = date( 'h:i A', current_time( 'timestamp', 0 ) );
			}
			$datetime = $date . " " . $time;
			$utc      = self::datetimeconv( $datetime );
			if ( ! $utc ) {
				return false;
			}
			$fields['scheduledDateTime'] = $utc;
		}

		$body = json_encode( $fields );

		$headers['Accept']       = 'application/json';
		$headers['Content-Type'] = 'application/json';

		$response = self::makeRequest( $body, $method, $url, $headers );

		return $response;
	}

	public static function datetimeconv( $datetime ) {
		$from = [ 'localeFormat' => "Y-m-d H:i:s", 'olsonZone' => 'Australia/Melbourne' ];
		$to   = [ 'localeFormat' => "Y-m-d H:i:s", 'olsonZone' => 'UTC' ];

		try {
			if ( ( $datetime = DateTime::createFromFormat( 'd/m/Y h:i A', $datetime, new DateTimeZone( $from['olsonZone'] ) ) ) == false ) {
				return false;
			}
			$datetime->setTimeZone( new DateTimeZone( $to['olsonZone'] ) );

			return $datetime->format( $to['localeFormat'] );
		} catch ( \Exception $e ) {
			return false;
		}
	}


	public static function login( $apiKey = null, $secret = null )
    {
        if (empty($apiKey) || empty($secret)) {
            return '';
        }

        // keeping it like this so that people already logged in don't break
        self::update_or_add_option('key', $apiKey);
        self::update_or_add_option('secret', $secret);

        // get balance
        $url = 'https://api.smsglobal.com/v2/user/credit-balance';
        $method = 'GET';

        $body = json_encode([]);
        $response = [];

        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $requestResponse = self::makeRequest($body, $method, $url, $headers);

        if ($requestResponse['response']['code'] != '403') {
            if ($requestResponse['response']['code'] != '404') {
                $response = (array)json_decode($requestResponse['body']);
                $response['paymentType'] = 'PRE';
            } else {
                $response['paymentType'] = 'POST';
            }

            // get contact details
            $url = 'https://api.smsglobal.com/v2/user/contact-details';
            $method = 'GET';

            $requestResponse = self::makeRequest($body, $method, $url, $headers);

            if ($requestResponse['response']['code'] != '403') {
                if (!empty($requestResponse['body'])) {
                    $response = array_merge($response, (array)json_decode($requestResponse['body']));
                    if (!empty($response['country'])) {
                        $response['countryCode'] = $response['country'];
                        unset($response['country']);
                    }
                }

                // Add option to save the returned values
                foreach ($response as $optionName => $optionValue) {
                    self::update_or_add_option($optionName, $optionValue);
                }

                return $response;
            }
        }

        // back out here
        delete_option( 'smsglobal_key' );
        delete_option( 'smsglobal_secret' );

        return false;
    }

    /**
     * Update the wordpress option or create one if it does not exist
	 *
	 * @param $option_name
	 * @param $option_value
	 */
	public static function update_or_add_option( $option_name, $option_value ) {
		if ( get_option( $option_name ) !== false ) {
			// The option already exists, so we just update it.
			update_option( 'smsglobal_' . $option_name, $option_value );
		} else {
			// The option hasn't been added yet. We'll add it with $autoload set to 'no'.
			$deprecated = null;
			$autoload   = 'no';
			add_option( 'smsglobal_' . $option_name, $option_value, $deprecated, $autoload );
		}
	}


	public static function makeRequest( $body, $method, $url, $headers ) {
		global $wp_version;

		if ( ! strstr( $url, "login" ) ) {
		    $parsedUrl = parse_url($url);
			$mac                      = self::generateMacHeader( $method, $parsedUrl['path'] );
			$headers['Authorization'] = $mac;
			$headers['User-Agent']    = "SMSGlobal-Integrations/" . SMSGLOBAL_PLUGIN_VERSION . ", WordPress/" . $wp_version;
		}

		$response = null;

		switch ( $method ) {
			case 'GET':
				$args = [
					'method'  => 'GET',
					'headers' => $headers,
					//	'body'    => $body,
					'timeout' => 30,
				];

				$response = wp_remote_request( $url, $args );
				break;
			case 'POST':
				$args     = [
					'method'  => 'POST',
					'headers' => $headers,
					'body'    => $body,
					'timeout' => 30,
				];
				$response = wp_remote_post( $url, $args );
				break;
			case 'PUT':
			case 'DELETE':
				break;
		}

		// Throw wordpress error
		if ( is_wp_error( $response ) ) {
			$errors = $response->get_error_messages();

			return $errors;
		}

		return $response;
	}


	public static function get_credits() {
		$apikey = get_option( 'smsglobal_key' );
		$secret = get_option( 'smsglobal_secret' );

		if ( empty( $apikey ) || empty( $secret ) ) {
			return 0;
		}

		//send login request
		$url    = 'https://api.smsglobal.com/v2/user/credit-balance';
		$method = 'GET';

		$body = json_encode( array() );

		$headers['Accept']       = 'application/json';
		$headers['Content-Type'] = 'application/json';
		$response                = self::makeRequest( $body, $method, $url, $headers );

		if (isset($response['body'])) {
		    $response = $response['body'];
        }

		return $response;
	}

	/**
	 * @param string $method
	 * @param string $uri
	 * @param string $host
	 * @param int $port
	 * @param string $extraData
	 *
	 * @return string
	 */
	public static function generateMacHeader( $method = 'POST', $uri = '/v2/sms/', $host = 'api.smsglobal.com', $port = 443, $extraData = '' ) {
		$apikey     = get_option( 'smsglobal_key' );
		$secret     = get_option( 'smsglobal_secret' );
		$timestamp  = time();
		$nonce      = self::createRandomString();
		$rawString  = $timestamp . "\n" . $nonce . "\n" . $method . "\n" . $uri . "\n" . $host . "\n" . $port . "\n" . $extraData . "\n";
		$hashHeader = base64_encode( hash_hmac( 'sha256', $rawString, $secret, true ) );

		return "MAC id=\"$apikey\", ts=\"$timestamp\", nonce=\"$nonce\", mac=\"$hashHeader\"";


	}

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	public static function createRandomString( $length = 10 ) {
		$result   = '';
		$CHAR_MAP = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTWXYZ0123456789';
		$size     = strlen( $CHAR_MAP );
		for ( $i = 0; $i < $length; $i ++ ) {
			$result .= $CHAR_MAP[ rand( 0, $size - 1 ) ];
		}

		return $result;
	}
}