<?php

namespace system\kernel;

use Swoole\Coroutine;

class Response
{
    private \Swoole\Http\Response $response;

    public function __construct(\Swoole\Http\Response $response)
    {
        $this->response = $response;
    }

    /**
     * 返回json格式内容
     * @param $data
     * @return null
     */
    public function json($data)
    {
        $this->write(json_encode($data));
    }

    public function end($content = '')
    {
        if (!empty($content)) {
            $this->response->end($content);
        } else {
            $this->response->end();
        }
        exit(SWOOLE_RESPONSE_EXIT); //响应完直接直接退出
    }

    public function status($statusCode)
    {
        $this->response->status($statusCode);
    }

    public function sendfile($filename, $offset = null, $length = null)
    {
        $this->response->sendfile($filename, $offset, $length);
    }

    public function redirect($location, $http_code = null)
    {
        $this->response->redirect($location, $http_code);
        exit(SWOOLE_RESPONSE_EXIT); //重定向后直接直接退出
    }

    public function write($content)
    {
        return $this->response->write($content);
    }

    /**
     * 输出标准输出到页面
     * @param callable $func
     */
    public function return(callable $func)
    {
        app()->ob_start();
        call_user_func($func);
        $output = app()->ob_get_clean();
        $this->end($output);
    }

    public function cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)
    {
        $this->response->cookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority);
    }

    public function rawCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)
    {
        $this->response->rawcookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority);
    }

    public function header($key, $value, $format = null)
    {
        $this->response->header($key, $value, $format);
    }
}