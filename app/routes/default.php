<?php

use system\kernel\Routing\Router;

Router::get('/', 'index', 'index');
Router::get('/test/{id}', 'index', 'test');
Router::get('/ws_client', 'wsclient', 'index');
Router::get('/file', 'file', 'index');
Router::middleware(['test'])->prefix('admin')->group(function () {
    Router::get('/test/{obj}', function ($obj) {
        response('admin/test'.$obj);
    });
});