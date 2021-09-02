<?php

namespace system\kernel\Session;

use SessionHandlerInterface;

class SessionManager
{

    public function driver_handler(): SessionHandlerInterface
    {
        $driver = config('session.driver', 'file');
        if (!config('session.start_session', false) || !in_array($driver, ['file', 'redis', 'database'])) {
            $driver = 'empty';
        }
        return call_user_func([$this, "create_{$driver}_handler"]);
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

    private function create_empty_handler()
    {
        return new SessionEmptyHandler();
    }

    public function load_session()
    {
        $handler = $this->driver_handler();
        return new SessionStore($handler);
    }
}