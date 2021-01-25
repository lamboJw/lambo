<?php
define('ROOT_PATH', __DIR__);

//配置文件路径
define('CONFIG_PATH', ROOT_PATH . '/config');

// 应用文件路径
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/views');
define('CONTROLLER_PATH', APP_PATH . '/controllers');
define('ROUTE_PATH', APP_PATH . '/routes');

// 框架核心路径
define('SYSTEM_PATH', ROOT_PATH . '/system');
define('SYS_KERNEL_PATH', SYSTEM_PATH . '/kernel');

// 静态文件路径
define('STATIC_NAME', 'static');
define('STATIC_PATH', ROOT_PATH . '/' . STATIC_NAME);
define('VIEW_CACHE_PATH', STATIC_PATH . '/view_cache');

// debug、log等级
define('LEVEL_NONE', 0);
define('LEVEL_ERROR', 1);
define('LEVEL_DEBUG', 2);
define('LEVEL_NOTICE', 3);
define('LEVEL_INFO', 4);
define('LEVEL_ALL', 5);

// http服务器类型
define('SWOOLE_HTTP_SERVER', 1);
define('CO_HTTP_SERVER', 2);

// 退出状态码
define('SWOOLE_RESPONSE_EXIT', -13800);

// Model类时间类型
define('MODEL_DATETIME', 1);
define('MODEL_DATE', 2);
define('MODEL_UNIX_TIMESTAMP', 3);

require_once SYS_KERNEL_PATH . '/run.php';