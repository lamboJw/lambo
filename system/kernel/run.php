<?php
defined('SYSTEM_PATH') or exit('No direct script access allowed');

require_once SYS_KERNEL_PATH . '/common.php';

if (config('app.load_vendor', false)) {
    require_once 'vendor/autoload.php';
}

//创建redis连接池
if (config('app.enable_redis_pool')) {
    CO\run(function () {
        $redis_config_key = config('app.redis_config_key');
        $redis_config = config("redis.{$redis_config_key}");
        new \system\kernel\BaseRedis($redis_config);
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

// 自动加载指定library和helper
autoload_lib_and_helper();

// 设置协程配置
$coroutine_config = config('swoole.coroutine');
if (!empty($coroutine_config)) {
    Co::set($coroutine_config);
}

(new system\kernel\Server())->run_http_server();
