<?php

use system\kernel\Route;
use system\kernel\Router;

Router::get('/', 'index', 'index');
Router::post('/', 'index', 'test');
Router::get('/test/{id}', 'index', 'test');
Router::get('/ws_client', 'wsclient', 'index');
Router::get('/file', 'file', 'index');
Router::middleware(['test'])->prefix('admin')->group(function () {
    Router::get('/test/{obj}', function ($obj) {
        response('admin/test'.$obj);
    });
});