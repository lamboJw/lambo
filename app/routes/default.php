<?php

use system\kernel\Route;
use system\kernel\Router;

(new Router())->route('/','index','index');
(new Router())->route('/test','index','test');
(new Router())->route('/ws_client','wsclient','index');
(new Router())->route('/file','file','index');
(new Router())->group(['test'],[
    (new Route('/test2','test','index2')),
]);