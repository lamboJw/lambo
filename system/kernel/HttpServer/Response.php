<?php

namespace system\kernel\HttpServer;

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

    /**
     * 设置cookie(会对$value进行urlencode编码)
     * @param string $name 名称
     * @param string $value 内容
     * @param int $expires 过期时间时间戳
     * @param string $path cookie可用的路径
     * @param string $domain cookie可用的域
     * @param bool $secure 是否只允许用https传输
     * @param bool $httponly 是否只允许通过http协议，设置为true时，不允许脚本语言访问，能有效防止XSS攻击。
     * @param string $samesite 限制第三方Cookie发送，Strict：仅允许发送同站点请求的cookie；Lax：除a标签、GET表单、预加载请求外，都禁止；None：不禁止，不过需要设置$secure=true
     * @param string $priority 优先级，chrome的提案，定义了三种优先级，Low/Medium/High，当cookie数量超出时，低优先级的cookie会被优先清除
     */
    public function cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)
    {
        $this->response->cookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority);
    }

    /**
     * 与cookie参数一样，只不过不会对$value进行urlencode编码
     */
    public function rawCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)
    {
        $this->response->rawcookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority);
    }

    public function header($key, $value, $format = null)
    {
        $this->response->header($key, $value, $format);
    }
}