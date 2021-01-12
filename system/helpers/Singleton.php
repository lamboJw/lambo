<?php


namespace system\helpers;


trait Singleton
{
    private static $instance;

    static function getInstance(...$args)
    {
        if (!isset(self::$instance)) {
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }

    private function __clone()
    {
    }
}