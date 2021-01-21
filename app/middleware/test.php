<?php

namespace app\middleware;

use \system\kernel\Middleware;

class test extends Middleware
{

    public function handle()
    {
        if (request('a') === null) {
            return api_json(0, 'fail');
        }
        return true;
    }
}