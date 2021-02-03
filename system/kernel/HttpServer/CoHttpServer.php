<?php


namespace system\kernel\HttpServer;

use Co\Http\Server;
use Co;
use Swoole\ExitException;
use Swoole\Process;
use Swoole\Table;
use Swoole\WebSocket\Frame;
use system\kernel\Application;
use system\kernel\BaseRedis;
use system\kernel\WebsocketServer\CoWebsocketResponse;
use Throwable;

class CoHttpServer extends HttpServerBase
{
    protected int $cur_request = 0;
    protected int $max_request = 0;
    public static int $pid;
    protected Process\Pool $pool;
    protected Table $connections;

    public function __construct($pool, $connections)
    {
        parent::__construct();
        self::$pid = posix_getpid();
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
            if (empty($ws_service)) {
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
            $process->kill(self::$pid, SIGTERM);
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
        $this->check_alive();
        $this->server->handle('/websocket', function (\Swoole\Http\Request $request, \Swoole\Http\Response $ws) {    //websocket服务器
            app()->set_websocket_response(CoWebsocketResponse::class, $request, $ws, $this->connections);
            $ws_resp = ws_response();
            $this->subscribe();
            $ws_resp->upgrade();
            $this->ws_service->onOpen();
            while (true) {
                $frame = $ws->recv();
                $ws_resp->set_frame($frame);
                if ($ws_resp->frame === '') {
                    debug('INFO', $ws_resp->fd . ' 确认客户端关闭，连接关闭');
                    $this->connections->del($ws_resp->fd);
                    $ws->close();
                    break;
                } else if ($ws_resp->frame === false) {
                    $error_no = swoole_last_error();
                    debug('INFO', $ws_resp->fd . ' websocket错误 : ' . $error_no);
                    $this->connections->del($ws_resp->fd);
                    $ws->close();
                    break;
                } elseif ($ws_resp->frame->data == config('swoole.websocket.close_command', 'close')) {
                    debug('INFO', "客户端fd#" . $ws_resp->fd . " 发出关闭指令");
                    $ws_resp->disconnect($ws_resp->fd, WEBSOCKET_CLOSE_NORMAL, '关闭指令连接关闭');
                } elseif (get_class($ws_resp->frame) === \Swoole\WebSocket\CloseFrame::class) {
                    $this->ws_service->onClose();
                    $this->connections->del($ws_resp->fd);
                    $ws->close();
                    break;
                } elseif ($ws_resp->frame->opcode == WEBSOCKET_OPCODE_PING || $ws_resp->frame->data == 'ping') {
                    $pong = new Frame();
                    $pong->opcode = WEBSOCKET_OPCODE_PONG;
                    $ws_resp->push($ws_resp->fd, $pong);
                } else {
                    $this->ws_service->onMessage();
                }
            }
            Application::destroy();
            return;
        });
    }

    private function check_alive(){
        go(function (){
            $redis = new BaseRedis();
            while (true){
                $redis->publish('websocket_broadcast', json_encode(['func'=>'check_alive']));
                Co::sleep(5);
            }
        });
    }

    private function subscribe(){
        go(function (){
            try{
                $redis = new BaseRedis();
                $redis->subscribe(['websocket_broadcast'], function (\Redis $redis, $chan, $msg)
                {
                    $context = Co::getContext(Co::getPcid());
                    if(!isset($context[Application::$class_key])){
                        exit();
                    }
                    $ws = $context[Application::$class_key]->ws_response();
                    if(!$this->connections->exist($ws->fd)){
                        exit();
                    }
                    $msg = json_decode($msg, true);
                    if ($msg['func'] == 'disconnect' && $msg['fd'] == $ws->fd) {
                        $ws->disconnect($msg['fd'], $msg['code'], $msg['reason']);
                        exit();
                    }
                    if ($msg['func'] == 'broadcast' && $msg['fd'] != $ws->fd) {
                        $msg['data'] = unserialize($msg['data']);
                        $ws->push($ws->fd, $msg['data'], $msg['opcode'], $msg['flag']);
                    }
                });
            }catch (throwable $e){
                if(!$e instanceof ExitException){
                    debug('error', 'websocket订阅子协程错误：'.$e->getMessage());
                }else{
                    var_dump('websocket订阅子协程退出');
                }
            }
        });
    }
}