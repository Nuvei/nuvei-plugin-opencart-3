<?php
// HTTP
define('HTTP_SERVER', 'http://opencart-3.localhost/');

// HTTPS
define('HTTPS_SERVER', 'http://opencart-3.localhost/');

// DIR
define('DIR_APPLICATION', '/opt/lampstack-7.4.30-0/apache2/htdocs/opencart-3/catalog/');
define('DIR_SYSTEM', '/opt/lampstack-7.4.30-0/apache2/htdocs/opencart-3/system/');
define('DIR_IMAGE', '/opt/lampstack-7.4.30-0/apache2/htdocs/opencart-3/image/');
define('DIR_STORAGE', DIR_SYSTEM . 'storage/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/theme/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

define('TWIG_CACHE', false);

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '123456');
define('DB_DATABASE', 'oc_3');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');