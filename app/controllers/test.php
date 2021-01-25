<?php

namespace app\controllers;

use system\kernel\BaseRedis;

class test
{
    public function __construct()
    {

    }

    public function index()
    {
        $redis = new BaseRedis();
        $redis->select(3);
        sleep(10);
        $redis->set('test', '123');
        view('test', ['a'=>'Hello World']);
    }

    public function index2(){
        $redis = new BaseRedis();
        $redis->set('test', 'abc');
    }
}
