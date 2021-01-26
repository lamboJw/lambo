<?php


namespace system\kernel\HttpServer;

use Co\Http\Server;
use Co;
use Swoole\Process;

class CoHttpServer extends HttpServerBase
{
    protected int $cur_request = 0;
    protected int $max_request;
    protected int $pid;
    protected Process\Pool $pool;
    public function __construct($pool, $workerId)
    {
        parent::__construct();
        $this->pid = posix_getpid();
        $this->pool = $pool;
        // 没有在协程容器中
        if (Co::getPcid() == false) {
            throw new \RuntimeException('协程风格HTTP服务器不能运行在非协程容器内');
        }
        $this->server = new Server($this->http_config['host'] ?? '127.0.0.1', $this->http_config['port'] ?? '10086', $this->http_config['ssl'] ?? false, true);
        $this->max_request = $this->server_config['max_request'] ?? 1000;
    }

    public function onRequest($callback)
    {
        $this->server->handle('/', function ($request, $response) use ($callback) {
//            if($this->check_request($response)){
                $this->check_request($response);
                $callback($request, $response, $this->route_map);
//                $this->shutdown();
//            }
        });
    }

    public function wsOnMessage($callback)
    {
        $this->server->handle('/websocket', function ($request, $ws) use ($callback) {    //websocket服务器
            $callback($request, $ws);
        });
    }

    public function check_request($response){
        if($this->cur_request < $this->max_request){
            $this->cur_request++;
            echo "Worker:{$this->pid},request:{$this->cur_request}\n";
//            return true;
        }else{
            echo "Worker:{$this->pid},request:full\n";
            $process = $this->pool->getProcess();
            $process->kill($this->pid, SIGTERM);
            /*$response->status(500);
            $response->end();
            return false;*/
        }
    }

    public function shutdown()
    {
        if($this->cur_request >= $this->max_request){
            echo "Worker:{$this->pid},server shutdown\n";
            $this->server->shutdown();
        }
    }
}