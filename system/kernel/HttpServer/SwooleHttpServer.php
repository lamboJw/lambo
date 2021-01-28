<?php


namespace system\kernel\HttpServer;

use app\websocket\WebsocketService;
use Swoole\Websocket\Server;

class SwooleHttpServer extends HttpServerBase
{
    public function __construct()
    {
        parent::__construct();
        $this->server = new Server($this->http_config['host'], $this->http_config['port'], $this->http_config['server_mode'], $this->http_config['socket_type']);
        $this->server->set($this->server_config);
        $this->onRequest();
        if (config('swoole.http.open_websocket', false)) {
            $this->ws_service = new WebsocketService();
            $this->websocket();
        }
    }

    protected function onRequest()
    {
        $this->server->on('request', function ($request, $response) {
            $this->http_server_callback($request, $response, $this->route_map);
        });
    }

    private function websocket()
    {
        $this->server->on('open', function (Server $server, $request) {
            $this->ws_service->onOpen();
        });
        $this->server->on('message', function (Server $server, $frame) {
            $this->ws_service->onMessage();
        });
        $this->server->on('close', function (Server $server, $fd) {
            $this->ws_service->onClose();
        });
    }
}