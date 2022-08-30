<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR . 'nuvei' 
    . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

class ControllerExtensionPaymentNuvei extends Controller
{
    private $is_user_logged;
	private $order_info;
    private $plugin_settings    = [];
    private $order_addresses    = [];
    
	public function index()
    {
        $this->load->model('checkout/order');
		$this->load->model('account/reward');
        $this->load_settings();
//        $this->load->model('setting/setting');
        $this->language->load(NUVEI_CONTROLLER_PATH);
        
        $this->order_info       = $this->model_checkout_order->getOrder($this->session->data['order_id']);
//        $this->plugin_settings  = $this->model_setting_setting->getSetting(trim(NUVEI_SETTINGS_PREFIX, '_'));
        $this->order_addresses  = $this->get_order_addresses();
        $this->is_user_logged   = !empty($this->session->data['customer_id']) ? 1 : 0;
		
		// detect ajax call when we need new Open Order
//        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
//			&& 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH']
//			&& NUVEI_CLASS::get_param('route') == NUVEI_CONTROLLER_PATH
//		) {
        if(isset($this->request->server['HTTP_X_REQUESTED_WITH'])
            && 'XMLHttpRequest' == $this->request->server['HTTP_X_REQUESTED_WITH']
            && NUVEI_CONTROLLER_PATH == $this->request->get['route']
        ) {
            $this->ajax_call();
            exit;
        }
        
        // Open Order
        $order_data = $this->open_order();
		
		if(empty($order_data) || empty($order_data['sessionToken'])) {
			NUVEI_CLASS::create_log($this->plugin_settings, $order_data, 'Open Order problem with the response', 'CRITICAL');
			
			exit('<div class="alert alert-danger">'. $this->language->get('error_nuvei_gateway') .'</div>');
		}
        
        $pm_black_list = [];
        
        if(!empty($this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'block_pms'])
            && is_array($this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'block_pms'])
        ) {
            $pm_black_list = $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'block_pms'];
        }
        
        $items_with_plan_data   = []; //@$this->check_for_product_with_plan(); // TODO
        $use_upos               = $save_pm = (bool) $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'use_upos'];
        
        if(0 == $this->is_user_logged) {
            $use_upos = $save_pm = false;
        }
        elseif(!empty($items_with_plan_data['item_with_plan'])) {
            $save_pm = 'always';
        }
        
        // set the template data
        $data = $this->load->language(NUVEI_CONTROLLER_PATH);
        
        $data['nuvei_sdk_params'] = [
            'renderTo'               => '#nuvei_checkout',
            'strict'                 => false,
            'alwaysCollectCvv'       => true,
            'maskCvv'                => true,
            'showResponseMessage'    => false,
            'sessionToken'           => $order_data['sessionToken'],
            'env'                    => 1 == $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'test_mode'] ? 'test' : 'prod',
            'merchantId'             => $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'merchantId'],
            'merchantSiteId'         => $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'merchantSiteId'],
            'country'                => $order_data['billingAddress']['country'],
            'currency'               => $order_data['currency'],
            'amount'                 => $order_data['amount'],
            'useDCC'                 => $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'use_dcc'],
            'savePM'                 => $save_pm,
            'showUserPaymentOptions' => $use_upos,
            'pmWhitelist'            => null,
            'pmBlacklist'            => $pm_black_list,
            'email'                  => $order_data['billingAddress']['email'],
            'fullName'               => trim($order_data['billingAddress']['firstName'] 
                . ' ' . $order_data['billingAddress']['lastName']),
            'payButton'              => $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'pay_btn_text'],
            'locale'                 => substr($this->get_locale(), 0, 2),
            'autoOpenPM'             => (bool) $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'auto_expand_pms'],
            'logLevel'               => $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'sdk_log_level'],
            'i18n'                   => json_decode($this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'sdk_transl'], true),
            'billingAddress'         => $order_data['billingAddress'],
            'userData'               => ['billingAddress' => $order_data['billingAddress']],
        ];
        
        $data['action'] = $this->url->link(NUVEI_CONTROLLER_PATH . '/process_payment')
			. '&order_id=' . $this->session->data['order_id'];
        
        if('prod' != $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'sdk_version']) {
            $data['nuvei_sdk_params']['webSdkEnv'] = 'dev';
        }
        
        // TODO check for product with a plan
        // if there are product with plan:
        // $data['nuvei_sdk_params']['pmWhitelist'][] = 'cc_card';
//         unset($data['nuvei_sdk_params']['pmBlacklist']);
        // /TODO check for product with a plan
        
        // TODO blocked_cards
        
//        NUVEI_CLASS::create_log($this->plugin_settings, $data, 'SDK parameters');
        
        // load common php template and then pass it to the real template
        // as single variable. The form is same for both versions
        $tpl_path = 'default/template/'  . NUVEI_CONTROLLER_PATH;
        
        ob_start();
        require DIR_TEMPLATE . $tpl_path . '.php';
        return ob_get_clean(); // the template of OC wants array
	}
    
    // on success add history note for the order
    public function success()
    {
        $this->load_settings();
        $this->load->model('checkout/order');
        $this->language->load(NUVEI_CONTROLLER_PATH);
        
        NUVEI_CLASS::create_log($this->plugin_settings, 'success page');
        
		if(!empty($this->request->get['order_id'])) {
			$order_id = (int) $this->request->get['order_id'];
		}
		elseif(NUVEI_CLASS::get_param('invoice_id')) {
			$arr		= explode("_", NUVEI_CLASS::get_param('invoice_id'));
			$order_id	= (int) $arr[0];
		}
		else {
			NUVEI_CLASS::create_log(
                $this->plugin_settings,
                @$_REQUEST,
                'Success Error - can not recognize order ID.'
            );
			
            $this->response->redirect($this->url->link(NUVEI_CONTROLLER_PATH . '/fail'));
		}
		
		$this->order_info = $this->model_checkout_order->getOrder($order_id);
        
        if(isset($this->order_info['order_status_id'])
            && (int) $this->order_info['order_status_id'] == 0
        ) {
			$this->model_checkout_order->addOrderHistory(
				$order_id,
                $this->order_info['order_status_id'],
				$this->language->get('nuvei_payment_complete'),
				true
			);
        }
        
        $this->response->redirect($this->url->link('checkout/success') . '&order_id=' . $order_id);
    }
    
    /*
     * Function fail()
     * When order fail came here.
     */
    public function fail()
	{
        $this->load_settings();
        $this->language->load(NUVEI_CONTROLLER_PATH);
        
        NUVEI_CLASS::create_log($this->plugin_settings, @$_REQUEST, 'Order FAIL');
        
		if(!empty($this->request->get['order_id'])) {
			$order_id = intval($this->request->get['order_id']);
		}
		elseif(NUVEI_CLASS::get_param('invoice_id')) {
			$arr		= explode("_", NUVEI_CLASS::get_param('invoice_id'));
			$order_id	= (int) $arr[0];
		}
		else {
			$this->session->data['error']= $this->language->get('nuvei_payment_faild');
			$this->response->redirect($this->url->link('checkout/cart'));
		}
		
		$this->load->model('checkout/order');
		$this->order_info = $this->model_checkout_order->getOrder($order_id);

		if ($this->order_info) {
            $this->change_order_status($order_id, 'FAIL');
		}

		$this->session->data['error']= $this->language->get('nuvei_payment_faild');
        
        $this->response->redirect($this->url->link('checkout/cart'));
	}
    
    /**
     * Receive DMNs here
     */
	public function callback()
    {
        $this->load_settings();
        $this->load->model('checkout/order');
        
        NUVEI_CLASS::create_log($this->plugin_settings, @$_REQUEST, 'DMN request');
        
        ### Manual stop DMN is possible only in test mode
//        if('yes' == $_SESSION['nuvei_test_mode']) {
//            NUVEI_CLASS::create_log($this->plugin_settings, http_build_query(@$_REQUEST), 'DMN request query');
//            die('manually stoped');
//        }
        ### Manual stop DMN END
        
        $order_id               = 0;
        $trans_type             = NUVEI_CLASS::get_param('transactionType', FILTER_SANITIZE_STRING);
        $trans_id               = (int) NUVEI_CLASS::get_param('TransactionID');
        $relatedTransactionId   = (int) NUVEI_CLASS::get_param('relatedTransactionId');
        $merchant_unique_id     = NUVEI_CLASS::get_param('merchant_unique_id');
        $req_status             = $this->get_request_status();
		
		if(!$trans_type) {
            $this->return_message('DMN report: Transaction Type is empty');
		}
        
        if(empty($req_status)) {
            $this->return_message('DMN report: the Status parameter is empty.');
		}
		
		if('pending' == strtolower($req_status)) {
            $this->return_message('DMN status is Pending. Wait for another status.');
		}
		
        if(!$this->checkAdvancedCheckSum()) {
            $this->return_message('DMN report: You receive DMN from not trusted source. The process ends here.');
        }
        
        // find Order ID
        if(!empty($_REQUEST['order_id'])) {
			$order_id = (int) $_REQUEST['order_id'];
		}
        elseif(!empty($merchant_unique_id) && false === strpos($merchant_unique_id, 'gwp_')) {
            if(is_numeric($merchant_unique_id)) {
                $order_id = (int) $merchant_unique_id;
            }
            // beacause of the modified merchant_unique_id - PayPal problem
            elseif(strpos($merchant_unique_id, '_') !== false) {
                $order_id_arr = explode('_', $merchant_unique_id);
                
                if(is_numeric($order_id_arr[0])) {
                    $order_id = (int) $order_id_arr[0];
                }
            }
        }
		else {
			$q = 'SELECT order_id FROM ' . DB_PREFIX . 'order '
                . 'WHERE custom_field = ' . $relatedTransactionId;
			
			$query = $this->db->query($q);
            
            NUVEI_CLASS::create_log($this->plugin_settings, @$query->row);
            $order_id   = (int) $query->row['order_id'];
		}
        
        if($order_id == 0 || !is_numeric($order_id)) {
            $this->return_message('DMN error - invalid Order ID.');
        }
        
        // get Order info
        try {
            $this->order_info = $this->model_checkout_order->getOrder($order_id);
            
            if(!$this->order_info || empty($this->order_info)) {
                http_response_code(400);
                $this->return_message('DMN error - there is no order info.');
            }
            
            // check for Nuvei Order
            if($this->order_info['payment_code'] != 'nuvei') {
                $this->return_message('DMN error - the Order does not belongs to the Nuvei.');
            }
        }
        catch (Exception $ex) {
            NUVEI_CLASS::create_log($this->plugin_settings, $ex->getMessage(), 'Exception', 'WARN');
            
            http_response_code(400);
            $this->return_message('DMN Exception', $ex->getMessage());
        }
        
        // do not override Order status
        if($this->order_info['order_status_id'] > 0
            && $this->order_info['order_status_id'] != $this->config->get(NUVEI_SETTINGS_PREFIX . 'pending_status_id')
            && 'pending' == strtolower($req_status)
        ) {
            $this->return_message('DMN Message - do not override current Order status with Pending');
        }
        
        # in Case of CPanel Refund DMN
        if(in_array($trans_type, array('Credit', 'Refund'))
            && strpos(NUVEI_CLASS::get_param('clientUniqueId'), 'gwp_') !== false
        ) {
            $this->model_checkout_order->addOrderHistory(
                $order_id,
                $this->order_info['order_status_id'],
                $this->language->get('CPanel Refund detected. Please, create a manual refund!'),
                false
            );

            $this->return_message('DMN received.');
        }
        # in Case of CPanel Refund DMN END
        
        # add new data into payment_custom_field
        $order_data = $this->order_info['payment_custom_field'];
        
        NUVEI_CLASS::create_log($this->plugin_settings, $order_data, 'callback() payment_custom_field');
        
        if(empty($order_data)) {
            $order_data = array();
        }
        // prevent dublicate data
        else {
            foreach($order_data as $trans) {
                if($trans['transactionId'] == $trans_id
                    && $trans['transactionType'] == $trans_type
                    && $trans['status'] == strtolower($req_status)
                ) {
                    NUVEI_CLASS::create_log(
                        $this->plugin_settings, 
                        'Dublicate DMN. We already have this information. Stop here.'
                    );
                    
                    $this->return_message('Dublicate DMN. We already have this information. Stop here.');
                }
            }
        }
        
        $order_data[] = array(
            'status'                => strtolower((string) $req_status),
            'clientUniqueId'        => NUVEI_CLASS::get_param('clientUniqueId', FILTER_SANITIZE_STRING),
            'transactionType'       => $trans_type,
            'transactionId'         => $trans_id,
            'relatedTransactionId'  => $relatedTransactionId,
            'userPaymentOptionId'   => (int) NUVEI_CLASS::get_param('userPaymentOptionId'),
            'authCode'              => (int) NUVEI_CLASS::get_param('AuthCode'),
            'totalAmount'           => round((float) NUVEI_CLASS::get_param('totalAmount'), 2),
            'currency'              => NUVEI_CLASS::get_param('currency', FILTER_SANITIZE_STRING),
            'paymentMethod'         => NUVEI_CLASS::get_param('payment_method', FILTER_SANITIZE_STRING),
            'responseTimeStamp'     => NUVEI_CLASS::get_param('responseTimeStamp', FILTER_SANITIZE_STRING),
        );
        
        // all data
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "order` "
            . "SET payment_custom_field = '" . json_encode($order_data) . "' "
            . "WHERE order_id = " . $order_id
        );
        
        // add only transaction ID if the transactions is Auth, Settle or Sale
        if(in_array($trans_type, array('Auth', 'Settle', 'Sale'))) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "order` "
                . "SET custom_field = '" . $trans_id . "' "
                . "WHERE order_id = " . $order_id
            );
        }
        # add new data into payment_custom_field END
		
        # Sale and Auth
        if(in_array($trans_type, array('Sale', 'Auth'))) {
            NUVEI_CLASS::create_log(
                $this->plugin_settings,
                array(
                    'order_status_id' => $this->order_info['order_status_id'],
                    'default complete status' => $this->config->get(NUVEI_SETTINGS_PREFIX . 'order_status_id'),
                ),
                'DMN Sale/Auth compare order status and default complete status:'
            );
            
			// if is different than the default Complete status
			if($this->order_info['order_status_id'] 
                != $this->config->get(NUVEI_SETTINGS_PREFIX . 'order_status_id')
            ) {
				$this->change_order_status($order_id, $req_status, $trans_type);
			}
            
            $this->return_message('DMN received.');
        }
        
        # Refund
        if(in_array($trans_type, array('Credit', 'Refund'))) {
            $this->change_order_status($order_id, $req_status, 'Credit');
            $this->return_message('DMN received.');
        }
        
        # Void, Settle
        if(in_array($trans_type, array('Void', 'Settle'))) {
            $this->change_order_status($order_id, $req_status, $trans_type);
			$this->return_message('DMN received.');
        }
        
        $this->return_message('DMN was not recognized!');
	}
    
    /**
     * Function process_payment
	 * 
     * We use this method with REST API.
     * Here we send the data from the form and prepare it before send it to the API.
     */
    public function process_payment()
    {
        $this->load_settings();
        $this->load->model('checkout/order');
        
        NUVEI_CLASS::create_log($this->plugin_settings, @$_POST, 'process_payment()');
        
        $this->session->data['nuvei_last_oo_details'] = [];
		$this->order_info = $this->model_checkout_order->getOrder($this->request->get['order_id']);
		
		$success_url    = $this->url->link(NUVEI_CONTROLLER_PATH . '/success') 
            . '&order_id=' . $this->request->get['order_id'];
		
//        $pending_url    = $success_url;
		
        $error_url      = $this->url->link(NUVEI_CONTROLLER_PATH . '/fail') 
            . '&order_id=' . $this->request->get['order_id'];
		
//        $back_url       = $this->url->link('checkout/checkout', '', true);
//        $notify_url     = $this->url->link(NUVEI_CONTROLLER_PATH . '/callback');
		
		if(!empty($this->request->post['sc_transaction_id'])
            && is_numeric($this->request->post['sc_transaction_id'])
		) {
			$this->response->redirect($success_url);
		}
        
        $this->response->redirect($error_url);
		
        /*
		# APMs
        $this->language->load(NUVEI_CONTROLLER_PATH);
        $data['process_payment'] = $this->language->get('Processing the payment. Please, wait!');
        
        $TimeStamp = date('YmdHis', time());
		
		$total_amount = $this->currency->format(
            $this->order_info['total'],
            $this->order_info['currency_code'],
            $this->order_info['currency_value'],
            false
        );
        
        if($total_amount < 0) {
            $total_amount = number_format(0, 2, '.', '');
        }
        else {
            $total_amount = number_format($total_amount, 2, '.', '');
        }
		
		$countriesWithStates = array('US', 'IN', 'CA');
		
		$state = preg_replace("/[[:punct:]]/", '', substr($this->order_info['payment_zone'], 0, 2));
		if (in_array($this->order_info['payment_iso_code_2'], $countriesWithStates)) {
			$state = $this->order_info['payment_zone_code'];
		}
        
		$params = array(
			'merchantId'        => $this->config->get(NUVEI_SETTINGS_PREFIX . 'merchantId'),
			'merchantSiteId'    => $this->config->get(NUVEI_SETTINGS_PREFIX . 'merchantSiteId'),
			'clientUniqueId'    => $this->request->get['order_id'] . '_' . uniqid(),
			'merchant_unique_id'=> $this->request->get['order_id'],
			'clientRequestId'   => $TimeStamp . '_' . uniqid(),
			'currency'          => $this->order_info['currency_code'],
			'amount'            => (string) $total_amount,
			'amountDetails'     => array(
				'totalShipping'     => '0.00',
				'totalHandling'     => '0.00',
				'totalDiscount'     => '0.00',
				'totalTax'          => '0.00',
			),
			'userDetails'       => array(
				'firstName'         => preg_replace("/[[:punct:]]/", '', $this->order_info['payment_firstname']),
				'lastName'          => preg_replace("/[[:punct:]]/", '', $this->order_info['payment_lastname']),
				'address'           => preg_replace("/[[:punct:]]/", '', $this->order_info['payment_address_1']),
				'phone'             => preg_replace("/[[:punct:]]/", '', $this->order_info['telephone']),
				'zip'               => preg_replace("/[[:punct:]]/", '', $this->order_info['payment_postcode']),
				'city'              => preg_replace("/[[:punct:]]/", '', $this->order_info['payment_city']),
				'country'           => $this->order_info['payment_iso_code_2'],
				'state'             => $state,
				'email'             => $this->order_info['email'],
				'county'            => '',
			),
			'shippingAddress'   => array(
				'firstName'         => preg_replace("/[[:punct:]]/", '', $this->order_info['shipping_firstname']),
				'lastName'          => preg_replace("/[[:punct:]]/", '', $this->order_info['shipping_lastname']),
				'address'           => preg_replace("/[[:punct:]]/", '', $this->order_info['shipping_address_1']),
				'cell'              => '',
				'phone'             => preg_replace("/[[:punct:]]/", '', $this->order_info['telephone']),
				'zip'               => preg_replace("/[[:punct:]]/", '', $this->order_info['shipping_postcode']),
				'city'              => preg_replace("/[[:punct:]]/", '', $this->order_info['shipping_city']),
				'country'           => preg_replace("/[[:punct:]]/", '', $this->order_info['shipping_iso_code_2']),
				'state'             => '',
				'email'             => $this->order_info['email'],
				'shippingCounty'    => '',
			),
			'urlDetails'        => array(
				'successUrl'        => $success_url,
				'failureUrl'        => $error_url,
				'pendingUrl'        => $pending_url,
				'backUrl'			=> $back_url,
				'notificationUrl'   => $notify_url,
			),
			'timeStamp'			=> $TimeStamp,
			'webMasterID'       => 'OpenCart ' . VERSION,
			'sessionToken'      => isset($this->request->post['lst']) ? $this->request->post['lst'] : '',
			'deviceDetails'     => NUVEI_CLASS::get_device_details(),
		);

		$params['billingAddress'] = $params['userDetails'];
		
		$params['items'][0] = array(
			'name'		=> $this->request->get['order_id'],
			'price'		=> $total_amount,
			'quantity'	=> 1,
		);
        
        if(!empty($this->request->post['nuvei_save_upo']) && 1 == $this->request->post['nuvei_save_upo']) {
            $params['userTokenId'] = $this->order_info['email'];
        }

		$params['checksum'] = hash(
			$this->config->get(NUVEI_SETTINGS_PREFIX . 'hash'),
			$params['merchantId'] 
                . $params['merchantSiteId'] 
                . $params['clientRequestId']
				. $params['amount'] 
                . $params['currency'] 
                . $TimeStamp
				. $this->config->get(NUVEI_SETTINGS_PREFIX . 'secret')
		);

        //$params['paymentMethod'] = $this->request->post['payment_method_sc'];
        $sc_payment_method = $this->request->post['payment_method_sc'];
		
		// UPO
		if (is_numeric($sc_payment_method)) {
			$endpoint_method                                = 'payment';
			$params['paymentOption']['userPaymentOptionId'] = $sc_payment_method;
			$params['userTokenId']							= $this->order_info['email'];
		}
        // APM
        else {
			$endpoint_method         = 'paymentAPM';
			$params['paymentMethod'] = $sc_payment_method;
			
			if (!empty($this->request->post[$sc_payment_method])) {
				$params['userAccountDetails'] = $this->request->post[$sc_payment_method];
			}
			
			if (
                isset($this->request->get['nuvei_save_upo']) == 1
                && $this->request->get['nuvei_save_upo'] == 1
            ) {
				$params['userTokenId'] = $this->order_info['email'];
			}
		}
        
//		if(
//			isset($this->request->post['payment_method_sc'], $this->request->post[$this->request->post['payment_method_sc']])
//			&& is_array($this->request->post[$this->request->post['payment_method_sc']])
//		) {
//			$params['userAccountDetails'] = $this->request->post[$this->request->post['payment_method_sc']];
//		}
            
//		$resp = NUVEI_CLASS::call_rest_api('paymentAPM', $params);
		$resp = NUVEI_CLASS::call_rest_api($endpoint_method, $params);

		if(!$resp) {
			$this->response->redirect($this->request->post['error_url']);
		}

		if($this->get_request_status($resp) == 'ERROR' || @$resp['transactionStatus'] == 'ERROR') {
			$this->change_order_status(
				(int) $this->request->get['order_id'], 
				'ERROR', 
				@$resp['transactionType']
			);

			$this->response->redirect($error_url);
		}

		if(@$resp['transactionStatus'] == 'DECLINED') {
			$this->change_order_status(
				(int) $this->request->get['order_id'], 
				'DECLINED', 
				@$resp['transactionType']
			);

            if(!empty($this->request->post['error_url'])) {
                $this->response->redirect($this->request->post['error_url']);
            }
            else {
                $this->response->redirect($error_url);
            }
		}

		if($this->get_request_status($resp) == 'SUCCESS') {
			// in case we have redirectURL
			if(!empty($resp['redirectURL'])) {
				$this->response->redirect($resp['redirectURL']);
			}
            elseif(!empty($resp['paymentOption']['redirectUrl'])) {
                $this->response->redirect($resp['paymentOption']['redirectUrl']);
            }
		}

		$this->response->redirect($success_url);
         * 
         */
    }
	
    /**
     * Function checkAdvancedCheckSum
     * Check if the DMN is not fake.
     * 
     * @return boolean
     */
    private function checkAdvancedCheckSum()
    {
        $str = hash(
            $this->config->get(NUVEI_SETTINGS_PREFIX . 'hash'),
            $this->config->get(NUVEI_SETTINGS_PREFIX . 'secret')
                . NUVEI_CLASS::get_param('totalAmount')
                . NUVEI_CLASS::get_param('currency')
                . NUVEI_CLASS::get_param('responseTimeStamp')
                . NUVEI_CLASS::get_param('PPP_TransactionID')
                . $this->get_request_status()
                . NUVEI_CLASS::get_param('productId')
        );

        if (NUVEI_CLASS::get_param('advanceResponseChecksum') == $str) {
            return true;
        }
        
        return false;
	}
    
    /**
     * Function get_request_status
     * 
     * We need this stupid function because as response request variable
     * we get 'Status' or 'status'...
     * 
     * @return string
     */
    private function get_request_status($params = array())
    {
        if(empty($params)) {
            if(isset($_REQUEST['Status'])) {
                return filter_var($_REQUEST['Status'], FILTER_SANITIZE_STRING);
            }

            if(isset($_REQUEST['status'])) {
                return filter_var($_REQUEST['status'], FILTER_SANITIZE_STRING);
            }
        }
        else {
            if(isset($params['Status'])) {
                return filter_var($params['Status'], FILTER_SANITIZE_STRING);
            }

            if(isset($params['status'])) {
                return filter_var($params['status'], FILTER_SANITIZE_STRING);
            }
        }
        
        return '';
    }
    
    /**
     * Function get_locale
     * Extract locale code in format "en_GB"
     * 
     * @return string
     */
    private function get_locale()
    {
		$langs = $this->model_localisation_language->getLanguages();
        $langs = current($langs);
        
        if(isset($langs['locale']) && $langs['locale'] != '') {
            $locale_parts = explode(',', $langs['locale']);
            
            foreach($locale_parts as $part) {
                if(strlen($part) == 5 && strpos($part, '_') != false) {
                    return $part;
                }
            }
        }
        
        return '';
	}
    
    /**
     * Function change_order_status
     * Change the status of the order.
     * 
     * @param int $order_id - escaped
     * @param string $status
     * @param string $transactionType - not mandatory for the DMN
     */
    private function change_order_status($order_id, $status, $transactionType = '')
    {
        NUVEI_CLASS::create_log($this->plugin_settings, 'change_order_status()');
        
        $message		= '';
        $send_message	= true;
        $trans_id       = (int) NUVEI_CLASS::get_param('TransactionID');
        $rel_tr_id      = (int) NUVEI_CLASS::get_param('relatedTransactionId');
        $payment_method = NUVEI_CLASS::get_param('payment_method', FILTER_SANITIZE_STRING);
        $total_amount   = (float) NUVEI_CLASS::get_param('totalAmount');
        $status_id      = $this->order_info['order_status_id'];
        $order_total    = $this->get_price($this->order_info['total']);
        
        $comment_details = '<br/>' 
            . $this->language->get('Transaction ID: ') . $trans_id . '<br/>'
            . $this->language->get('Related Transaction ID: ') . $rel_tr_id . '<br/>'
            . $this->language->get('Status: ') . $status . '<br/>'
            . $this->language->get('Transaction Type: ') . $transactionType . '<br/>'
            . $this->language->get('Payment Method: ') . $payment_method . '<br/>';
        
        switch($status) {
            case 'CANCELED':
                $message = $this->language->get('Your request was Canceled.') . $comment_details;
                break;

            case 'APPROVED':
                if($transactionType == 'Void') {
                    $message    = $this->language->get('Your Order was Voided.') . $comment_details;
                    $status_id  = $this->config->get(NUVEI_SETTINGS_PREFIX . 'canceled_status_id');
                    break;
                }
                
                // Refund
                if($transactionType == 'Credit') {
//					$curr_refund_amount = $total_amount;
					
//                        // get all order Refunds
//                        $query = $this->db->query('SELECT * FROM nuvei_refunds WHERE orderId = ' . $order_id);
//
//                        $refs_sum = 0;
//                        if(@$query->rows) {
//							NUVEI_CLASS::create_log($this->plugin_settings, $query->rows, 'Refunds:');
//							
//                            foreach($query->rows as $row) {
//                                $row_amount = round(floatval($row['amount']), 2);
//                                
//                                if($row['approved'] == 1) {
//                                    $refs_sum += $row_amount;
//                                }
//                                // find the record for the current Refund
//                                // and check the Amount, the amount in the base is correct one
//                                elseif(
//                                    $row['clientUniqueId'] == $cl_unique_id
//                                    && round($curr_refund_amount, 2) != $row_amount
//                                ) {
//                                    $curr_refund_amount = $row_amount;
//                                }
//                            }
//                        }
                        
                        // to the sum of approved refund add current Refund amount
						/** TODO because of bug, only cc_card provide correct Refund Amount */
//						if('cc_card' == $payment_method) {
//							$refs_sum += $curr_refund_amount;
//						}

                        $send_message   = false;
                        $status_id      = $this->config->get(NUVEI_SETTINGS_PREFIX . 'refunded_status_id');

//                        if(round($refs_sum, 2) == round($this->order_info['total'], 2)) {
//                            $status_id = 11; // Refunded
//                            $send_message = true;
//
//                            $this->db->query("UPDATE " . DB_PREFIX
//                                . "order SET order_status_id = 11 WHERE order_id = {$order_id};");
//                        }
                        
                        $message = $this->language->get('Your Order was Refunded.') . $comment_details;
						
						//if($cl_unique_id) {
							$formated_refund = $this->currency->format(
//								$curr_refund_amount,
								$total_amount,
								$this->order_info['currency_code'],
								$this->order_info['currency_value']
							);
							
							$message .= $this->language->get('Refund Amount: ') . $formated_refund;
						//}
						
                        # update Refund data into the DB
//						$q = "UPDATE nuvei_refunds SET "
//							. "transactionId = '{$this->db->escape(@$_REQUEST['TransactionID'])}', "
//							. "authCode = '{$this->db->escape(@$_REQUEST['AuthCode'])}', "
//							. "approved = 1 "
//						. "WHERE orderId = {$order_id} "
//							. "AND clientUniqueId = '{$this->db->escape($cl_unique_id)}'";
//                        
//						NUVEI_CLASS::create_log($this->plugin_settings, $q, 'Refunds update query:');
//							
//                        $this->db->query($q);
                    break;
                }
                
                $status_id = $this->config->get(NUVEI_SETTINGS_PREFIX . 'order_status_id'); // "completed"
                
                if($transactionType == 'Auth') {
                    $message    = $this->language->get('The amount has been authorized and wait for Settle.');
                    $status_id  = $this->config->get(NUVEI_SETTINGS_PREFIX . 'pending_status_id');
                }
                elseif($transactionType == 'Settle') {
                    $message = $this->language->get('The amount has been captured by Nuvei.');
                }
                // set the Order status to Complete
                elseif($transactionType == 'Sale') {
                    $message = $this->language->get('The amount has been authorized and captured by Nuvei.');
                }
                
                // check for different Order Amount
                if(in_array($transactionType, array('Sale', 'Settle')) && $order_total != $total_amount) {
                    $msg = $this->language->get('Attention - the Order total is ') 
                        . $this->order_info['currency_code'] . ' ' . $order_total
                        . $this->language->get(', but the Captured amount is ')
                        . NUVEI_CLASS::get_param('currency', FILTER_SANITIZE_STRING)
                        . ' ' . $total_amount . '.';

                    $this->model_checkout_order->addOrderHistory($order_id, $status_id, $msg, false);
                }
                
				$message .= $comment_details;
                break;

            case 'ERROR':
            case 'DECLINED':
            case 'FAIL':
                $message = $this->language->get('Your request faild.') . $comment_details
                    . $this->language->get('Reason: ');
                
                if( ($reason = NUVEI_CLASS::get_param('reason', FILTER_SANITIZE_STRING)) ) {
                    $message .= $reason;
                }
                elseif( ($reason = NUVEI_CLASS::get_param('Reason', FILTER_SANITIZE_STRING)) ) {
                    $message .= $reason;
                }
                elseif( ($reason = NUVEI_CLASS::get_param('paymentMethodErrorReason', FILTER_SANITIZE_STRING)) ) {
                    $message .= $reason;
                }
                elseif( ($reason = NUVEI_CLASS::get_param('gwErrorReason', FILTER_SANITIZE_STRING)) ) {
                    $message .= $reason;
                }
                
                $message .= '<br/>';
                
                $message .= 
                    $this->language->get("Error code: ") 
                    . (int) NUVEI_CLASS::get_param('ErrCode') . '<br/>'
                    . $this->language->get("Message: ") 
                    . NUVEI_CLASS::get_param('message', FILTER_SANITIZE_STRING) . '<br/>';
                
                if(in_array($transactionType, array('Sale', 'Auth'))) {
                    $status_id = $this->config->get(NUVEI_SETTINGS_PREFIX . 'failed_status_id');
                    break;
                }

                // Void, do not change status
                if($transactionType == 'Void') {
                    $status_id = $this->order_info['order_status_id'];
                    break;
                }
                
                // Refund
                if($transactionType == 'Credit') {
					//if($cl_unique_id) {
						$formated_refund = $this->currency->format(
                            $total_amount,
							$this->order_info['currency_code'],
							$this->order_info['currency_value']
						);
						
						$message .= $this->language->get('Refund Amount: ') . $formated_refund;
					//}
                    
                    $status_id = $this->order_info['order_status_id'];
                    $send_message = false;
                    break;
                }
                
                $status_id = $this->config->get(NUVEI_SETTINGS_PREFIX . 'failed_status_id');
                break;

			/** TODO Remove it. We stop process in the beginning when status is Pending */
//            case 'PENDING':
//				NUVEI_CLASS::create_log($this->plugin_settings, $this->order_info['order_status_id'], 'Order status is:', $this->config->get(NUVEI_SETTINGS_PREFIX . 'test_mode'));
//				
//                if ($this->order_info['order_status_id'] == '5' || $this->order_info['order_status_id'] == '15') {
//                    $status_id = $this->order_info['order_status_id'];
//                    break;
//                }
//				
//				$status_id = $this->config->get(NUVEI_SETTINGS_PREFIX . 'pending_status_id');
//                
//                $message = 'Payment is still pending, PPP_TransactionID '
//                    . @$request['PPP_TransactionID'] . ", Status = " . $status;
//
//                if($transactionType) {
//                    $message .= ", TransactionType = " . $transactionType;
//                }
//
//                $message .= ', GW_TransactionID = ' . @$request['TransactionID'];
//                
//                $this->model_checkout_order->addOrderHistory(
//                    $order_id,
//                    $status_id,
//                    'Nuvei payment status is pending<br/>Unique Id: '
//                        .@$request['PPP_TransactionID'],
//                    true
//                );
//                
//                break;
                
            default:
                NUVEI_CLASS::create_log($this->plugin_settings, $status, 'Unexisting status:');
        }
        
        NUVEI_CLASS::create_log(
            $this->plugin_settings,
            array(
                'order_id'  => $order_id,
                'status_id' => $status_id,
            ),
            'addOrderHistory()'
        );
        
        $this->model_checkout_order->addOrderHistory($order_id, $status_id, $message, $send_message);
    }
    
	private function open_order()
    {
        NUVEI_CLASS::create_log($this->plugin_settings, 'open_order()');
        
        # try to update Order
		$resp = $this->update_order();
        
        if (!empty($resp['status']) && 'SUCCESS' == $resp['status']) {
			return $resp;
		}
        # /try to update Order
        
        $amount = $this->get_price($this->order_info['total']);
        
		$oo_params = array(
			'clientUniqueId'	=> $this->session->data['order_id'] . '_' . uniqid(),
			'amount'            => $amount,
			'currency'          => $this->order_info['currency_code'],
			
			'urlDetails'        => array(
//				'successUrl'        => $this->url->link(NUVEI_CONTROLLER_PATH . '/success'),
//				'failureUrl'        => $this->url->link(NUVEI_CONTROLLER_PATH . '/fail'),
//				'pendingUrl'        => $this->url->link(NUVEI_CONTROLLER_PATH . '/success'),
				'backUrl'			=> $this->url->link('checkout/checkout', '', true),
				'notificationUrl'   => $this->url->link(NUVEI_CONTROLLER_PATH . '/callback'),
			),
			
			'userDetails'       => $this->order_addresses['billingAddress'],
			'billingAddress'	=> $this->order_addresses['billingAddress'],
            'shippingAddress'   => $this->order_addresses['shippingAddress'],
			
			'paymentOption'		=> array('card' => array('threeD' => array('isDynamic3D' => 1))),
			'transactionType'	=> $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'payment_action'],
		);
		
        // change urlDetails
        if(1 == @$this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'auto_close_apm_popup']
            || 0 == $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'test_mode']
        ) {
            $oo_params['urlDetails']['successUrl']  = $oo_params['urlDetails']['failureUrl']
                                                    = $oo_params['urlDetails']['pendingUrl']
                                                    = NUVEI_SDK_AUTOCLOSE_URL;
        }
        
        # use or not UPOs
        // rebiling parameters
        $rebilling_params = $this->preprareRebillingParams();

        // in case there is a Product with a Payment Plan
        if(isset($rebilling_params['isRebilling']) && 0 == $rebilling_params['isRebilling']) {
            $oo_params['userTokenId'] = $oo_params['billingAddress']['email'];
        }
        elseif(1 == $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'use_upos'] 
            && 1 == $this->is_user_logged
        ) {
            $oo_params['userTokenId'] = $oo_params['billingAddress']['email'];
        }
        # /use or not UPOs
        
		$resp = NUVEI_CLASS::call_rest_api(
            'openOrder',
            $this->plugin_settings,
            array('merchantId', 'merchantSiteId', 'clientRequestId', 'amount', 'currency', 'timeStamp'),
            $oo_params
        );
		
		if (empty($resp['status']) || empty($resp['sessionToken']) || 'SUCCESS' != $resp['status']) {
			if(!empty($resp['message'])) {
				return $resp;
			}
			
			return array();
		}
        
        // set them to session for the check before submit the data to the webSDK
        $this->session->data['nuvei_last_oo_details']['amount']             = $oo_params['amount'];
        $this->session->data['nuvei_last_oo_details']['sessionToken']       = $resp['sessionToken'];
        $this->session->data['nuvei_last_oo_details']['clientRequestId']    = $resp['clientRequestId'];
        $this->session->data['nuvei_last_oo_details']['orderId']            = $resp['orderId'];
        $this->session->data['nuvei_last_oo_details']['billingAddress']['country']
            = $oo_params['billingAddress']['country'];
        
        $oo_params['sessionToken'] = $resp['sessionToken'];
		
		return $oo_params;
	}
    
    private function update_order()
    {
        NUVEI_CLASS::create_log($this->plugin_settings, 'update_order()');
        
        if (empty($this->session->data['nuvei_last_oo_details'])
			|| empty($this->session->data['nuvei_last_oo_details']['sessionToken'])
			|| empty($this->session->data['nuvei_last_oo_details']['orderId'])
			|| empty($this->session->data['nuvei_last_oo_details']['clientRequestId'])
		) {
			NUVEI_CLASS::create_log(
                $this->plugin_settings,
                'update_order() - exit updateOrder logic, continue with new openOrder.'
            );
			
			return array('status' => 'ERROR');
		}
        
        $amount = $this->get_price($this->order_info['total']);
        
        // updateOrder params
		$params = array(
			'sessionToken'		=> $this->session->data['nuvei_last_oo_details']['sessionToken'],
			'orderId'			=> $this->session->data['nuvei_last_oo_details']['orderId'],
            'clientRequestId'	=> $this->session->data['nuvei_last_oo_details']['clientRequestId'],
            'currency'          => $this->order_info['currency_code'],
            'amount'            => $amount,
            
            'userDetails'	=> $this->order_addresses['billingAddress'],
            'billingAddress'	=> $this->order_addresses['billingAddress'],
            'shippingAddress'   => $this->order_addresses['shippingAddress'],
            
            'items'				=> array(
				array(
					'name'		=> 'oc_order',
					'price'		=> $amount,
					'quantity'	=> 1
				)
			),
		);

        // rebiling parameters
        $rebilling_params = $this->preprareRebillingParams();
        // when will use UPOs
        if(0 == $rebilling_params['isRebilling']) {
            $params['userTokenId'] = $this->order_addresses['billingAddress']['email'];
        }
        elseif($this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'use_upos'] == 1) {
            $params['userTokenId'] = $this->order_addresses['billingAddress']['email'];
        }
        else {
            $params['userTokenId'] = null;
        }
        
        $params = array_merge_recursive($params, $rebilling_params);
        
		$resp = NUVEI_CLASS::call_rest_api(
            'updateOrder', 
            $this->plugin_settings, 
            array('merchantId', 'merchantSiteId', 'clientRequestId', 'amount', 'currency', 'timeStamp'), 
            $params
        );
        
        # Success
		if (!empty($resp['status']) && 'SUCCESS' == $resp['status']) {
            $this->session->data['nuvei_last_oo_details']['amount'] = $params['amount'];
            $this->session->data['nuvei_last_oo_details']['billingAddress']['country'] 
                = $params['billingAddress']['country'];
            
            // set the order status to Pending if enabled into plugin settings
            if(1 == (int) @$this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'change_order_status']) {
                $pending_status = (int) $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'pending_status_id'];

                $this->db->query(
                    "UPDATE " . DB_PREFIX ."order "
                    . "SET order_status_id = {$pending_status} "
                    . "WHERE order_id = " . (int) $this->session->data['order_id']
                );
            }
            
			return array_merge($resp, $params);
		}
		
		NUVEI_CLASS::create_log($this->plugin_settings, 'Order update was not successful.');

		return array('status' => 'ERROR');
    }
    
    private function get_order_addresses()
    {
        return array(
            'billingAddress'	=> array(
				"firstName"	=> $this->order_info['payment_firstname'],
				"lastName"	=> $this->order_info['payment_lastname'],
				"address"   => $this->order_info['payment_address_1'],
				"phone"     => $this->order_info['telephone'],
				"zip"       => $this->order_info['payment_postcode'],
				"city"      => $this->order_info['payment_city'],
				'country'	=> $this->order_info['payment_iso_code_2'],
				'email'		=> $this->order_info['email'],
			),
            
            'shippingAddress'    => [
				"firstName"	=> $this->order_info['shipping_firstname'],
				"lastName"	=> $this->order_info['shipping_lastname'],
				"address"   => $this->order_info['shipping_address_1'],
				"phone"     => $this->order_info['telephone'],
				"zip"       => $this->order_info['shipping_postcode'],
				"city"      => $this->order_info['shipping_city'],
				'country'	=> $this->order_info['shipping_iso_code_2'],
				'email'		=> $this->order_info['email'],
			],
        );
    }

	private function ajax_call()
    {
		NUVEI_CLASS::create_log($this->plugin_settings, 'ajax_call()');
        
        $oo_data = $this->open_order();
		
		if(empty($oo_data)) {
			echo json_encode(array('status' => 'error'));
			exit;
		}
		
		echo json_encode(array(
			'status'		=> 'success',
			'sessionToken'	=> $oo_data['sessionToken']
		));
		exit;
	}
	
    private function remove_upo()
    {
        if(empty($this->customer->getEmail())) {
            echo json_encode(array(
                'status'    => 0,
                'msg'       => $this->language->get('nuvei_error_logged_user')
            ));
            exit;
        }
        
        $timeStamp = gmdate('YmdHis', time());
			
		$params = array(
			'merchantId'            => $this->config->get(NUVEI_SETTINGS_PREFIX . 'merchantId'),
			'merchantSiteId'        => $this->config->get(NUVEI_SETTINGS_PREFIX . 'merchantSiteId'),
			'userTokenId'           => $this->customer->getEmail(),
			'clientRequestId'       => $timeStamp . '_' . uniqid(),
			'userPaymentOptionId'   => (int) $this->request->post['upoId'],
			'timeStamp'             => $timeStamp,
		);
		
		$params['checksum'] = hash(
			$this->config->get(NUVEI_SETTINGS_PREFIX . 'hash'),
			implode('', $params) 
                . $this->config->get(NUVEI_SETTINGS_PREFIX . 'secret')
		);
		
		$resp = NUVEI_CLASS::call_rest_api('deleteUPO', $params);
        
        if (empty($resp['status']) || 'SUCCESS' != $resp['status']) {
			$msg = !empty($resp['reason']) ? $resp['reason'] : '';
			
			echo json_encode(array(
				'status'    => 0,
				'msg'       => $msg
            ));
			exit;
		}
		
		echo json_encode(array('status' => 1));
		exit;
    }

    /**
     * Function return_message
     * 
     * @param string    $msg
     * @param mixed     $data
     */
    private function return_message($msg, $data = '') {
        if(!is_string($msg)) {
            $msg = json_encode($msg);
        }
        
        if(!empty($data)) {
            NUVEI_CLASS::create_log($this->plugin_settings, $data, $msg);
        }
        else {
            NUVEI_CLASS::create_log($this->plugin_settings, $msg);
        }
        
        echo $msg;
        exit;
    }
    
    private function preprareRebillingParams()
    {
        $params = [];
        
        // default rebiling parameters
        $params['isRebilling']                                        = 1;
        $params['paymentOption']['card']['threeD']['rebillFrequency'] = 0;
        $params['paymentOption']['card']['threeD']['rebillExpiry']    = date('Ymd', time());
        
        # TODO check for a product with a Payment Plan
//        $prod_with_plan = $this->getProdsWithPlansFromCart();
//        
//        // in case there is a Product with a Payment Plan
//        if(!empty($prod_with_plan) && is_array($prod_with_plan)) {
//            $params['isRebilling']                                        = 0;
//			$params['paymentOption']['card']['threeD']['rebillFrequency'] = 1;
//			$params['paymentOption']['card']['threeD']['rebillExpiry']    = gmdate('Ymd', strtotime('+5 years'));
//            $params['merchantDetails']['customField5']                    = $prod_with_plan['plan_details'];
//        }
        
        return $params;
    }
    
    private function load_settings()
    {
        if(empty($this->plugin_settings) || null === $this->plugin_settings) {
            $this->load->model('setting/setting');
            $this->plugin_settings  = $this->model_setting_setting->getSetting(trim(NUVEI_SETTINGS_PREFIX, '_'));
        }
    }
    
    /**
     * Get some price by the currency convert rate.
     */
    private function get_price($price)
    {
        $new_price = round((float) $price * $this->order_info['currency_value'], 2);
        return number_format($new_price, 2, '.', '');
    }
}
