<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR 
    . 'nuvei' . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

try {
    // $order_info came from the Order class we modify
    
    $this->load->model('setting/setting');

    $nuvei_last_trans			= array();
    $nuvei_refunds              = array();
    $nuvei_settings             = $this->model_setting_setting->getSetting(trim(NUVEI_SETTINGS_PREFIX, '_'));
    $nuvei_remaining_total		= $order_info['total'];
    
    //$data['nuveiAjaxUrl']		= 'index.php?route=' . NUVEI_CONTROLLER_PATH . '&' . NUVEI_TOKEN_NAME . '=' 
    //    . NUVEI_CLASS::get_param(NUVEI_TOKEN_NAME);

    $nuveiAllowRefundBtn    = 0;
    $nuveiAllowVoidBtn      = 0;
    $nuveiAllowSettleBtn    = 0;
    $isNuveiOrder           = 'nuvei' == $order_info['payment_code'] ? 1 : 0;

    if(1 == $isNuveiOrder
        && !empty($order_info['payment_custom_field']) 
        && is_array($order_info['payment_custom_field'])
    ) {
        $nuvei_last_trans       = end($order_info['payment_custom_field']);
        $data['paymentMethod']  = $nuvei_last_trans['paymentMethod'];

        foreach($order_info['payment_custom_field'] as $trans_data) {
            if(in_array($trans_data['transactionType'], array('Refund', 'Credit'))
                && 'approved' == $trans_data['status']
            ) {
                $nuvei_remaining_total		-= $trans_data['totalAmount'];
                $ref_data					= $trans_data;
                $ref_data['amount_curr']	= '-' . $this->currency->format(
                    $trans_data['totalAmount'],
                    $order_info['currency_code'],
                    $order_info['currency_value']
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
        if(!in_array($nuvei_last_trans['transactionType'], array('Refund', 'Credit', 'Void'))
            && "cc_card" == $nuvei_last_trans['paymentMethod']
        ) {
            $nuveiAllowVoidBtn = 1;
        }

        // can we show Settle button
        if('Auth' == $nuvei_last_trans['transactionType']
            && 'approved' == $nuvei_last_trans['status']
        ) {
            $nuveiAllowSettleBtn = 1;
        }
        
        $remainingTotalCurr = $this->currency->format(
            $nuvei_remaining_total,
            $order_info['currency_code'],
            $order_info['currency_value']
        );
    }
    
    $this->session->data['isNuveiOrder']            = $isNuveiOrder;
    $this->session->data['nuveiRefunds']            = $nuvei_refunds;
    $this->session->data['nuveiAllowRefundBtn']     = $nuveiAllowRefundBtn;
    $this->session->data['nuveiAllowVoidBtn']       = $nuveiAllowVoidBtn;
    $this->session->data['nuveiAllowSettleBtn']     = $nuveiAllowSettleBtn;
    $this->session->data['nuveiRemainingTotalCurr'] = $remainingTotalCurr;
}
catch (Exception $e) {
    $data['error_warning'] = 'Nuvei modification exception: ' . $e->getMessage();
}
