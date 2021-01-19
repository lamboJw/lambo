<?php
$config = [
    // http服务器基本配置
    'http' => [
        // 服务器地址
        'host' => '192.168.130.235',

        // 服务器监听端口
        'port' => '10086',

        // 是否启用ssl
        'ssl' => false,

        // 以下配置仅当app.server_type=SWOOLE_HTTP_SERVER时有效
        // socket类型，如果开启HTTP2，需增加SWOOLE_SSL
        'socket_type' => SWOOLE_SOCK_TCP,

        // 服务器模式，SWOOLE_PROCESS 或 SWOOLE_BASE
        'server_mode' => SWOOLE_BASE,
    ],
    // websocket基本配置
    'websocket' => [
        //是否开启websocket服务
        'open_websocket' => false,

        //客户端与服务器关闭连接的指令
        'close_command' => 'close'
    ],
    // 服务器详细配置
    'server' => [
        // worker进程数量
        'worker_num' => 8,

        // reactor进程数量
        'reactor_num' => 8,

        // worker进程执行N次请求后重启，避免内存泄露
        'max_request' => 10000,

        // 最大允许连接数，默认 ulimit -n
        'max_connection' => 2000,

        // 是否开启压缩
        'http_compression' => true,

        // 响应内容压缩等级
        'http_compression_level' => 2,

        // 数据包分发策略，1：轮询，2：固定，3：抢占，4：IP，5、UID，6：stream
        'dispatch_mode' => 3,

        // 文件根目录
        'document_root' => ROOT_PATH,

        // 开启静态文件请求处理功能
        'enable_static_handler' => false,

        // 静态文件路径
        'static_handler_locations' => ['/' . STATIC_NAME],

        // 上传文件的临时目录
        'upload_tmp_dir' => STATIC_PATH . '/uploads/',

        // 开启HTTP2服务器时需要设为true
        'open_http2_protocol' => false,

        // 作为守护进程运行
        'daemonize' => false,

        // 守护进程模式需要制定日志文件路径
        'log_file' => STATIC_PATH . '/logs/server.log',

        // 设置 Server 日志分割
        'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,

        // 设置 Server 日志时间格式
        'log_date_format' => '%Y-%m-%d %H:%M:%S',

        // 开启异步风格服务器的协程支持
        'enable_coroutine' => true,

        // 开启一键协程化范围
        'hook_flags' => SWOOLE_HOOK_ALL,
    ],
    // 协程配置
    'coroutine' => [
        // 开启一键协程化范围
        'hook_flags' => SWOOLE_HOOK_ALL,

        // 建立 TCP 连接超时时间
        'socket_connect_timeout' => 2,

        // TCP 读 / 写操作超时时间
        'socket_timeout' => 60,

        // TCP 读操作超时时间
//        'socket_read_timeout' => 60,

        // TCP 写操作超时时间
//        'socket_write_timeout' => 60

        // 自定义 reactor 退出的条件
//        'exit_condition' => function () {
//            return true;
//        },
        // 日志等级
        'log_level' => SWOOLE_LOG_NONE,
    ],
];
