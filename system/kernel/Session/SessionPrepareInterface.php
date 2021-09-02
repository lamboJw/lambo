<?php


namespace system\kernel\Session;


interface SessionPrepareInterface
{
    /**
     * 开启session前准备
     */
    public function prepare();
}