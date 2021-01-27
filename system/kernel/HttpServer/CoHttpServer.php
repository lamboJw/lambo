<?php


namespace system\kernel\HttpServer;

use Co\Http\Server;
use Co;
use Swoole\Process;

class CoHttpServer extends HttpServerBase
{
    protected int $cur_request = 0;
    protected int $max_request = 0;
    protected int $pid;
    protected Process\Pool $pool;

    public function __construct($pool = null)
    {
        parent::__construct();
        $this->pid = posix_getpid();
        if ($pool !== null) {
            $this->pool = $pool;
        }
        // 没有在协程容器中
        if (Co::getPcid() == false) {
            throw new \RuntimeException('协程风格HTTP服务器不能运行在非协程容器内');
        }
        $this->server = new Server(
            $this->http_config['host'] ?? '127.0.0.1',
            $this->http_config['port'] ?? '10086',
            $this->http_config['ssl'] ?? false,
            true
        );
        $this->max_request = $this->server_config['max_request'] ?? 0;
    }

    /**
     * 接收请求
     * @param callable $callback
     */
    public function onRequest($callback)
    {
        $this->handle_static();
        $this->server->handle('/', function ($request, $response) use ($callback) {
            if ($this->max_request > 0) {
                $this->check_request();
            }
            $callback($request, $response, $this->route_map);
        });
    }

    public function wsOnMessage($callback)
    {
        $this->server->handle('/websocket', function ($request, $ws) use ($callback) {    //websocket服务器
            $callback($request, $ws);
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
}