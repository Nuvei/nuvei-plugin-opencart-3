<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR . 'nuvei' 
    . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

class ControllerExtensionPaymentNuvei extends Controller
{ 
    private $required_settings  = [
        'test_mode',
        'merchantId',
        'merchantSiteId',
        'secret',
        'hash',
        'payment_action',
        'create_logs',
    ];
    
    private $data               = []; // the data for the admin template
    private $plugin_settings    = [];
	private $prefix             = '';
	private $notify_url         = '';
    private $ajax_action        = '';
	
    public function install()
    {
        // Load the event model to add a new event
        $this->load->model('setting/event');
        
        // for the Catalog custom JSs
        $this->model_setting_event->addEvent(
            'nuvei_catalog_add_scripts',  // Unique event code
            'catalog/controller/common/footer/before',
            'extension/payment/nuvei/addJsScriptsToCatalog' // The callback method
        );
        
        // event whent someone try to add product to the Cart
        $this->model_setting_event->addEvent(
            'nuvei_catalog_add_to_cart',  // Unique event code
            'catalog/controller/checkout/cart/add/before',
            'extension/payment/nuvei/addProductToCart' // The callback method
        );
        
        // try to filter payment providers
        $this->model_setting_event->addEvent(
            'nuvei_catalog_filter_payment_methods',  // Unique event code
            'catalog/model/setting/extension/getExtensions/after',
            'extension/payment/nuvei/filterPaymentProviders' // The callback method
        );
        
        // add custom JS in admin header
        $this->model_setting_event->addEvent(
            'nuvei_admin_add_script',  // Unique event code
            'admin/controller/common/header/before',
            'extension/payment/nuvei/addJsScriptsToAdmin' // The callback method
        );
        
        // modify Orders list if any of the Order has Refund
        $this->model_setting_event->addEvent(
            'nuvei_admin_modify_orders_totals',  // Unique event code
            'admin/model/sale/order/getOrders/after',
            'extension/payment/nuvei/modifyOrdersTotals' // The callback method
        );
        
        // add custom script in Order info page
        $this->model_setting_event->addEvent(
            'nuvei_admin_order_info_add_script',  // Unique event code
            'admin/controller/sale/order/info/before',
            'extension/payment/nuvei/addJsScriptsToAdminOrderInfo' // The callback method
        );
        
        // remove the plugin upgrade cookie if exists
        if (!empty($_COOKIE['nuvei_plugin_msg'])) {
            unset($_COOKIE['nuvei_plugin_msg']);
            setcookie('nuvei_plugin_msg', null, -1, '/'); 
            return true;
        }
    }
    
    public function uninstall()
    {
        // Load the event model to remove the event
        $this->load->model('setting/event');

        // Remove events based on its unique code
        $this->model_setting_event->deleteEventByCode('nuvei_catalog_add_scripts');
        $this->model_setting_event->deleteEventByCode('nuvei_catalog_add_to_cart');
        $this->model_setting_event->deleteEventByCode('nuvei_catalog_filter_payment_methods');
        $this->model_setting_event->deleteEventByCode('nuvei_admin_add_script');
        $this->model_setting_event->deleteEventByCode('nuvei_admin_modify_orders_totals');
        $this->model_setting_event->deleteEventByCode('nuvei_admin_order_info_add_script');
        
        // remove the plugin upgrade cookie if exists
        if (!empty($_COOKIE['nuvei_plugin_msg'])) {
            unset($_COOKIE['nuvei_plugin_msg']);
            setcookie('nuvei_plugin_msg', null, -1, '/'); 
            return true;
        }
    }
    
	public function index()
    {
        $this->load->language(NUVEI_CONTROLLER_PATH);
		$this->load->model('setting/setting');
        $this->load->model('sale/order');
        
        // get settings
        $this->plugin_settings = $this->model_setting_setting->getSetting(trim(NUVEI_SETTINGS_PREFIX, '_'));
        
        if(isset($this->request->server['HTTP_X_REQUESTED_WITH'], $this->request->post['action'])
            && 'XMLHttpRequest' == $this->request->server['HTTP_X_REQUESTED_WITH']
        ) {
            $this->ajax_call();
            exit;
        }
        
        // validate on save
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting(trim(NUVEI_SETTINGS_PREFIX, '_'), $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}
        
        if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
        // /validate on save
        
        $this->data['nuveiPluginVersion']       = NUVEI_PLUGIN_V;
        $this->data['NUVEI_SETTINGS_PREFIX']    = NUVEI_SETTINGS_PREFIX;
        
        // add the settings to the $this->data
        $this->data = array_merge($this->data, $this->plugin_settings );
        
        // if we have added general settings allready get merchan payment methods
        $this->get_payment_methods();
		
        // added path menu
		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link(
                'common/dashboard',
                NUVEI_TOKEN_NAME . '=' . $this->session->data[NUVEI_TOKEN_NAME],
                true
            ),
            'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link(
                NUVEI_ADMIN_EXT_URL,
                NUVEI_TOKEN_NAME . '=' . $this->session->data[NUVEI_TOKEN_NAME] . '&type=payment',
                true
            ),
            'separator' => ' :: '
		);
        
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
			'href' => $this->url->link(
                $this->request->get['route'],
                NUVEI_TOKEN_NAME . '=' . $this->session->data[NUVEI_TOKEN_NAME],
                true
            ),
            'separator' => ' :: '
   		);
        // /added path menu

		$this->data['action'] = $this->url->link(
            $this->request->get['route'],
            NUVEI_TOKEN_NAME . '=' . $this->session->data[NUVEI_TOKEN_NAME],
            true
        );
		
        // cancel (go back) link
        $this->data['cancel'] = $this->url->link(
            NUVEI_ADMIN_EXT_URL,
            NUVEI_TOKEN_NAME . '=' . $this->session->data[NUVEI_TOKEN_NAME] . '&type=payment',
            true
        );
        
        // DMN URL
        $this->data['nuvei_dmn_url'] = str_replace('admin/', '', $this->url->link(NUVEI_CONTROLLER_PATH . '/callback'));

        // set statuses manually
        $statuses = array(
            5   => 'order_status_id',
            1   => 'pending_status_id',
            7   => 'canceled_status_id',
            10  => 'failed_status_id',
            11  => 'refunded_status_id',
//            13  => 'chargeback_status_id',
        );
        
        foreach($statuses as $id => $name) {
            if (isset($this->request->post[NUVEI_SETTINGS_PREFIX . $name])) {
                $this->data[NUVEI_SETTINGS_PREFIX . $name] = $this->request->post[NUVEI_SETTINGS_PREFIX . $name];
            }
            elseif (isset($this->plugin_settings [NUVEI_SETTINGS_PREFIX . $name])) {
                $this->data[NUVEI_SETTINGS_PREFIX . $name] = $this->config->get(NUVEI_SETTINGS_PREFIX . $name); 
            }
            else {
                $this->data[NUVEI_SETTINGS_PREFIX . $name] = $id;
            }
        }
        // /set statuses manually
        
        // get all statuses
		$this->load->model('localisation/order_status');
		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        
        // get all geo-zones
		$this->load->model('localisation/geo_zone');
		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        elseif (isset($this->session->data['error_warning'])) {
            $this->data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
        }
        
        // set template parts
        $this->data['header']       = $this->load->controller('common/header');
        $this->data['column_left']	= $this->load->controller('common/column_left');
        $this->data['footer']		= $this->load->controller('common/footer');
        
        // load common php template and then pass it to the real template
        ob_start();
        $data = $this->data; // $data goes to the template
        require DIR_TEMPLATE . NUVEI_CONTROLLER_PATH . '.php';
        $sc_form['sc_form'] = ob_get_clean(); // the template of OC wants array
        
        $this->response->setOutput($this->load->view(NUVEI_CONTROLLER_PATH, $sc_form));
        
        // if we want twig file only
//        $this->response->setOutput($this->load->view(NUVEI_CONTROLLER_PATH, $this->data));
	}

    /**
     * Event callback method.
     * 
     * @param string $route
     * @param array $data
     */
    public function addJsScriptsToAdmin(&$route, &$data)
    {
        if ($this->user->isLogged()) {
            if (!empty($this->session->data['nuveiPluginGitVersion'])
                && $this->session->data['nuveiPluginGitVersion'] > (int) str_replace('.', '', NUVEI_PLUGIN_V)
            ) {
                $this->document->addScript('view/javascript/nuvei_version_checker.js');
            }
            elseif ( ($git_v = NUVEI_CLASS::get_plugin_git_version()) > (int) str_replace('.', '', NUVEI_PLUGIN_V) ) {
                $this->session->data['nuveiPluginGitVersion'] = $git_v;
                $this->document->addScript('view/javascript/nuvei_version_checker.js');
            }
        }
    }
    
    /**
     * Event callback method.
     * 
     * @param string $route
     * @param array $data
     */
    public function addJsScriptsToAdminOrderInfo(&$route, &$data)
    {
        $this->document->addScript('view/javascript/nuvei_orders.js');
    }
    
    /**
     * Event callback method.
     * 
     * @param string $route
     * @param array $args
     * @param array $results The list with Orders
     * 
     * @return void
     */
    public function modifyOrdersTotals(&$route, &$args, &$results)
    {
        if (empty($results) || !is_array($results)) {
            return;
        }
        
        $ordersIds = array();
        
        try {
            // extract order ids
            foreach ($results as $order) {
                if (isset($order['order_id'])) {
                    $ordersIds[] = (int) $order['order_id'];
                }
            }
            
            if (empty($ordersIds)) {
                return;
            }
            
            // get Nuvei field for the extracted orders
            $queryResult = $this->db->query('SELECT order_id, payment_custom_field FROM ' . DB_PREFIX  
                . 'order WHERE order_id IN (' . join(', ', $ordersIds) . ')');
            
            if (0 == $queryResult->num_rows) {
                return;
            }
            
            $nuveiData = array();
            
            foreach ($queryResult->rows as $row) {
                $nuveiData[$row['order_id']] = $row;
            }
                
            foreach($results as $key => $order) {
                $refunds_sum = 0; // it is converted

                if (!isset($nuveiData[$order['order_id']])
                    || empty($nuveiData[$order['order_id']]['payment_custom_field'])
                ) {
                    continue;
                }
                
                $nuvei_data = json_decode($nuveiData[$order['order_id']]['payment_custom_field'], true);

                foreach($nuvei_data as $nuv_rec) {
                    if(!empty($nuv_rec['status'])
                        && 'approved' == $nuv_rec['status']
                        && !empty($nuv_rec['transactionType'])
                        && in_array($nuv_rec['transactionType'], array('Credit', 'Refund'))
                    ) {
                        $refunds_sum += $nuv_rec['totalAmount'];
                    }

                    if (in_array($nuv_rec['transactionType'], array('Auth', 'Sale'))
                        && !empty($nuv_rec['totalCurrAlert'])
                    ) {
                        $results[$key]['order_status'] .= '&nbsp;<a title="Check transaction total/currency!" class="btn btn-warning"><i class="fa fa-warning"></i></a>';
                    }
                }

                if($refunds_sum > 0) {
                    $formated_total = '<del>' . $this->currency->format(
                        $order['total'],
                        $order['currency_code'],
                        $order['currency_value']
                    ) . '</del>';

                    $converted_total = $order['total'] * $order['currency_value'];

                    $formated_total .= '&nbsp;' . $this->currency->format(
                        ($converted_total - $refunds_sum), 
                        $order['currency_code'],
                        1 // 1 in case the amout is converted, else - $this->data['currency_value']
                    );

                    $results[$key]['total'] = $formated_total;
                }
            }
        }
        catch (Exception $e) {
            NUVEI_CLASS::create_log($this->plugin_settings, $e->getMessage(), 'Nuvei event exception.', 'WARN');
            return;
        }
    }
    
    public function modifyOrderHiostoryRecords()
    {
        
    }
    
    /**
     * Process Ajax calls here.
     */
    private function ajax_call()
    {
        $this->ajax_action = $this->request->post['action'];
        
        switch ($this->ajax_action) {
//            case 'checkForUpdate':
//                $this->check_for_update();
//                exit;
                
            case 'getNuveiVars':
                $this->get_nuvei_vars();
                exit;
                
            case 'refund':
                $this->order_refund();
                exit;
                
//            case 'refundManual':
//                $this->order_refund(true);
//                exit;
                
//            case 'deleteManualRefund':
//                $this->delete_refund();
//                exit;
                
            case 'void':
            case 'settle':
                $this->order_void_settle();
                exit;
                
            case 'cancelSubscr':
                $this->subscription_cancel();
                exit;
                
            default:
                echo json_encode(array('status' => 0, 'msg' => 'Unknown order action: ' . $this->ajax_action));
                exit;
        }
    }
    
    private function order_refund($is_manual = false)
    {
        if(!isset($this->request->post['orderId'])) {
            exit(json_encode(array(
                'status'    => 0,
                'msg'       => 'orderId parameter is not set.')
            ));
        }
        
        $order_id           = (int) $this->request->post['orderId'];
        $this->notify_url   = $this->url->link(
            NUVEI_CONTROLLER_PATH
            . '/callback'
            . '&action=' . $this->ajax_action . '&order_id=' . $order_id
        );

        $this->notify_url   = str_replace('admin/', '', $this->notify_url);
		$request_amount     = round((float) $this->request->post['amount'], 2);
		
		NUVEI_CLASS::create_log(
            $this->plugin_settings,
			array(
				'order_id'	=> $order_id,
				'is_manual'	=> $is_manual,
			),
			'order_refund()'
		);
		
        if($request_amount <= 0) {
            echo json_encode(array(
                'status'    => 0,
                'msg'       => 'The Refund Amount must be greater than 0!')
            );
            exit;
        }
        
        $this->data             = $this->model_sale_order->getOrder($order_id);
        $remaining_ref_amound   = $this->get_price($this->data['total']);
        $last_sale_tr           = [];
        
        NUVEI_CLASS::create_log(
            $this->plugin_settings,
            $this->data['payment_custom_field'],
            'refund payment_custom_field'
        );
        
        // get the refunds
        foreach(array_reverse($this->data['payment_custom_field']) as $tr_data) {
            if(in_array($tr_data['transactionType'], array('Refund', 'Credit'))
                && 'approved' == $tr_data['status']
            ) {
                $remaining_ref_amound -= $tr_data['totalAmount'];
            }
            
            if(empty($last_sale_tr)
                && in_array($tr_data['transactionType'], array('Sale', 'Settle'))
                && 'approved' == $tr_data['status']
            ) {
                $last_sale_tr = $tr_data;
            }
        }
        
        if(round($remaining_ref_amound, 2) < $request_amount) {
            echo json_encode(array(
                'status'    => 0,
                'msg'       => 'Refunds sum exceeds Order Amount')
            );
            exit;
        }
        
        if($is_manual) {
            $order_status = $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'refunded_status_id']; // refunded
            
            $this->data['payment_custom_field'][] = array(
                'status'                => 'approved',
                'clientUniqueId'        => $order_id . '_' . uniqid(),
                'transactionType'       => 'Refund',
                'transactionType'       => 'Refund',
                'transactionId'         => '',
                'relatedTransactionId'  => $last_sale_tr['transactionId'],
                'userPaymentOptionId'   => '',
                'authCode'              => '',
                'totalAmount'           => $request_amount,
                'currency'              => $last_sale_tr['currency'],
                'paymentMethod'         => $last_sale_tr['paymentMethod'],
                'responseTimeStamp'     => date('Y-m-d.H:i:s'),
            );
            
            $this->db->query(
                "UPDATE " . DB_PREFIX ."order "
                . "SET payment_custom_field = '". json_encode($this->data['payment_custom_field']) ."' "
                . "WHERE order_id = " . $order_id
            );
            
            $this->db->query(
                "UPDATE " . DB_PREFIX ."order "
                . "SET order_status_id = {$order_status} "
                . "WHERE order_id = {$order_id};"
            );
            
            exit(json_encode(array('status' => 1)));
        }
        // /manual refund
        
		$time               = date('YmdHis');
        $order_status_curr  = $this->data['order_status_id'];
        $order_status       = 1; // pending
        $error_resp         = array();
        
        $ref_parameters     = array(
			'clientUniqueId'        => $order_id . '_' . uniqid(),
			'amount'                => $this->request->post['amount'],
			'currency'              => $this->data['currency_code'],
			'relatedTransactionId'  => $last_sale_tr['transactionId'],
			'authCode'              => $last_sale_tr['authCode'],
			'url'                   => $this->notify_url,
			'customData'            => $request_amount, // optional - pass the Refund Amount here
			'urlDetails'            => array('notificationUrl' => $this->notify_url),
			'url'                   => $this->notify_url,
		);
        
        // set the status to Pending
        $this->db->query(
            "UPDATE " . DB_PREFIX ."order "
            . "SET order_status_id = {$order_status} "
            . "WHERE order_id = {$order_id};"
        );
            
        NUVEI_CLASS::create_log(
            $this->plugin_settings,
            $order_status,
            'after set the refund status in the admin'
        );
		
		$resp = NUVEI_CLASS::call_rest_api(
            'refundTransaction',
            $this->plugin_settings,
            ['merchantId', 'merchantSiteId', 'clientRequestId', 'clientUniqueId', 'amount', 'currency', 'relatedTransactionId', 'authCode', 'url', 'timeStamp'],
            $ref_parameters
        );
			
        if(!$resp) {
            $error_resp = array(
                'status'    => 0, 
                'msg'       => 'Empty response.'
            );
            
//            exit(json_encode(array(
//                'status'    => 0, 
//                'msg'       => 'Empty response.')
//            ));
        }
        
        // in case we have message but without status
        if(!isset($resp['status']) && isset($resp['msg'])) {
            $error_resp = array(
                'status'    => 0,
                'msg'       => $resp['msg']
            );
            
//            exit(json_encode(array(
//                'status'    => 0,
//                'msg'       => $resp['msg']
//            )));
        }
        
        if($resp === false) {
            $error_resp = array(
                'status'    => 0,
                'msg'       => $this->language->load('The request faild.')
            );
            
//            exit(json_encode(array(
//                'status'    => 0,
//                'msg'       => $this->language->load('The request faild.')
//            )));
        }
        
        if(!is_array($resp)) {
            $error_resp = array(
                'status'    => 0,
                'msg'       => $this->language->load('Invalid request response.')
            );
            
//            exit(json_encode(array(
//                'status'    => 0,
//                'msg'       => $this->language->load('Invalid request response.')
//            )));
        }
        
        // the status of the request is ERROR
        if(!empty($resp['status']) && $resp['status'] == 'ERROR') {
            $error_resp = array(
                'status'    => 0, 
                'msg'       => $resp['reason']
            );
            
//            exit(json_encode(array(
//                'status'    => 0, 
//                'msg'       => $resp['reason']
//            )));
        }
        
        if (!empty($error_resp)) {
            // revert the original Order status
            $this->db->query(
                "UPDATE " . DB_PREFIX ."order "
                . "SET order_status_id = {$order_status_curr} "
                . "WHERE order_id = {$order_id};"
            );
                
            exit(json_encode($error_resp));
        }
        
        // if this is the e last possible refund set order to Refunded
        if($remaining_ref_amound == $request_amount) {
            $order_status = 11; // refunded
            
            $this->db->query(
                "UPDATE " . DB_PREFIX ."order "
                . "SET order_status_id = {$order_status} "
                . "WHERE order_id = {$order_id};"
            );
        }
        
        exit(json_encode(array(
            'status' => 1
        )));
    }
    
    private function delete_refund()
    {
        if(!isset($this->request->post['refId'])) {
            exit(json_encode(array(
                'status'    => 0,
                'msg'       => 'refId parameter is not set.')
            ));
        }
        
        $ref_id             = $this->request->post['refId'];
        $order_id           = (int) $this->request->post['orderId'];
        $order_data         = $this->model_sale_order->getOrder($order_id);
        $nuvei_data         = $order_data['payment_custom_field'];
        $refunds_count      = 0;
        $ref_key_to_delete  = null;
        
        try {
            foreach($nuvei_data as $key => $tr_data) {
                if($tr_data['clientUniqueId'] == $ref_id) {
                    $ref_key_to_delete = $key;
                    $refunds_count++;
                }
                elseif (in_array($tr_data['transactionType'], ['Credit', 'Refund'])) {
                    $refunds_count++;
                }
            }
            
            if(null !== $ref_key_to_delete) {
                unset($nuvei_data[$ref_key_to_delete]);
                
                // update the nuvei data
                $this->db->query(
                    "UPDATE " . DB_PREFIX ."order "
                    . "SET payment_custom_field = '". json_encode($nuvei_data) ."' "
                    . "WHERE order_id = " . $order_id
                );
                
                $refunds_count--;
            }
            
            // check for other refunds, if no more refunds, change the order status
            if($refunds_count < 1) {
                $order_status = $this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'order_status_id']; // completed
                
                $this->db->query(
                    "UPDATE " . DB_PREFIX ."order "
                    . "SET order_status_id = {$order_status} "
                    . "WHERE order_id = {$order_id};"
                );
            }
        }
        catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
        
        exit(json_encode(array('status' => 1)));
    }


    /**
     * Function order_void_settle
     * 
     * We use one function for both because the only
     * difference is the endpoint, all parameters are same
     */
    private function order_void_settle()
    {
        if(!isset($this->request->post['orderId'])) {
            exit(json_encode(array(
                'status'    => 0,
                'msg'       => 'orderId parameter is not set.')
            ));
        }
        
        $order_id           = (int) $this->request->post['orderId'];
        $this->notify_url   = $this->url->link(
            NUVEI_CONTROLLER_PATH
            . '/callback&action=' . $this->ajax_action . '&order_id=' . $order_id
        );

        $this->notify_url = str_replace('admin/', '', $this->notify_url);
        
        $this->data         = $this->model_sale_order->getOrder($order_id);
        $time               = date('YmdHis', time());
        $last_allowed_trans = array();
        
        foreach(array_reverse($this->data['payment_custom_field']) as $tr_data) {
            if('settle' == $this->request->post['action']
                && 'Auth' == $tr_data['transactionType']
            ) {
                $last_allowed_trans = $tr_data;
                break;
            }
            
            if('void' == $this->request->post['action']
                && in_array($tr_data['transactionType'], array('Auth', 'Settle', 'Sale'))
            ) {
                $last_allowed_trans = $tr_data;
                break;
            }
        }
        
        $amount = $this->get_price($this->data['total']);
        
        # when try to Void Zero Auth Transaction, just try to cancel the Rebilling
        if (0 == $amount
            && 'void' == $this->request->post['action']
            && !empty($last_allowed_trans['subscrIDs'])
            && $this->is_active_recurring($order_id)
        ) {
            $resp = array(
                'status'    => 0,
                'msg'       => ''
            );
            
            $resp = NUVEI_CLASS::call_rest_api(
                'cancelSubscription',
                $this->plugin_settings,
                ['merchantId', 'merchantSiteId', 'subscriptionId', 'timeStamp'],
                ['subscriptionId' => $last_allowed_trans['subscrIDs']]
            );

            if(!$resp || !is_array($resp)
                || @$resp['status'] == 'ERROR'
                || @$resp['transactionStatus'] == 'ERROR'
            ) {
                $resp['msg'] = $this->language->get('Cancel requrest for Subscription ID ') 
                    . $last_allowed_trans['subscrIDs'] . $this->language->get('failed.') . ' ';
            }
            elseif(@$resp['transactionStatus'] == 'DECLINED') {
                $resp['msg'] = $this->language->get('Cancel requrest for Subscription ID ') 
                    . $last_allowed_trans['subscrIDs'] . $this->language->get('was declined.') . ' ';
            }

            $resp['status'] = 1;
            
            $this->db->query(
                'UPDATE ' . DB_PREFIX . 'order '
                . 'SET order_status_id = ' . $this->config->get(NUVEI_SETTINGS_PREFIX . 'canceled_status_id') . ' '
                . 'WHERE order_id = ' . $order_id
            );
            
            exit(json_encode($resp));
        }
        
        # normal Void or Settle
        $params = array(
//            'clientRequestId'       => $time . '_' . $last_allowed_trans['transactionId'],
            'clientUniqueId'        => $order_id . '_' . uniqid(),
            'amount'                => $amount,
            'currency'              => $this->data['currency_code'],
            'relatedTransactionId'  => $last_allowed_trans['transactionId'],
            'urlDetails'            => array('notificationUrl' => $this->notify_url),
            'url'                   => $this->notify_url, // a custom parameter
            'authCode'              => $last_allowed_trans['authCode'],
        );

        $resp = NUVEI_CLASS::call_rest_api(
            'settle' == $this->request->post['action'] ? 'settleTransaction' : 'voidTransaction',
            $this->plugin_settings,
            ['merchantId', 'merchantSiteId', 'clientRequestId', 'clientUniqueId', 'amount', 'currency', 'relatedTransactionId', 'authCode', 'url', 'timeStamp'],
            $params
        );
		
		if(!$resp || !is_array($resp)
            || @$resp['status'] == 'ERROR'
            || @$resp['transactionStatus'] == 'ERROR'
        ) {
            echo json_encode(array('status' => 0));
			exit;
        }
		
		if(@$resp['transactionStatus'] == 'DECLINED') {
            echo json_encode(array(
				'status' => 0,
				'msg' => 'Your request was Declined.'
			));
			exit;
        }
		
		echo json_encode(array('status' => 1));
		exit;
    }
    
    /**
     * Help function to check is there active Subscription for an Order.
     * 
     * @param int $order_id
     * @return bool
     */
    private function is_active_recurring($order_id)
    {
        $query =
            "SELECT order_recurring_id "
            . "FROM ". DB_PREFIX ."order_recurring "
            . "WHERE order_id = " . (int) $order_id . " "
            . "AND status = 1"; // active

        $res = $this->db->query($query);

        if(!isset($res->num_rows) || $res->num_rows == 0) {
            return false;
        }
        
        return true;
    }
    
    private function subscription_cancel()
    {
        NUVEI_CLASS::create_log($this->plugin_settings, 'subscription_cancel');
        
        $order_id = NUVEI_CLASS::get_param('orderId', FILTER_VALIDATE_INT);
        
        // search for active subscription
        if(!$this->is_active_recurring($order_id)) {
            exit(json_encode([
                'status'    => 0,
                'msg'       => $this->language->get('text_no_active_subscr'),
            ]));
        }
        
        $order_info = $this->model_sale_order->getOrder($order_id);
        
        if (empty($order_info['payment_custom_field'])) {
            NUVEI_CLASS::create_log($this->plugin_settings, $order_info, 'subscription_cancel error');
            
            exit(json_encode([
                'status'    => 0,
                'msg'       => $this->language->get('error_missing_nuvei_data'),
            ]));
        }
        
        $order_data = $order_info['payment_custom_field'];
        
        foreach (array_reverse($order_data) as $transaction) {
            if (empty($transaction['subscrIDs'])) {
                continue;
            }
            
            $resp = NUVEI_CLASS::call_rest_api(
                'cancelSubscription',
                $this->plugin_settings,
                array('merchantId', 'merchantSiteId', 'subscriptionId', 'timeStamp'),
                ['subscriptionId' => $transaction['subscrIDs']]
            );

            // On Error
            if (empty($resp['status']) || 'SUCCESS' != $resp['status']) {
                $msg = $this->language->get('Error when try to cancel Subscription #')
                    . $transaction['subscrIDs'] . ' ';

                if (!empty($resp['reason'])) {
                    $msg .= '<br/>' . $this->language->get('Reason: ') . $resp['reason'];
                }

                $this->model_sale_order->addOrderHistory(
                    $order_id,
                    $order_info['order_status_id'],
                    $msg,
                    true // $send_message
                );
                
                exit(json_encode([
                    'status'    => 0,
                    'msg'       => $msg,
                ]));
            }

            exit(json_encode([
                'status'    => 1,
            ]));
        }
        
		exit(json_encode([
            'status'    => 0,
            'msg'       => $this->language->get('error_missing_subscr_data'),
        ]));
    }
    
    /**
     * Check required Nuvei Setting when try to save them.
     * 
     * @param string NUVEI_CONTROLLER_PATH
     * @return array $this->data
     */
    private function validate_settings()
    {
        $this->data = $this->load->language(NUVEI_CONTROLLER_PATH); // add translation in the data
        
        // when save the settings
		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            // Validate
            $save_post = true;
            
            if (!$this->user->hasPermission('modify', NUVEI_CONTROLLER_PATH)) {
                $this->error['warning'] = $this->language->get('error_permission');
            }
            
            // if all is ok - save settings
            if($save_post) {
                $this->model_setting_setting->editSetting(
                    trim(NUVEI_SETTINGS_PREFIX, '_'),
                    $this->request->post
                );
                
                $this->session->data['success'] = $this->data['text_success'];
            }
        }
        
        $this->data['error_permission'] = false;
    }
    
    private function validate() {
        if (!$this->user->hasPermission('modify', NUVEI_CONTROLLER_PATH)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
    }
    
    /**
	 * Here we only set template variables
	 */
	private function get_payment_methods()
    {
        $payment_methods    = [];
        $session_token      = $this->get_session_token();
        
        // on missing session token
        if(empty($session_token)) {
            NUVEI_CLASS::create_log($this->plugin_settings, '','Missing session token', 'WARN');
            
            $this->data['nuvei_pms'] = $payment_methods;
            return;
        }
        
        $nuvei_block_pms = @$this->plugin_settings[NUVEI_SETTINGS_PREFIX . 'block_pms'];
        
        if(!is_array($nuvei_block_pms)) {
            $nuvei_block_pms = [];
        }
        
        NUVEI_CLASS::create_log($this->plugin_settings, $nuvei_block_pms, '$nuvei_block_pms');
			
		$apms_params		= array(
			'sessionToken'      => $session_token,
			'languageCode'      => $this->language->get('code'),
		);
        
		$res = NUVEI_CLASS::call_rest_api(
            'getMerchantPaymentMethods',
            $this->plugin_settings,
            array('merchantId', 'merchantSiteId', 'clientRequestId', 'timeStamp'),
            $apms_params,
        );
        
		if(!empty($res['paymentMethods']) && is_array($res['paymentMethods'])) {
            
            foreach($res['paymentMethods'] as $pm) {
                if(empty($pm['paymentMethod'])) {
                    continue;
                }
                
                $pm_name = '';
                
                if(!empty($pm['paymentMethodDisplayName'][0]['message'])) {
                    $pm_name = $pm['paymentMethodDisplayName'][0]['message'];
                }
                else {
                    $pm_name = ucfirst(str_replace('_', ' ', $pm['paymentMethod']));
                }
                
                $payment_methods[$pm['paymentMethod']] = [
                    'name'      => $pm_name,
                    'selected'  => in_array($pm['paymentMethod'], $nuvei_block_pms) ? 1 : 0
                ];
            }
		}
        
		$this->data['nuvei_pms'] = $payment_methods;
	}
    
    /**
     * Try to get a session token
     * 
     * @return string
     */
    private function get_session_token() {
        $resp = NUVEI_CLASS::call_rest_api(
            'getSessionToken', 
            $this->plugin_settings, 
            ['merchantId', 'merchantSiteId', 'clientRequestId', 'timeStamp']//,
            //['clientRequestId'   => date('YmdHis', time()) . '_' . uniqid()]
        );
        
        if(!empty($resp['sessionToken'])) {
            return $resp['sessionToken'];
        }
        
        return '';
    }
    
    private function get_nuvei_vars()
    {
        $order_id               = (int) $this->request->post['orderId'];
        $this->data             = $this->model_sale_order->getOrder($order_id);
        $nuvei_last_trans       = array();
        $nuvei_refunds          = array();
        $remainingTotalCurr     = '';
        $nuvei_remaining_total  = $this->get_price($this->data['total']);
        $nuveiAllowRefundBtn    = 0;
        $nuveiAllowVoidBtn      = 0;
        $nuveiAllowSettleBtn    = 0;
        $allowCancelSubsBtn     = 0;
        $isNuveiOrder           = NUVEI_PLUGIN_CODE == $this->data['payment_code'] ? 1 : 0;
        $historyPage            = 1;
        $histories              = array();
        
        if (isset($this->request->post['historyPage'])) {
			$page = (int) $this->request->post['historyPage'];
		}
        
        // replace short history date with full date
		$historyResults = $this->model_sale_order->getOrderHistories($order_id, ($historyPage - 1) * 10, 10);
        
        foreach ($historyResults as $result) {
			$histories[] = date($this->language->get('datetime_format'), strtotime($result['date_added']));
		}
        
        if(1 == $isNuveiOrder
            && !empty($this->data['payment_custom_field']) 
            && is_array($this->data['payment_custom_field'])
        ) {
            $nuvei_last_trans       = end($this->data['payment_custom_field']);
            $data['paymentMethod']  = $nuvei_last_trans['paymentMethod'];

            foreach($this->data['payment_custom_field'] as $trans_data) {
                if(in_array($trans_data['transactionType'], array('Refund', 'Credit'))
                    && 'approved' == $trans_data['status']
                ) {
                    $nuvei_remaining_total		-= $trans_data['totalAmount'];
                    $ref_data					= $trans_data;
                    $ref_data['amount_curr']	= '-' . $this->currency->format(
                        $trans_data['totalAmount'],
                        $this->data['currency_code'],
                        1 // 1 in case the amout is converted, else - $this->data['currency_value']
                    );

                    $nuvei_refunds[] = $ref_data;
                }
            }

            // can we show Refund button
            if(in_array($nuvei_last_trans['transactionType'], array('Refund', 'Credit', 'Sale', 'Settle'))
                && 'approved' == $nuvei_last_trans['status']
                && in_array($nuvei_last_trans['paymentMethod'], array("cc_card", "apmgw_expresscheckout"))
                //&& round($data['remainingTotal'], 2) > 0
                && round($nuvei_remaining_total, 2) > 0
            ) {
                $nuveiAllowRefundBtn = 1;
            }

            // can we show Void button
            if(in_array($nuvei_last_trans['transactionType'], array('Auth', 'Settle', 'Sale'))
                && "cc_card" == $nuvei_last_trans['paymentMethod']
            ) {
                $nuveiAllowVoidBtn = 1;
            }
            if ($this->data['order_status_id']  == $this->config->get(NUVEI_SETTINGS_PREFIX . 'canceled_status_id')
                || 0 == (float) $nuvei_remaining_total
            ) {
                $nuveiAllowVoidBtn = 0;
            }
            
            // can we show Settle button
            if('Auth' == $nuvei_last_trans['transactionType']
                && 'approved' == $nuvei_last_trans['status']
                && (float) $nuvei_last_trans['totalAmount'] > 0
            ) {
                $nuveiAllowSettleBtn = 1;
            }
            
            // can we show Cancel Subscription Button
            if($this->is_active_recurring($order_id)) {
                $allowCancelSubsBtn = 1;
            }
            
            $remainingTotalCurr = $this->currency->format(
                $nuvei_remaining_total,
                $this->data['currency_code'],
                1 // 1 in case the amout is converted, else - $this->data['currency_value']
            );
        }

        exit(json_encode([
            'nuveiAllowRefundBtn'           => $nuveiAllowRefundBtn,
            'nuveiAllowVoidBtn'             => $nuveiAllowVoidBtn,
            'nuveiAllowSettleBtn'           => $nuveiAllowSettleBtn,
            'nuveiAllowCancelSubsBtn'       => $allowCancelSubsBtn,
            'nuveiRefunds'                  => json_encode($nuvei_refunds),
            'remainingTotalCurr'            => $remainingTotalCurr, // formated
            'isNuveiOrder'                  => $isNuveiOrder,
            'orderTotal'                    => round($nuvei_remaining_total, 2),
            'currSymbolRight'               => $this->currency->getSymbolRight($this->data['currency_code']),
            'currSymbolLeft'                => $this->currency->getSymbolLeft($this->data['currency_code']),
            'histories'                     => $histories,
            
            'nuveiRefundAmountError'        => $this->language->get('nuvei_refund_amount_error'),
            'nuveiUnexpectedError'          => $this->language->get('nuvei_unexpected_error'),
            'nuveiOrderConfirmDelRefund'    => $this->language->get('nuvei_order_confirm_del_refund'),
            'nuveiCreateRefund'             => $this->language->get('nuvei_create_refund'),
            'nuveiOrderConfirmRefund'       => $this->language->get('nuvei_order_confirm_refund'),
            'nuveiBtnManualRefund'          => $this->language->get('nuvei_btn_manual_refund'),
            'nuveiBtnRefund'                => $this->language->get('nuvei_btn_refund'),
            'nuveiBtnVoid'                  => $this->language->get('nuvei_btn_void'),
            'btnCancelSubscr'               => $this->language->get('nuvei_btn_cancel_subscr'),
            'nuveiOrderConfirmCancel'       => $this->language->get('nuvei_order_confirm_cancel'),
            'orderConfirmCancelSubscr'      => $this->language->get('nuvei_order_confirm_cancel_subscr'),
            'nuveiBtnSettle'                => $this->language->get('nuvei_btn_settle'),
            'nuveiOrderConfirmSettle'       => $this->language->get('nuvei_order_confirm_settle'),
            'nuveiMoreActions'              => $this->language->get('nuvei_more_actions'),
            'nuveiRefundId'                 => $this->language->get('nuvei_refund_id'),
            'nuveiDate'                     => $this->language->get('nuvei_date'),
            'nuveiRemainingTotal'           => $this->language->get('nuvei_remaining_total'),
        ]));
    }
    
    /**
     * Get some price by the currency convert rate.
     */
    private function get_price($price)
    {
        $new_price = round((float) $price * $this->data['currency_value'], 2);
        return number_format($new_price, 2, '.', '');
    }
    
}
