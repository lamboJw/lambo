<?php

namespace app\controllers;

use app\logic\testLogic;
use app\models\example;
use system\kernel\BaseRedis;

class test
{
    public function __construct()
    {

    }

    public function index()
    {
//        $model = new example();
//        $data['db'] = $model->getList(['id'=>[1,2,3]],"name");
//        $redis = new BaseRedis();
//        $data['redis'] = $redis->sadd('test',date("Y-m-d H:i:s"));
//        api_response(1, 'success', $data);
        view('test', ['a'=>1,'b'=>2]);
    }

    public function index2()
    {
        echo 'index2';
    }
}
