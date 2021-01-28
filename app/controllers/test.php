<?php

namespace app\controllers;

class test
{
    public function index()
    {
        $a = request('a');
        view('test', ['a' => $a]);
    }
}
