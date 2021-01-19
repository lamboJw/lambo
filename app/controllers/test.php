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
        /*$model = new example();
        $re = $model->getList(['id'=>[1,2,3]],"title");
        api_response(1,'success',$re);*/
//        $redis = new BaseRedis();
//        $re = $redis->sadd('test',date("Y-m-d H:i:s"));
        api_response(1, 'success');
    }

    public function index2()
    {
        echo 'index2';
    }
}
