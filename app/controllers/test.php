<?php
namespace app\controllers;

use app\logic\testLogic;

class test
{
    public function __construct()
    {

    }
    public function index(){
//        $test1 = new testLogic();
//        $test1->index();
        response("Hello World!!!");
    }

    public function index2(){
        echo 'index2';
    }
}