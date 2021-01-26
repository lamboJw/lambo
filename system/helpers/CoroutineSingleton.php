<?php
/**
 * 协程单例模式插件
 * 服务器类型为 CO_HTTP_SERVER 时使用该插件的类需要定义一个静态变量 $class_key 用来标识该单例类
 * CO_HTTP_SERVER 时通过Coroutine::getContext()实现协程隔离，最后要调用destroy销毁
 * SWOOLE_HTTP_SERVER 时使用静态变量存储，最后不需要destroy，会自动销毁
 * 一般不需要使用该插件，用app()->singleton_class('\namespace\class');即可
 */

namespace system\helpers;


use Swoole\Coroutine;

trait CoroutineSingleton
{
    static array $instance;

    static function getInstance(...$args)
    {
        if (config('app.server_type') == CO_HTTP_SERVER) {
            $context = Coroutine::getContext();
            if (!isset($context[self::$class_key])) {
                $context[self::$class_key] = new static(...$args);
            }
            return $context[self::$class_key];
        } else {
            $cid = Coroutine::getCid();
            if (!isset(self::$instance[$cid])) {
                self::$instance[$cid] = new static(...$args);
                if ($cid > 0) {
                    Coroutine::defer(function () use ($cid) {
                        unset(self::$instance[$cid]);
                    });
                }
            }
            return self::$instance[$cid];
        }
    }

    static function isInstantiated(){
        if (config('app.server_type') == CO_HTTP_SERVER) {
            $context = Coroutine::getContext();
            return isset($context[self::$class_key]);
        }else{
            $cid = Coroutine::getCid();
            return isset(self::$instance[$cid]);
        }
    }

    static function destroy()
    {
        if (config('app.server_type') == CO_HTTP_SERVER) {
            $context = Coroutine::getContext();
            unset($context[self::$class_key]);
        } else {
            $cid = Coroutine::getCid();
            unset(self::$instance[$cid]);
        }
    }
}