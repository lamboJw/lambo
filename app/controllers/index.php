<?php


namespace app\controllers;


use app\models\example;

class index
{
    public function index(){
        view('index');
    }

    public function test(example $model, $id){
        var_dump($id);
        $re = $model->getInfo($id);
        response(var_export($re, true));
    }
}