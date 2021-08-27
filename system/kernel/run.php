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
        new \system\kernel\Redis($redis_config);
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

//开启session后，执行session垃圾回收
if (config('session.start_session')){
    $main_pid = posix_getpid();
    $session_gc_process = new Swoole\Process(function (\Swoole\Process $session_gc_process) use ($main_pid) {
        $manager = new \system\kernel\Session\SessionManager();
        while (true){
            if(!Swoole\Process::kill($main_pid, 0)){
                $session_gc_process->exit();
                break;
            }
            $manager->gc();
            sleep(5);
        }
    });
    $session_gc_process->start();
}

// 自动加载指定library和helper
autoload_lib_and_helper();

(new system\kernel\Server())->run_http_server();
