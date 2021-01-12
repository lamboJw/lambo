<?php
define('ROOT_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/views');
define('CONTROLLER_PATH', APP_PATH . '/controllers');
define('ROUTE_PATH', APP_PATH . '/routes');

define('SYSTEM_PATH', ROOT_PATH . '/system');
define('SYS_KERNEL_PATH', SYSTEM_PATH . '/kernel');

define('STATIC_NAME', 'static');
define('STATIC_PATH', ROOT_PATH . '/' . STATIC_NAME);

define('LEVEL_NONE', 0);
define('LEVEL_ERROR', 1);
define('LEVEL_DEBUG', 2);
define('LEVEL_NOTICE', 3);
define('LEVEL_INFO', 4);
define('LEVEL_ALL', 5);

define('SWOOLE_HTTP_SERVER',1);
define('CO_HTTP_SERVER',2);

require_once SYS_KERNEL_PATH . '/run.php';
