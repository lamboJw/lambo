<?php

namespace system\kernel;

use Swoole\Coroutine;

class Response
{
    private \Swoole\Http\Response $response;

    /**
     * 响应已结束标志
     * @var bool
     */
    private bool $is_end = false;

    public function __construct(\Swoole\Http\Response $response)
    {
        $this->response = $response;
    }

    /**
     * 判断是否已经结束响应
     * @return mixed
     */
    private function is_end()
    {
        if ($this->is_end) {
            debug('NOTICE', 'Response不可用，也许它已经end()或detach()');
//            debug('NOTICE', Coroutine::getBackTrace(Coroutine::getCid(), DEBUG_BACKTRACE_IGNORE_ARGS));
        }
        return $this->is_end;
    }

    /**
     * 返回json格式内容
     * @param $data
     * @return null
     */
    public function json($data)
    {
        if ($this->is_end()) {
            return null;
        }
        $this->write(json_encode($data));
    }

    private function end()
    {
        if ($this->is_end()) {
            return null;
        }
        $this->is_end = true;
        $this->response->end();
    }

    public function status($statusCode)
    {
        if ($this->is_end()) {
            return null;
        }
        $this->response->status($statusCode);
    }

    public function sendfile($filename, $offset = null, $length = null)
    {
        if ($this->is_end()) {
            return null;
        }
        $this->response->sendfile($filename, $offset, $length);
    }

    public function redirect($location, $http_code = null)
    {
        if ($this->is_end()) {
            return null;
        }
        $this->is_end = true;
        $this->response->redirect($location, $http_code);
    }

    public function write($content)
    {
        if ($this->is_end()) {
            return null;
        }
        $this->response->write($content);
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
        if (!$this->is_end()) {
            if (!empty($output)) {
                $this->response->write($output);
            }
            $this->end();
        } else {
            debug('INFO', $output);
        }
    }

    public function cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)
    {
        if ($this->is_end()) {
            return null;
        }
        $this->response->cookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority);
    }

    public function header($key, $value, $ucwords = null)
    {
        if ($this->is_end()) {
            return null;
        }
        $this->response->header($key, $value, $ucwords);
    }
}