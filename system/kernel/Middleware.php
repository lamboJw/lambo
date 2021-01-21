<?php


namespace system\kernel;


abstract class Middleware
{
    abstract public function handle();
}