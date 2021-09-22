<?php
defined('SYSTEM_PATH') or exit('No direct script access allowed');

require_once SYS_KERNEL_PATH . '/common.php';

if (config('app.load_vendor', false)) {
    require_once 'vendor/autoload.php';
}

// 设置协程配置
$coroutine_config = config('swoole.coroutine');
if (!empty($coroutine_config)) {
    Co::set($coroutine_config);
}

//创建redis连接池
if (config('app.enable_redis_pool')) {
    CO\run(function () {
        $redis_config_key = config('app.redis_config_key', 'default');
        $redis_config = config("redis.{$redis_config_key}");
        new \system\kernel\Database\Redis($redis_config);
    });
}

//创建mysql连接池
if (config('app.enable_mysql_pool')) {
    CO\run(function () {
        $mysql_config = config('database');
        foreach ($mysql_config as $db => $db_conf) {
            new \system\kernel\BaseModel($db, $db_conf);
        }
    });
}

//开启session
session_service()->prepare();
session_service()->gc();

// 自动加载指定library和helper
autoload_lib_and_helper();

(new system\kernel\Server())->run_http_server();
