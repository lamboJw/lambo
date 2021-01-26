<?php


namespace system\kernel\HttpServer;

use Swoole\Http\Server;
use system\kernel\Router;

class SwooleHttpServer extends HttpServerBase
{
    public function __construct()
    {
        parent::__construct();
        $this->server = new Server(
            $this->http_config['host'] ?? '127.0.0.1',
            $this->http_config['port'] ?? '10086',
            $this->http_config['server_mode'] ?? SWOOLE_BASE,
            $this->http_config['socket_type'] ?? SWOOLE_SOCK_TCP
        );
        if (!empty($this->server_config)) {
            $this->server->set($this->server_config);
        }
    }

    public function onRequest(callable $callback)
    {
        $this->server->on('request', function ($request, $response) use ($callback) {
            $callback($request, $response, $this->route_map);
        });
    }
}