<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR  . 'nuvei' . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

try {
    $this->load->model('setting/setting');
    
    // add Nuvei Checkout SDK
    if (!empty($_SERVER['SERVER_NAME']) 
        && 'opencartautomation.gw-4u.com' == $_SERVER['SERVER_NAME']
        && defined('NUVEI_SDK_URL_TAG')
    ) {
        $this->document->addScript(NUVEI_SDK_URL_TAG);
    }
    else {
        $this->document->addScript(NUVEI_SDK_URL_PROD);
    }
     
    // add Nuvei common modify script
    $this->document->addScript('catalog/view/javascript/nuvei_common_js_mod.js');
}
catch (Exception $e) {
    $data['error_warning'] = 'Nuvei modification exception: ' . $e->getMessage();
}