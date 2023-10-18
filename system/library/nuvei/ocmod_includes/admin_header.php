<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR . 'nuvei' . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

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