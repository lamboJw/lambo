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
        $redis = new BaseRedis();
        $redis->select(2);
        $redis->set('test',1);
        $redis->set('test',2);
        view('test', ['a'=>'Hello World']);
    }

    public function index2(){
        $redis = new BaseRedis();
//        $redis->select(1);
        $redis->set('test',3);
    }
}
