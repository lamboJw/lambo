<?php
$config = [
    'http' => [
        'host' => '127.0.0.1',
        'port' => '10086',
        'ssl' => false,
        // 以下配置仅当app.server_type=SWOOLE_HTTP_SERVER时有效
        'socket_type' => SWOOLE_SOCK_TCP,                   // socket类型，如果开启HTTP2，需增加SWOOLE_SSL
        'server_mode' => SWOOLE_BASE,                    // 服务器模式，SWOOLE_PROCESS 或 SWOOLE_BASE
    ],
    'websocket' => [
        'open_websocket' => false,                          // 开启websocket服务
        'close_command' => 'close'                          // 客户端与服务器关闭连接的指令
    ],
    'server' => [
        'worker_num' => 8,                                  // worker进程数量
        'reactor_num' => 8,                                 // reactor进程数量
        'max_request' => 10000,                              // worker进程执行N次请求后重启，避免内存泄露
        'max_connection' => 2000,                           // 最大允许连接数，默认 ulimit -n
        'http_compression' => true,                         // 是否开启压缩
        'http_compression_level' => 2,                      // 响应内容压缩等级
        'dispatch_mode' => 3,                               // 数据包分发策略，1：轮询，2：固定，3：抢占，4：IP，5、UID，6：stream
        'document_root' =>  ROOT_PATH,                      // 文件根目录
        'enable_static_handler' => false,                   // 开启静态文件请求处理功能
        'static_handler_locations' => ['/'.STATIC_NAME],    // 静态文件路径
        'upload_tmp_dir' => STATIC_PATH.'/uploads/',        // 上传文件的临时目录
        'open_http2_protocol' => false,                     // 开启HTTP2服务器时需要设为true
        'daemonize' => true,                                // 作为守护进程运行
        'log_file' => STATIC_PATH.'/logs/server.log',       // 守护进程模式需要制定日志文件路径
        'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,        // 设置 Server 日志分割
        'log_date_format' => '%Y-%m-%d %H:%M:%S',           // 设置 Server 日志时间格式
        'enable_coroutine' => true,                         // 开启异步风格服务器的协程支持
        'hook_flags' => SWOOLE_HOOK_ALL,                    // 开启一键协程化范围
    ],
    'coroutine' => [
        'hook_flags' => SWOOLE_HOOK_ALL,                    // 开启一键协程化范围
        'socket_connect_timeout' => 2,                      // 建立 TCP 连接超时时间
        'socket_timeout' => 60,                             // TCP 读 / 写操作超时时间
//        'socket_read_timeout' => 60,                      // TCP 读操作超时时间
//        'socket_write_timeout' => 60                      // TCP 写操作超时时间
//        'exit_condition' => function () {                 // 自定义 reactor 退出的条件
//            return true;
//        },
        'log_level' => SWOOLE_LOG_NONE,
    ],
];