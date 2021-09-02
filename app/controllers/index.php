<?php


namespace app\controllers;


use app\models\example;

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