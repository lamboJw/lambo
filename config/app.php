<?php
$config = [
    'load_vendor' => true,                  //是否加载vendor

    'env' => 'development',

    'debug' => true,                        //是否记录debug信息

    'debug_level' => LEVEL_ALL,           //记录debug信息的最高等级

    'log_path' => STATIC_PATH.'/logs',      //日志文件的保存路径

    'log_level' => LEVEL_NONE,             //记录日志的最高等级

    'log_file_extension' => 'txt',          //日志文件的后缀

    'log_date_format' => 'Y-m-d H:i:s',     //日志消息的时间格式

    'log_file_permissions' => 0644,         //日志文件的权限

    'server_type' => SWOOLE_HTTP_SERVER,    //启动服务器的类型，SWOOLE_HTTP_SERVER 或 CO_HTTP_SERVER

    'std_output_to_page' => true,           //标准输出是否输出到页面
];