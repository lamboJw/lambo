<?php


namespace system\kernel\WebsocketServer;

use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Table;
use Swoole\WebSocket\CloseFrame;
use system\kernel\BaseRedis;
use system\kernel\HttpServer\CoHttpServer;

class CoWebsocketResponse extends WebsocketResponseBase
{
    public $frame;
    private Response $ws;
    private Request $request;
    private Table $connections;
    private Server $server;
    public $fd;   //客户端唯一标识key
    private bool $use_broadcast = false;

    public function __construct(Request $request, Response $ws, Table $connections)
    {
        $this->request = $request;
        $this->ws = $ws;
        $this->fd = CoHttpServer::$pid . '_' . spl_object_id($ws);
        $this->connections = $connections;
        $this->connections->set($this->fd, ['is_upgraded' => 0]);
        $this->use_broadcast = (bool)config('swoole.http.co_ws_broadcast', false);
    }

    public function set_frame($frame)
    {
        $this->frame = $frame;
        if (empty($frame->fd)) {
            $this->connections->del($this->fd);
        }
    }

    function push($fd, $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool
    {
        if ($fd != $this->fd) {
            if (!$this->use_broadcast) {
                return false;
            }
            $redis = new BaseRedis();
            $func = 'push';
            $data = serialize($data);
            $redis->publish('websocket_broadcast', json_encode(compact('func', 'fd', 'data', 'opcode', 'flag')));
            return true;
        } else {
            return $this->ws->push($data, $opcode, $flag);
        }
    }

    function exists($fd): bool
    {
        return $this->connections->exist($fd);
    }

    function disconnect($fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool
    {
        if ($fd != $this->fd) {
            if (!$this->use_broadcast) {
                return false;
            }
            $redis = new BaseRedis();
            $func = 'disconnect';
            $redis->publish('websocket_broadcast', json_encode(compact('func', 'fd', 'code', 'reason')));
            return true;
        } else {
            $close_frame = new CloseFrame();
            $close_frame->reason = $reason;
            $close_frame->code = $code;
            $close_frame->finish = 1;
            return $this->ws->push($close_frame);
        }
    }

    function isEstablished($fd): bool
    {
        $re = $this->connections->get($fd, 'is_upgraded');
        if ($re === false) {
            return false;
        }
        return (bool)$re;
    }

    function upgrade()
    {
        if ($this->ws->upgrade()) {
            $this->connections->set($this->fd, ['is_upgraded' => 1]);
        }
    }

    function broadcast($data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool
    {
        if (!$this->use_broadcast) {
            return false;
        }
        $redis = new BaseRedis();
        $data = serialize($data);
        $func = 'broadcast';
        $fd = $this->fd;
        $redis->publish('websocket_broadcast', json_encode(compact('func', 'fd', 'data', 'opcode', 'flag')));
        return true;
    }

    function connection_count(): int
    {
        return $this->connections->count();
    }
}