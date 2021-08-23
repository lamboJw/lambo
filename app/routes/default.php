<?php

use system\kernel\Route;
use system\kernel\Router;

(new Router())->route('/','index','index');
(new Router())->middleware([])->route('/test','test','index');
(new Router())->route('/ws_client','wsclient','index');
(new Router())->group(['test'],[
    (new Route('/test2','test','index2')),
]);