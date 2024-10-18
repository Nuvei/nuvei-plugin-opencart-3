<?php

if (empty($json['error'])) {
    require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR  . 'nuvei' . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

    $this->language->load(NUVEI_CONTROLLER_PATH);
    
    $nuveiSelectedRecId = isset($_POST['recurring_id']) ? (int) $_POST['recurring_id'] : -1;
     
    // for the incoming product
    if ($recurrings 
        && isset($recurrings[$nuveiSelectedRecId]['name']) 
        && strpos(strtolower($recurrings[$nuveiSelectedRecId]['name']), NUVEI_PLUGIN_CODE) !== false
    ) {
        if(count($this->cart->getProducts())) {
            $json['error']['recurring'] = $this->language->get('nuvei_rec_error');
        }
        if(empty($this->session->data['customer_id'])) {
            $json['error']['recurring'] = $this->language->get('nuvei_rec_user_error');
        }
    }
    // check for rebilling products into the Cart
    else {
        $rebilling_data = $this->cart->getRecurringProducts();
        
        if(count($rebilling_data) > 0) {
            foreach($rebilling_data as $reb_data) {
                // check for nuvei into recurring name
                if (isset($reb_data['recurring']['name'])
                    && strpos(strtolower($reb_data['recurring']['name']), NUVEI_PLUGIN_CODE) !== false
                ) {
                    $json['error_nuvei'] = $this->language->get('nuvei_rec_error');
                    break;
                }
            }
        }
    }
}