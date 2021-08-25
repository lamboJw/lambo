<?php
/**
 * 服务器
 */

namespace system\kernel;

use Swoole\Process\Pool;
use Swoole\Process;
use Swoole\Table;
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
        $connections = null;
        if (config('swoole.http.open_websocket')) {
            $connections = new Table(config('swoole.http.co_ws_pool_size', 1024));
            $connections->column('is_upgraded', Table::TYPE_INT);
            $connections->create();
        }
        $pool = new Pool($workerNum, 0, null, true);
        $pool->on('WorkerStart', function ($pool, $workerId) use ($connections) {
            $server = new CoHttpServer($pool, $connections);
            Process::signal(SIGTERM, function () use ($server) {
                $server->shutdown();
            });
            $server->start();
        });
        debug('info', "协程http服务器启动");
        $pool->start();
    }

    /**
     * 异步风格
     */
    private function swoole_http_server()
    {
        $server = new SwooleHttpServer();
        debug('info', "异步http服务器启动");
        $server->start();
    }
}