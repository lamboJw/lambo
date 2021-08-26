<?php
$config = [
    //是否开启session
    'start_session' => true,

    //保存session_id的cookie名称
    'session_id_name' => 'lambo_session',

    //保存session_id的cookie的samesite属性
    'samesite' => 'Lax',

    //session有效时间（秒）
    'expire_time' => 86400,

    //保存session信息的驱动，file，redis，database
    'driver' => 'file',

    //生成session_id的加密key
    'encrypt_key' => 'LSNBDxH6zSyB4hrovEj9',
];