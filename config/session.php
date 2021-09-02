<?php
$config = [
    //是否开启session
    'start_session' => false,

    //保存session_id的cookie名称
    'session_id_name' => 'lambo_session',

    //保存session_id的cookie的samesite属性
    'samesite' => 'Lax',

    //session有效时间（秒）
    'max_life_time' => 86400,

    //保存session信息的驱动，file，redis，database
    'driver' => 'file',

    //生成session_id的加密key
    'encrypt_key' => 'LSNBDxH6zSyB4hrovEj9',

    //database驱动时，存放数据的表名；redis驱动时，key的前缀
    'table' => 'lambo_session',

    //database驱动时，数据表使用的数据库
    'database_config' => 'default',
];