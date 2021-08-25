<?php


namespace system\kernel\HttpServer;

use Swoole\Websocket\Server as ws_server;
use Swoole\Http\Server as http_server;
use system\kernel\WebsocketServer\SwooleWebsocketResponse;

class SwooleHttpServer extends HttpServerBase
{
    public function __construct()
    {
        parent::__construct();
        if (!empty($this->http_config['open_websocket'])) {
            $this->server = new ws_server($this->http_config['host'], $this->http_config['port'], $this->http_config['server_mode'], $this->http_config['socket_type']);
            $this->server->set($this->websocket_config);
            $this->websocket();
        } else {
            $this->server = new http_server($this->http_config['host'], $this->http_config['port'], $this->http_config['server_mode'], $this->http_config['socket_type']);
        }
        $this->server->set($this->server_config);
        $this->onRequest();
        $this->onWorkerStart();
    }

    protected function onRequest()
    {
        $this->server->on('request', function ($request, $response) {
            $this->http_server_callback($request, $response, $this->route_map);
        });
    }

    protected function onWorkerStart()
    {
        $this->server->on('WorkerStart', function ($server, $worker_id) {
            $this->auto_reload();
        });
    }

    protected function websocket()
    {
        $this->server->on('open', function (ws_server $server, $request) {
            app()->set_websocket_response(SwooleWebsocketResponse::class, $server);
            ws_response()->set_fd($request->fd);
            $this->ws_service->onOpen();
        });
        $this->server->on('message', function (ws_server $server, $frame) {
            app()->set_websocket_response(SwooleWebsocketResponse::class, $server);
            ws_response()->set_frame($frame);
            ws_response()->set_fd($frame->fd);
            if ($frame->data == config('swoole.websocket.close_command', 'close')) {
                $server->disconnect($frame->fd, WEBSOCKET_CLOSE_NORMAL, '关闭连接');
                return;
            }
            $this->ws_service->onMessage();
        });
        $this->server->on('close', function (ws_server $server, $fd) {
            app()->set_websocket_response(SwooleWebsocketResponse::class, $server);
            ws_response()->set_fd($fd);
            $this->ws_service->onClose();
        });
    }

    protected function reload()
    {
        $this->server->reload();
    }
}