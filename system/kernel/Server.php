<?php
/**
 * 服务器
 */

namespace system\kernel;

use Swoole\Process\Pool;
use Swoole\Process;
use system\kernel\HttpServer\CoHttpServer;
use system\kernel\HttpServer\SwooleHttpServer;

class Server
{
    /**
     * 根据服务器类型启动http服务器
     */
    public function run_http_server()
    {
        switch (config('app.server_type')) {
            case CO_HTTP_SERVER:
            {
                $this->co_http_server();
                break;
            }
            case SWOOLE_HTTP_SERVER:
            {
                $this->swoole_http_server();
                break;
            }
            default:
                exit("错误的服务器类型\n");
        }
    }

    /**
     * 协程风格
     */
    private function co_http_server()
    {
        if (config('swoole.server.daemonize', false)) {
            Process::daemon();
        }
        $workerNum = config('swoole.server.worker_num', 1);
        $pool = new Pool($workerNum, 0, null, true);
        $pool->on('WorkerStart', function ($pool, $workerId) {
            $server = new CoHttpServer($pool);
            Process::signal(SIGTERM, function () use ($server) {
                $server->shutdown();
            });
            $server->start();
        });
        echo "协程http服务器启动\n";
        $pool->start();
    }

    /**
     * 异步风格
     */
    private function swoole_http_server()
    {
        $server = new SwooleHttpServer();
        echo "异步http服务器启动\n";
        $server->start();
    }
}