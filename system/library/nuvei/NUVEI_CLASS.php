<?php

/**
 * NUVEI_CLASS Class
 * 
 * A class for work with Nuvei REST API.
 * 
 * @author Nuvei
 */

define('NUVEI_PLUGIN_V',            '1.1');
define('NUVEI_PLUGIN_CODE',         'nuvei');
define('NUVEI_PLUGIN_TITLE',        'Nuvei');

define('NUVEI_LIVE_URL_BASE',       'https://secure.safecharge.com/ppp/api/v1/');
define('NUVEI_TEST_URL_BASE',       'https://ppp-test.safecharge.com/ppp/api/v1/');

define('NUVEI_SDK_URL_INT',         'https://srv-bsf-devpppjs.gw-4u.com/checkoutNext/checkout.js');
define('NUVEI_SDK_URL_PROD',        'https://cdn.safecharge.com/safecharge_resources/v1/checkout/checkout.js');

define('NUVEI_SDK_AUTOCLOSE_URL',   'https://cdn.safecharge.com/safecharge_resources/v1/websdk/autoclose.html');

define('NUVEI_AUTH_CODE',           '_authCode');
define('NUVEI_TRANS_ID',            '_transactionId');
define('NUVEI_TRANS_TYPE',          '_transactionType');

// if change the both consts above, change the ajaxURL in admin nuvei_order.js
define('NUVEI_TOKEN_NAME',          'user_token');
define('NUVEI_CONTROLLER_PATH',     'extension/payment/nuvei');

define('NUVEI_SETTINGS_PREFIX',     'payment_nuvei_');
define('NUVEI_SOURCE_APP',          'OPENCART_3_0_PLUGIN');

//define('NUVEI_ADMIN_TXT_EXT_KEY',   'text_extension');
define('NUVEI_ADMIN_EXT_URL',       'marketplace/extension');

class NUVEI_CLASS
{
    private static $trace_id;
    
	// array details to validate request parameters
    private static $params_validation = array(
        // deviceDetails
        'deviceType' => array(
            'length' => 10,
            'flag'    => FILTER_SANITIZE_STRING
        ),
        'deviceName' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
        'deviceOS' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
        'browser' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
//        'ipAddress' => array(
//            'length' => 15,
//            'flag'    => FILTER_VALIDATE_IP
//        ),
        // deviceDetails END
        
        // userDetails, shippingAddress, billingAddress
        'firstName' => array(
            'length' => 30,
            'flag'    => FILTER_DEFAULT
        ),
        'lastName' => array(
            'length' => 40,
            'flag'    => FILTER_DEFAULT
        ),
        'address' => array(
            'length' => 60,
            'flag'    => FILTER_DEFAULT
        ),
        'cell' => array(
            'length' => 18,
            'flag'    => FILTER_DEFAULT
        ),
        'phone' => array(
            'length' => 18,
            'flag'    => FILTER_DEFAULT
        ),
        'zip' => array(
            'length' => 10,
            'flag'    => FILTER_DEFAULT
        ),
        'city' => array(
            'length' => 30,
            'flag'    => FILTER_DEFAULT
        ),
        'country' => array(
            'length' => 20,
            'flag'    => FILTER_SANITIZE_STRING
        ),
        'state' => array(
            'length' => 2,
            'flag'    => FILTER_SANITIZE_STRING
        ),
        'county' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
        // userDetails, shippingAddress, billingAddress END
        
        // specific for shippingAddress
        'shippingCounty' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
        'addressLine2' => array(
            'length' => 50,
            'flag'    => FILTER_DEFAULT
        ),
        'addressLine3' => array(
            'length' => 50,
            'flag'    => FILTER_DEFAULT
        ),
        // specific for shippingAddress END
        
        // urlDetails
        'successUrl' => array(
            'length' => 1000,
            'flag'    => FILTER_VALIDATE_URL
        ),
        'failureUrl' => array(
            'length' => 1000,
            'flag'    => FILTER_VALIDATE_URL
        ),
        'pendingUrl' => array(
            'length' => 1000,
            'flag'    => FILTER_VALIDATE_URL
        ),
        'notificationUrl' => array(
            'length' => 1000,
            'flag'    => FILTER_VALIDATE_URL
        ),
        // urlDetails END
    );
	
	private static $params_validation_email = array(
		'length'	=> 79,
		'flag'		=> FILTER_VALIDATE_EMAIL
	);
	
    private static $devices = array('iphone', 'ipad', 'android', 'silk', 'blackberry', 'touch', 'linux', 'windows', 'mac');
    
    private static $browsers = array('ucbrowser', 'firefox', 'chrome', 'opera', 'msie', 'edge', 'safari', 'blackberry', 'trident');
    
    private static $device_types = array('macintosh', 'tablet', 'mobile', 'tv', 'windows', 'linux', 'tv', 'smarttv', 'googletv', 'appletv', 'hbbtv', 'pov_tv', 'netcast.tv', 'bluray');
    
    /**
	 * Call REST API with cURL post and get response.
	 * The URL depends from the case.
	 *
	 * @param string $method
	 * @param array $settings       The plugin settings
	 * @param array $checsum_params The parameters for Checksum
	 * @param array $params         Specific method parameters
	 *
	 * @return array
	 */
    public static function call_rest_api($method, array $settings, array $checsum_params, array $params = [])
    {
        if(empty($method)) {
			self::create_log($settings, 'call_rest_api() Error - the passed method can not be empty.');
			return array('status' => 'ERROR');
		}
        
        $url = self::get_endpoint_base($settings) . $method . '.do';
		
		if(!is_array($params)) {
			self::create_log($params, 'callRestApi() Error - the passed params parameter is not array.');
			return array('status' => 'ERROR');
		}
        
        if(empty($settings[NUVEI_SETTINGS_PREFIX . 'hash'])) {
            self::create_log($params, 'callRestApi() Error - the hash params parameter is empty.');
            return array('status' => 'ERROR');
        }
        
        $time = date('YmdHis', time());
       
        // set here some of the mandatory parameters
        $params = array_merge_recursive(
            array(
                'merchantId'        => $settings[NUVEI_SETTINGS_PREFIX . 'merchantId'],
                'merchantSiteId'    => $settings[NUVEI_SETTINGS_PREFIX . 'merchantSiteId'],
                'timeStamp'         => $time,
                'webMasterId'       => 'OpenCart ' . VERSION,
                'sourceApplication' => NUVEI_SOURCE_APP,
                
                'merchantDetails'	=> array(
					'customField1' => NUVEI_SOURCE_APP . ' ' . NUVEI_PLUGIN_V,
					'customField2' => $time, // time when we create request
				),
                
                'deviceDetails'     => self::get_device_details(),
            ),
            $params
        );
        
        // calculate the checksum
        $concat = '';
        
        foreach($checsum_params as $key) {
            if(!isset($params[$key])) {
                self::create_log(
                    $settings,
                    array(
                        'request url'   => $url,
                        'params'        => $params,
                        'missing key'   => $key,
                    ),
                    'Error - Missing a mandatory parameter for the Checksum:'
                );
                
                return array('status' => 'ERROR');
            }
            
            $concat .= $params[$key];
        }
        
        $concat .= $settings[NUVEI_SETTINGS_PREFIX . 'secret'];
        
        $params['checksum'] = hash($settings[NUVEI_SETTINGS_PREFIX . 'hash'], $concat);
        // /calculate the checksum
        
        // validate parameters
        $params = self::validate_params($params);
        
        if(isset($params['status']) && 'ERROR' == $params['status']) {
            return $params;
        }
        
        self::create_log(
            $settings,
            array(
				'url'       => $url,
				'params'    => $params,
			)
            , 'REST API request'
        );
        // /validate parameters
        
        $json_post = json_encode($params);
        
        try {
            $header =  array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_post),
            );
            
            // create cURL post
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $resp = curl_exec($ch);
            curl_close ($ch);
			
			$resp_arr = json_decode($resp, true);
            
            self::create_log($settings, $resp_arr, 'REST API Response: ');
			
			return $resp_arr;
        }
        catch(Exception $e) {
            self::create_log($e->getMessage(), 'Call REST API Exception');
			
            return array('status' => 'ERROR');
        }
    }
    
    /**
     * Function get_device_details
	 * 
     * Get browser and device based on HTTP_USER_AGENT.
     * The method is based on D3D payment needs.
     * 
     * @return array $device_details
     */
    public static function get_device_details()
    {
        $device_details = array(
            'deviceType'    => 'UNKNOWN', // DESKTOP, SMARTPHONE, TABLET, TV, and UNKNOWN
            'deviceName'    => 'UNKNOWN',
			'deviceOS'      => 'UNKNOWN',
			'browser'       => 'UNKNOWN',
			'ipAddress'     => '0.0.0.0',
        );
        
        if(empty($_SERVER['HTTP_USER_AGENT'])) {
			$device_details['Warning'] = 'User Agent is empty.';
			
			self::create_log($device_details['Warning'], 'get_device_details() Error');
			return $device_details;
		}
		
		$user_agent = strtolower(filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING));
		
		if (empty($user_agent)) {
			$device_details['Warning'] = 'Probably the merchant Server has problems with PHP filter_var function!';
			
			self::create_log($device_details['Warning'], 'get_device_details() Error');
			return $device_details;
		}
		
		$device_details['deviceName'] = $user_agent;
		
        foreach (self::$device_types as $d) {
            if (strstr($user_agent, $d) !== false) {
                if(in_array($d, array('linux', 'windows', 'macintosh'), true)) {
                    $device_details['deviceType'] = 'DESKTOP';
                } else if('mobile' === $d) {
                    $device_details['deviceType'] = 'SMARTPHONE';
                } else if('tablet' === $d) {
                    $device_details['deviceType'] = 'TABLET';
                } else {
                    $device_details['deviceType'] = 'TV';
                }

                break;
            }
        }

        foreach (self::$devices as $d) {
            if (strstr($user_agent, $d) !== false) {
                $device_details['deviceOS'] = $d;
                break;
            }
        }

        foreach (self::$browsers as $b) {
            if (strstr($user_agent, $b) !== false) {
                $device_details['browser'] = $b;
                break;
            }
        }

        // get ip
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
		}
		if (empty($ip_address) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip_address = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
		}
		if (empty($ip_address) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip_address = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
		}
		if (!empty($ip_address)) {
			$device_details['ipAddress'] = (string) $ip_address;
		}
            
        return $device_details;
    }
    
    /**
     * function get_param
     * 
     * Helper function to safety access request parameters
     * 
     * @param type $name
     * @param type $filter
     * 
     * @return mixed
     */
    public static function get_param($name, $filter = FILTER_DEFAULT)
    {
        $val = filter_input(INPUT_GET, $name, $filter);
        
        if(null === $val || false === $val) {
            $val = filter_input(INPUT_POST, $name, $filter);
        }
        
        if(null === $val || false === $val) {
            return false;
        }
        
        return $val;
    }
    
	/**
     * Create plugin logs.
	 * 
     * @param array $settings   The plugin settings
     * @param mixed $data       The data to save in the log.
     * @param string $message   Record message.
     * @param string $log_level The Log level.
     * @param string $span_id   Process unique ID.
     */
    public static function create_log($settings, $data, $message = '', $log_level = 'INFO', $span_id = '')
	{
        // it is defined in OC config.php file
        if(!defined('DIR_LOGS') || !is_dir(DIR_LOGS)) {
            return;
        }
        
        // is logging enabled
        $log_files = '';
        
        if(isset($settings[NUVEI_SETTINGS_PREFIX . 'create_logs'])) {
            $log_files = $settings[NUVEI_SETTINGS_PREFIX . 'create_logs'];
        }
        
        if(empty($log_files) || 'no' == $log_files) {
            return;
        }

        // can we save DEBUG logs
        $test_mode = 0;
        
        if(!empty($settings[NUVEI_SETTINGS_PREFIX . 'test_mode'])) {
            $test_mode = $settings[NUVEI_SETTINGS_PREFIX . 'test_mode'];
        }
        
        if('DEBUG' == $log_level && 0 == $test_mode) {
            return;
        }
        // /can we save DEBUG logs
        
        $beauty_log = (1 == $test_mode) ? true : false;
        $tab        = '    '; // 4 spaces
        
        # prepare log parts
        $utimestamp     = microtime(true);
        $timestamp      = floor($utimestamp);
        $milliseconds   = round(($utimestamp - $timestamp) * 1000000);
        $record_time    = date('Y-m-d') . 'T' . date('H:i:s') . '.' . $milliseconds . date('P');
        
        if(null == self::$trace_id) {
            self::$trace_id = bin2hex(random_bytes(16));
        }
        
        if(!empty($span_id)) {
            $span_id .= $tab;
        }
        
        $machine_name       = '';
        $service_name       = NUVEI_SOURCE_APP . ' ' . NUVEI_PLUGIN_V . '|';
        $source_file_name   = '';
        $member_name        = '';
        $source_line_number = '';
        $backtrace          = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        
        if(!empty($backtrace)) {
            if(!empty($backtrace[0]['file'])) {
                $file_path_arr  = explode(DIRECTORY_SEPARATOR, $backtrace[0]['file']);
                
                if(!empty($file_path_arr)) {
                    $source_file_name = end($file_path_arr) . '|';
                }
            }
            
            if(!empty($backtrace[0]['line'])) {
                $source_line_number = $backtrace[0]['line'] . $tab;
            }
        }
        
        if(!empty($message)) {
            $message .= $tab;
        }
        
        if(is_array($data)) {
            // paymentMethods can be very big array
            if(!empty($data['paymentMethods'])) {
                $exception = json_encode($data);
            }
            else {
                $exception = $beauty_log ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data);
            }
        }
        elseif(is_object($data)) {
            $data_tmp   = print_r($data, true);
            $exception  = $beauty_log ? json_encode($data_tmp, JSON_PRETTY_PRINT) : json_encode($data_tmp);
        }
        elseif(is_bool($data)) {
            $exception = $data ? 'true' : 'false';
        }
        elseif(is_string($data)) {
            $exception = false === strpos($data, 'http') ? $data : urldecode($data);
        }
        else {
            $exception = $data;
        }
        # prepare log parts END
        
        // Content of the log string:
        $string = $record_time      // timestamp
            . $tab                  // tab
            . $log_level            // level
            . $tab                  // tab
            . self::$trace_id       // TraceId
            . $tab                  // tab
            . $span_id              // SpanId, if not empty it will include $tab
//            . $parent_id            // ParentId, if not empty it will include $tab
            . $machine_name         // MachineName if not empty it will include a "|"
            . $service_name         // ServiceName if not empty it will include a "|"
            // TreadId
            . $source_file_name     // SourceFileName if not empty it will include a "|"
            . $member_name          // MemberName if not empty it will include a "|"
            . $source_line_number   // SourceLineName if not empty it will include $tab
            // RequestPath
            // RequestId
            . $message
            . $exception            // the exception, in our case - data to print
        ;
        
        $string     .= "\r\n\r\n";
        $file_name  = 'nuvei.log';
        
        if($log_files == 'both') {
            // save the single file, then the daily
            try {
                file_put_contents(DIR_LOGS . $file_name, $string, FILE_APPEND);
            }
            catch (Exception $exc) {}
            
            $file_name = 'nuvei-' . date('Y-m-d', time()) . '.log';
        }
        
        if($log_files == 'daily') {
            $file_name = 'nuvei-' . date('Y-m-d', time()) . '.log';
        }
        
		try {
			file_put_contents(DIR_LOGS . $file_name, $string, FILE_APPEND);
		}
		catch (Exception $exc) {}
	}
    
    /**
	 * Get the URL to the endpoint, without the method name, based on the site mode.
	 * 
     * @param array $settings The plugin settings.
	 * @return string
	 */
	private static function get_endpoint_base(array $settings)
    {
		if (1 == @$settings[NUVEI_SETTINGS_PREFIX . 'test_mode']) {
			return NUVEI_TEST_URL_BASE;
		}
		
		return NUVEI_LIVE_URL_BASE;
	}
    
    /**
     * Just move out the validation outside of call_rest_api method.
     * 
     * @param array $params
     * @return array $params
     */
    private static function validate_params(array $params)
    {
		# validate parameters
		// directly check the mails
		if(isset($params['billingAddress']['email'])) {
			if(!filter_var($params['billingAddress']['email'], self::$params_validation_email['flag'])) {
				self::create_log('call_rest_api() Error - Billing Address Email is not valid.');
				
				return array(
					'status' => 'ERROR',
					'message' => 'Billing Address Email is not valid.'
				);
			}
			
			if(strlen($params['billingAddress']['email']) > self::$params_validation_email['length']) {
				self::create_log('call_rest_api() Error - Billing Address Email is too long');
				
				return array(
					'status' => 'ERROR',
					'message' => 'Billing Address Email is too long.'
				);
			}
		}
		
		if(isset($params['shippingAddress']['email'])) {
			if(!filter_var($params['shippingAddress']['email'], self::$params_validation_email['flag'])) {
				self::create_log('call_rest_api() Error - Shipping Address Email is not valid.');
				
				return array(
					'status' => 'ERROR',
					'message' => 'Shipping Address Email is not valid.'
				);
			}
			
			if(strlen($params['shippingAddress']['email']) > self::$params_validation_email['length']) {
				self::create_log('call_rest_api() Error - Shipping Address Email is too long.');
				
				return array(
					'status' => 'ERROR',
					'message' => 'Shipping Address Email is too long'
				);
			}
		}
		// directly check the mails END
		
		foreach ($params as $key1 => $val1) {
            if (!is_array($val1) && !empty($val1) && array_key_exists($key1, self::$params_validation)) {
                $new_val = $val1;
                
                if (mb_strlen($val1) > self::$params_validation[$key1]['length']) {
                    $new_val = mb_substr($val1, 0, self::$params_validation[$key1]['length']);
                    
                    self::create_log($key1, 'Limit');
                }
                
                $params[$key1] = filter_var($new_val, self::$params_validation[$key1]['flag']);
            }
			elseif (is_array($val1) && !empty($val1)) {
                foreach ($val1 as $key2 => $val2) {
                    if (!is_array($val2) && !empty($val2) && array_key_exists($key2, self::$params_validation)) {
                        $new_val = $val2;

                        if (mb_strlen($val2) > self::$params_validation[$key2]['length']) {
                            $new_val = mb_substr($val2, 0, self::$params_validation[$key2]['length']);
                            
                            self::create_log($key2, 'Limit');
                        }

                        $params[$key1][$key2] = filter_var($new_val, self::$params_validation[$key2]['flag']);
                    }
                }
            }
        }
		# validate parameters END
        
        return $params;
    }
}
