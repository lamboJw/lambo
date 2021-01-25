<?php

namespace system\kernel;

use Swoole\Coroutine;
use system\helpers\CoroutineSingleton;


class Application
{
    public static string $class_key = 'app';

    use CoroutineSingleton;

    /**
     * 用于存储会话上下文
     */
    private $context;

    private function __construct($request, $response)
    {
        if (server_type() == CO_HTTP_SERVER) {
            $this->context = Coroutine::getContext();
        } else {
            $this->context = [];
        }
        $this->context['ob_count'] = 0;     //缓冲区计数
        $this->set_request($request);
        $this->set_response($response);
        if(config('swoole', 'websocket')['open_websocket'] ?? false){
            $this->set_websocket_response($response);
        }
    }

    private function set_request($request)
    {
        $this->context['request'] = new Request($request);
    }

    public function request(): Request
    {
        return $this->context['request'];
    }

    private function set_response($response)
    {
        $this->context['response'] = new Response($response);
    }

    public function response(): Response
    {
        return $this->context['response'];
    }

    private function set_websocket_response($response)
    {
        $this->context['websocket_response'] = new WebsocketResponse($response);
    }

    public function ws_response(): WebsocketResponse
    {
        return $this->context['websocket_response'];
    }

    /**
     * 获取一个单例的类
     * @param string $class
     * @param mixed ...$params
     * @return mixed
     */
    public function singleton_class(string $class, ...$params)
    {
        if (!isset($this->context['singleton_classes'][$class])) {
            $this->context['singleton_classes'][$class] = new $class(...$params);
        }
        return $this->context['singleton_classes'][$class];
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
        $this->context['ob_count']++;
        ob_start();
    }

    public function ob_get_clean()
    {
        if ($this->context['ob_count'] > 0) {
            $this->context['ob_count']--;
        }
        return ob_get_clean();
    }

    public function ob_clean_all()
    {
        if ($this->context['ob_count'] > 0) {
            log_message('NOTICE', "有未闭合缓冲区{$this->context['ob_count']}个");
            for ($i = $this->context['ob_count']; $i > 0; $i--) {
                $this->context['ob_count']--;
                config('app', 'debug') ? ob_end_flush() : ob_end_clean();
            }
        }
    }

    public function ob_get_all()
    {
        $all = [];
        if ($this->context['ob_count'] > 0) {
            for ($i = $this->context['ob_count']; $i > 0; $i--) {
                $all[$this->context['ob_count']] = $this->ob_get_clean();
            }
        }
        return $all;
    }

    public function ob_restore_all($all)
    {
        if ($this->context['ob_count'] == 0) {
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