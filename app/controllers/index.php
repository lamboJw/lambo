<?php


namespace app\controllers;


use app\logic\testLogic;
use app\models\example;
use co;
use Co\System;
use Swoole\Coroutine;
use Swoole\Coroutine\Barrier;
use function Co\run;

class index
{
    public function index(){
        view('index');
    }

    public function test(){
        $model = new example();
        $re = $model->getInfo(1);
        response(var_export($re, true));
    }
}