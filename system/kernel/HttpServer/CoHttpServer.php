<?php


namespace system\kernel\HttpServer;

use Co\Http\Server;
use Co;
use Swoole\Process;
use Swoole\Table;
use system\kernel\Application;
use system\kernel\WebsocketServer\CoWebsocketResponse;

class CoHttpServer extends HttpServerBase
{
    protected int $cur_request = 0;
    protected int $max_request = 0;
    protected int $pid;
    protected Process\Pool $pool;
    protected Table $connections;

    public function __construct($pool, $connections)
    {
        parent::__construct();
        $this->pid = posix_getpid();
        $this->pool = $pool;
        // 没有在协程容器中
        if (Co::getPcid() == false) {
            throw new \RuntimeException('协程风格HTTP服务器不能运行在非协程容器内');
        }
        $this->server = new Server($this->http_config['host'], $this->http_config['port'], $this->http_config['ssl'], true);
        $this->max_request = $this->server_config['max_request'];
        $this->onRequest();
        if (config('swoole.http.open_websocket', false)) {
            $ws_service = config('swoole.http.websocket_service');
            if(empty($ws_service)){
                throw new \RuntimeException('请配置swoole.http.websocket_service项');
            }
            $this->ws_service = new $ws_service();
            $this->connections = $connections;
            $this->websocket();
        }
    }

    /**
     * 接收请求
     */
    protected function onRequest()
    {
        $this->handle_static();
        $this->server->handle('/', function ($request, $response) {
            if ($this->max_request > 0) {
                $this->check_request();
            }
            $this->http_server_callback($request, $response, $this->route_map);
        });
    }

    /**
     * 到达max_request后，重启进程
     */
    private function check_request()
    {
        if ($this->cur_request < $this->max_request) {
            $this->cur_request++;
        } else {
            $process = $this->pool->getProcess();
            $process->kill($this->pid, SIGTERM);
        }
    }

    /**
     * 关闭服务器
     */
    public function shutdown()
    {
        $this->server->shutdown();
    }

    /**
     * 静态文件处理
     */
    private function handle_static()
    {
        if (!$this->server_config['enable_static_handler']) return;
        foreach ($this->server_config['static_handler_locations'] as $location) {
            $this->server->handle($location, function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
                $file_path = $this->server_config['document_root'] . $request->server['path_info'];
                if (file_exists($file_path)) {
                    $response->sendfile($file_path);
                } else {
                    $response->status(404);
                    $response->end('<h1>File Doesn\'t Exists</h1>');
                }
            });
        }
    }

    /**
     * websocket服务
     */
    private function websocket()
    {
        $this->server->handle('/websocket', function (\Swoole\Http\Request $request, \Swoole\Http\Response $ws) {    //websocket服务器
            app()->set_websocket_response(CoWebsocketResponse::class , $request, $ws, $this->connections, $this->server);
            $ws_resp = ws_response();
            $ws_resp->upgrade();
            $this->ws_service->onOpen();
            while (true) {
                $frame = $ws->recv();
                $ws_resp->set_frame($frame);
                if ($ws_resp->frame === '') {
                    debug('INFO', '连接关闭');
                    $this->ws_service->onClose();
                    $ws_resp->disconnect(WEBSOCKET_CLOSE_NORMAL, '连接关闭');
                    break;
                } else if ($ws_resp->frame === false) {
                    debug('INFO', 'websocket错误 : ' . swoole_last_error());
                    $this->ws_service->onClose();
                    $ws_resp->disconnect(WEBSOCKET_CLOSE_DATA_ERROR, '接收消息失败，错误代码：'.swoole_last_error());
                    break;
                } elseif ($ws_resp->frame->data == config('swoole.websocket.close_command', 'close') || get_class($ws_resp->frame) === \Swoole\WebSocket\CloseFrame::class) {
                    debug('INFO', "客户端fd#".$ws_resp->frame->fd." 发出关闭指令");
                    $this->ws_service->onClose();
                    $ws_resp->disconnect(WEBSOCKET_CLOSE_NORMAL, '连接关闭');
                    break;
                } else {
                    $this->ws_service->onMessage();
//                    $ws_resp->disconnect(WEBSOCKET_CLOSE_NORMAL, '正常退出');
                }
            }
            Application::destroy();
        });
    }
}