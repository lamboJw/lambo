<?php
$config = [
    // 是否加载vendor
    'load_vendor' => true,

    // 是否记录debug信息
    'debug' => true,

    // 记录debug信息的最高等级
    'debug_level' => LEVEL_ALL,

    // 日志文件的保存路径
    'log_path' => STATIC_PATH.'/logs',

    // 记录日志的最高等级
    'log_level' => LEVEL_NONE,

    // 日志文件的后缀
    'log_file_extension' => 'txt',

    // 日志消息的时间格式
    'log_date_format' => 'Y-m-d H:i:s',

    // 日志文件的权限
    'log_file_permissions' => 0644,

    /*
     * 启动服务器的类型
     * 异步风格SWOOLE_HTTP_SERVER 或 协程风格CO_HTTP_SERVER
     */
    'server_type' => SWOOLE_HTTP_SERVER,

    // 标准输出是否输出到页面
    'std_output_to_page' => false,

    // 开启redis连接池
    'enable_redis_pool' => false,

    // redis使用配置
    'redis_config_key' => 'default',

    // 开启mysql连接池
    'enable_mysql_pool' => false,
];
