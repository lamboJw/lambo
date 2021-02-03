<?php

namespace system\kernel;

use Swoole\Coroutine;
use system\helpers\CoroutineSingleton;
use system\kernel\HttpServer\Request;
use system\kernel\HttpServer\Response;
use system\kernel\WebsocketServer\CoWebsocketResponse;
use system\kernel\WebsocketServer\SwooleWebsocketResponse;
use system\kernel\WebsocketServer\WebsocketResponseBase;


class Application
{
    public static string $class_key = 'app';

    use CoroutineSingleton;

    /**
     * @var array 用于存储会话上下文
     */
    private array $context = [];

    /**
     * @var int 缓冲区计数
     */
    private int $ob_count = 0;

    private function __construct()
    {

    }

    public function set_request($request)
    {
        if (isset($this->context['singleton_classes']['request'])) {
            return false;
        }
        $this->singleton('request', Request::class, $request);
    }

    public function request(): Request
    {
        return $this->singleton('request');
    }

    public function set_response($response)
    {
        if (isset($this->context['singleton_classes']['response'])) {
            return false;
        }
        $this->singleton('response', Response::class, $response);
    }

    public function response(): Response
    {
        return $this->singleton('response');
    }

    public function set_websocket_response(string $class, ...$params)
    {
        if (isset($this->context['singleton_classes']['websocket_response'])) {
            return false;
        }
        if (!in_array($class, [CoWebsocketResponse::class, SwooleWebsocketResponse::class])) {
            return false;
        }
        $this->singleton('websocket_response', $class, ...$params);
    }

    public function ws_response(): WebsocketResponseBase
    {
        return $this->singleton('websocket_response');
    }

    /**
     * 获取一个单例的类
     * @param string $key 类别名
     * @param string $class 类
     * @param mixed ...$params 构造函数参数
     * @return mixed
     */
    public function singleton(string $key, string $class = '', ...$params)
    {
        if (!isset($this->context['singleton_class'][$key])) {
            $this->context['singleton_class'][$key] = new $class(...$params);
        }
        return $this->context['singleton_class'][$key];
    }

    /**
     * 设置全局变量
     * @param string|array $key 变量名，可以传数组一次保存多个
     * @param mixed $value 变量值
     */
    public function set($key, $value = '')
    {
        if (is_array($key)) {
            $this->context['params'] = array_merge($this->context['params'], $key);
        } else {
            $this->context['params'][$key] = $value;
        }
    }

    /**
     * 获取全局变量
     * @param string|array $key 变量名，可以传数组一次获取多个
     * @return mixed|null
     */
    public function get($key)
    {
        if (is_array($key)) {
            $return = [];
            foreach ($key as $item) {
                $return[$item] = $this->context['params'][$item] ?? null;
            }
        } else {
            return $this->context['params'][$key] ?? null;
        }
    }

    /**
     * 以下方法用于输出标准输出到页面的辅助方法
     */
    public function ob_start()
    {
        $this->ob_count++;
        ob_start();
    }

    public function ob_get_clean()
    {
        if ($this->ob_count > 0) {
            $this->ob_count--;
        }
        return ob_get_clean();
    }

    public function ob_clean_all()
    {
        if ($this->ob_count > 0) {
            log_message('NOTICE', "有未闭合缓冲区{$this->ob_count}个");
            for ($i = $this->ob_count; $i > 0; $i--) {
                $this->ob_count--;
                config('app.debug') ? ob_end_flush() : ob_end_clean();
            }
        }
    }

    public function ob_get_all()
    {
        $all = [];
        if ($this->ob_count > 0) {
            for ($i = $this->ob_count; $i > 0; $i--) {
                $all[$this->ob_count] = $this->ob_get_clean();
            }
        }
        return $all;
    }

    public function ob_restore_all($all)
    {
        if ($this->ob_count == 0) {
            foreach ($all as $item) {
                $this->ob_start();
                echo $item;
            }
        }
    }
    /**
     * 以上方法用于输出标准输出到页面的辅助方法
     */
}