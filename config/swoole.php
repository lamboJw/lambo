<?php
$config = [
    'http' => [
        'host' => '127.0.0.1',
        'port' => '10086',
        'ssl' => false,
        'socket_type' => SWOOLE_SOCK_TCP,
    ],
    'websocket' => [
        'open_websocket' => false,                  //开启websocket服务
        'close_command' => 'close'                  //客户端与服务器关闭连接的指令
    ],
    'server' => [
        'worker_num' => swoole_cpu_num() * 2,       //worker进程数量
        'reactor_num' => swoole_cpu_num() * 2,      //reactor进程数量
        'max_request' => 1000,                      //worker进程执行N次请求后重启，避免内存泄露
        'http_compression' => true,                 //是否开启压缩
        'http_compression_level' => 2,              //响应内容压缩等级
        'document_root' =>  ROOT_PATH,              //文件根目录
        'enable_static_handler' => false,           //开启静态文件请求处理功能
        'static_handler_locations' => ['/'.STATIC_NAME],    //静态文件路径
        'upload_tmp_dir' => STATIC_PATH.'/uploads/',        //上传文件的临时目录
    ],
    'coroutine' => [
        'hook_flags' => SWOOLE_HOOK_ALL,    // 开启一键协程化范围
        'socket_connect_timeout' => 2,      // 建立 TCP 连接超时时间
        'socket_timeout' => 60,             // TCP 读 / 写操作超时时间
//        'socket_read_timeout' => 60,        // TCP 读操作超时时间
//        'socket_write_timeout' => 60        // TCP 写操作超时时间
//        'exit_condition' => function () {   //自定义 reactor 退出的条件
//            return true;
//        },
        'log_level' => SWOOLE_LOG_NONE,
    ],
];