<?php

namespace app\controllers;

class test
{
    public function index()
    {
        view('test', ['a' => 'Hello World']);
    }
}
