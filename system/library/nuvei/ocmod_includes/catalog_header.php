<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR  . 'nuvei' . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

try {
    $this->load->model('setting/setting');
    
    // add Nuvei Checkout SDK
//    if('prod' == $this->config->get(NUVEI_SETTINGS_PREFIX . 'sdk_version')) {
        $this->document->addScript(NUVEI_SDK_URL_PROD);
//    }
//    else {
//        $this->document->addScript(NUVEI_SDK_URL_INT);
//    }
    
    // add Nuvei common modify script
    $this->document->addScript('catalog/view/javascript/nuvei_common_js_mod.js');
}
catch (Exception $e) {
    $data['error_warning'] = 'Nuvei modification exception: ' . $e->getMessage();
}