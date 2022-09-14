<?php

if (empty($json['error'])) {
    require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR  . 'nuvei' . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

    $this->language->load(NUVEI_CONTROLLER_PATH);

    // for the incoming product
    if ($recurrings) {
        foreach($recurrings as $reb_data) {
            // Check for nuvei into recurring name.
            // Stop adding if there are any products in the cart already or
            // if the user is not logged.
            if (strpos(strtolower($reb_data['name']), NUVEI_PLUGIN_CODE) !== false) {
                if(count($this->cart->getProducts())) {
                    $json['error']['recurring'] = $this->language->get('nuvei_rec_error');
                    break;
                }
                if(empty($this->session->data['customer_id'])) {
                    $json['error']['recurring'] = $this->language->get('nuvei_rec_user_error');
                    break;
                }
            }
        }
    }
    // check for rebilling products into the Cart
    else {
        $rebilling_data = $this->cart->getRecurringProducts();

        if(count($rebilling_data) > 0) {
            foreach($rebilling_data as $reb_data) {
                // check for nuvei into recurring name
                if (strpos(strtolower($reb_data['recurring']['name']), NUVEI_PLUGIN_CODE) !== false) {
                    $json['error_nuvei'] = $this->language->get('nuvei_rec_error');
                    break;
                }
            }
        }
    }
}