<?php

if($this->user->isLogged()) {
    $this->document->addScript('view/javascript/nuvei_version_checker.js');
}