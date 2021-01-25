<?php

use system\kernel\Route;
use system\kernel\Router;

(new Router())->middleware([])->route('/test','test','index');

//(new Router())->route('/test2','test1','func');

(new Router())->group(['test'],[
    (new Route('/test2','test','index2')),
]);