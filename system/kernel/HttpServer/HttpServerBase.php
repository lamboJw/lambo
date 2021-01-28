<?php


namespace system\kernel\HttpServer;

use system\kernel\Application;
use system\kernel\Router;
use system\kernel\WebsocketServer\WebsocketHandlerInterface;
use Throwable;

abstract class HttpServerBase
{
    protected $server;
    protected array $route_map;
    protected WebsocketHandlerInterface $ws_service;

    protected array $http_config = [
        'host' => '0.0.0.0',
        'port' => '10086',
        'ssl' => false,
        'socket_type' => SWOOLE_SOCK_TCP,
        'server_mode' => SWOOLE_BASE,
    ];

    protected array $server_config = [
        'worker_num' => 4,
        'reactor_num' => 4,
        'max_request' => 0,
        'max_connection' => 10000,
        'http_compression' => true,
        'http_compression_level' => 2,
        'dispatch_mode' => 3,
        'enable_static_handler' => true,
        'document_root' => ROOT_PATH,
        'static_handler_locations' => ['/' . STATIC_NAME],
        'upload_tmp_dir' => STATIC_PATH . '/uploads/',
        'open_http2_protocol' => false,
        'daemonize' => false,
        'log_file' => STATIC_PATH . '/logs/server.log',
        'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,
        'log_date_format' => '%Y-%m-%d %H:%M:%S',
        'enable_coroutine' => true,
        'hook_flags' => SWOOLE_HOOK_ALL,
        'ssl_cert_file' => '',
        'ssl_key_file' => '',
        'ssl_protocols' => 0,
    ];

    public function __construct()
    {
        $this->http_config = array_replace_recursive($this->http_config, config('swoole.http', []));
        $this->server_config = array_replace_recursive($this->server_config, config('swoole.server', []));
        $this->route_map = Router::load_routes();
    }

    abstract protected function onRequest();

    public function start()
    {
        $this->server->start();
    }

    //HTTP服务器处理请求函数
    protected function http_server_callback(\Swoole\Http\Request $request, \Swoole\Http\Response $response, $route_map)
    {
        //处理chrome请求favicon.ico
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->sendfile(STATIC_PATH . '/common/images/favicon.ico');
            return;
        }
        app()->set_request($request);
        app()->set_response($response);
        try {
            if (array_key_exists($request->server['request_uri'], $route_map)) {
                $route = $route_map[$request->server['request_uri']];
                foreach ($route['middleware'] as $middleware) {
                    $mid_result = (new $middleware())->handle();
                    if ($mid_result !== true) {
                        response()->end($mid_result);
                    }
                }
                if (config('app.std_output_to_page')) {
                    //输出标准输出到页面时的写法
                    response()->return(function () use ($route) {
                        $class = new $route['class']();
                        call_user_func([$class, $route['func']]);
                    });
                } else {
                    //标准输出到控制台的写法
                    $class = new $route['class']();
                    call_user_func([$class, $route['func']]);
                    response()->end();
                }
            } else {
                response()->status(404);
                response()->end('<h1>Page Not Found</h1>');
            }
        } catch (Throwable $e) {
            if ($e instanceof \Swoole\ExitException && $e->getStatus() == SWOOLE_RESPONSE_EXIT) {
                //如果是响应退出，且开启了标准输出到页面，因为有一个ob_start未闭合，所以要执行一次
                if (config('app.std_output_to_page')) {
                    app()->ob_get_clean();
                }
            } else {
                debug('ERROR', '捕获错误：' . swoole_last_error() . '， 错误信息：' . $e->getMessage());
                $response->status(500);
                if (config('app.debug')) {
                    $response->end(json_encode(['msg' => $e->getMessage(), 'request' => $request]));
                } else {
                    $response->end('<h1>500 服务器错误</h1>');
                }
            }
        }
        if (config('app.std_output_to_page')) {
            app()->ob_clean_all();    //使用标准输出到页面时，需要清除缓冲区
        }
        Application::destroy();
        return;
    }
}