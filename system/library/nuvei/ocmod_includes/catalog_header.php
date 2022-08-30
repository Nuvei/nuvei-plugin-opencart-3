<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR  . 'nuvei' . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

try {
    $this->load->model('setting/setting');
    
    if('prod' == $this->config->get(NUVEI_SETTINGS_PREFIX . 'sdk_version')) {
        $this->document->addScript(NUVEI_SDK_URL_PROD);
    }
    else {
        $this->document->addScript(NUVEI_SDK_URL_INT);
    }
}
catch (Exception $e) {
    $data['error_warning'] = 'Nuvei modification exception: ' . $e->getMessage();
}