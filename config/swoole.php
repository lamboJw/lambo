<?php
$config = [
    // http服务器基本配置
    'http' => [
        // 服务器地址
        'host' => '0.0.0.0',

        // 服务器监听端口
        'port' => '10086',

        // 是否启用ssl，协程风格使用
        'ssl' => false,

        // socket类型，如果开启SSL，需增加 SWOOLE_SSL
        // 仅当app.server_type=SWOOLE_HTTP_SERVER时有效
        'socket_type' => SWOOLE_SOCK_TCP,

        // 服务器模式，SWOOLE_PROCESS 或 SWOOLE_BASE
        // 仅当app.server_type=SWOOLE_HTTP_SERVER时有效
        'server_mode' => SWOOLE_BASE,

        // 是否开启websocket服务
        'open_websocket' => false,

        // 客户端与服务器关闭连接的指令
        'close_command' => 'close',

        // websocket处理服务
        'websocket_service' => \app\websocket\WebsocketService::class,

        // 协程websocket服务器开启广播功能
        'co_ws_broadcast' => true,

        // 协程websocket客户端连接池容量
        'co_ws_pool_size' => 1024,
    ],
    // websocket基本配置
    'websocket' => [
        // 设置WebSocket子协议
        'websocket_subprotocol' => '',

        // 启用在 onMessage 回调中接收关闭帧（opcode 为 0x08 的帧）
        'open_websocket_close_frame' => false,

        // 启用在 onMessage 回调中接收Ping帧（opcode 为 0x09 的帧）
        'open_websocket_ping_frame' => false,

        // 启用在 onMessage 回调中接收Pong帧（opcode 为 0x0A 的帧）
        'open_websocket_pong_frame' => false,

        // 启用数据压缩
        'websocket_compression' => false,
    ],
    // 服务器详细配置
    'server' => [
        // worker进程数量
        'worker_num' => 1,

        // reactor进程数量
        'reactor_num' => 4,

        // worker进程执行N次请求后重启，避免内存泄露
        // 协程风格下，0为不限制
        'max_request' => 0,

        // 最大允许连接数，默认 ulimit -n
        // 当实际连接数高时，适当调高ulimit -n，同时修改该选项
        'max_connection' => 10000,

        // 是否开启压缩
        'http_compression' => true,

        // 响应内容压缩等级
        'http_compression_level' => 2,

        // 数据包分发策略，1：轮询，2：固定，3：抢占，4：IP，5、UID，6：stream
        // 仅当异步风格SWOOLE_PROCESS时有效
        'dispatch_mode' => 3,

        // 开启静态文件请求处理功能
        'enable_static_handler' => true,

        // 项目根目录
        'document_root' => ROOT_PATH,

        // 静态文件路径，配合document_root使用
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

        // ssl加密证书
        'ssl_cert_file' => '',

        // ssl加密密钥
        'ssl_key_file' => '',

        // ssl加密协议，0为全部支持
        'ssl_protocols' => 0,
    ],
    // 协程配置
    'coroutine' => [
        // 开启一键协程化范围
        'hook_flags' => SWOOLE_HOOK_ALL,

        // 建立 TCP 连接超时时间
        'socket_connect_timeout' => 2,

        // TCP 读 / 写操作超时时间（统一设置）
        'socket_timeout' => 60,

        // TCP 读操作超时时间（单独设置）
//        'socket_read_timeout' => 60,

        // TCP 写操作超时时间（单独设置）
//        'socket_write_timeout' => 60,

        // 自定义 reactor 退出的条件
//        'exit_condition' => function () {
//            return true;
//        },
    ],
];
