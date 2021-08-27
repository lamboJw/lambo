<?php

namespace system\kernel\Session;

use SessionHandlerInterface;

class SessionManager
{

    public function __construct()
    {
    }

    private function driver_handler(): SessionHandlerInterface
    {
        $drive = config('session.driver', 'file');
        return call_user_func([$this, "create_{$drive}_handler"]);
    }

    private function create_file_handler()
    {
        return new SessionFileHandler();
    }

    private function create_redis_handler()
    {
        return new SessionRedisHandler();
    }

    private function create_database_handler()
    {
        return new SessionDatabaseHandler();
    }

    public function load_session()
    {
        $handler = $this->driver_handler();
        return new SessionStore($handler);
    }

    public function gc(){
        $handler = $this->driver_handler();
        $handler->gc(config('session.max_life_time'));
    }
}