<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR 
        . 'nuvei' . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

try {
    // load the nuvei script
    $this->document->addScript('view/javascript/nuvei_orders.js');
    
    if(!is_array($data)) {
        $data = array();
    }

    // add all translated strings
    $data = array_merge($data, $this->load->language(NUVEI_CONTROLLER_PATH));

    // then load again default language file
    $this->load->language('sale/order');
}
catch (Exception $e) {
    $data['error_warning'] = 'Nuvei modification exception: ' . $e->getMessage();
}