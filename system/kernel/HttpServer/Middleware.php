<?php


namespace system\kernel\HttpServer;


abstract class Middleware
{
    abstract public function handle();
}