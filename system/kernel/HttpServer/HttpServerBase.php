<?php


namespace system\kernel\HttpServer;

use system\kernel\Router;

abstract class HttpServerBase
{
    protected $server;
    protected array $route_map;
    protected $http_config;
    protected $server_config;

    public function __construct()
    {
        $this->http_config = config('swoole.http', []);
        $this->server_config = config('swoole.server', []);
        $this->route_map = Router::load_routes();
    }

    abstract function onRequest(callable $callback);

    public function start()
    {
        $this->server->start();
    }
}