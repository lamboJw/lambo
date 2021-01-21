<?php

use system\kernel\Route;
use system\kernel\Router;

(new Router())->middleware(['test'])->route('/test','test','index');

(new Router())->route('/test1','test1','func');

(new Router())->group(['test'],[
    (new Route('/test','test','index2')),
]);