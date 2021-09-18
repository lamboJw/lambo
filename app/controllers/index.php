<?php


namespace app\controllers;


use app\models\example;
use system\kernel\Model;

class index
{
    public Model $model;

    public function __construct(example $model)
    {
        $this->model = $model;
    }

    public function index()
    {
        view('index');
    }

    public function test($id)
    {
        var_dump($id);
        $re = $this->model->getInfo($id);
        response(var_export($re, true));
    }
}