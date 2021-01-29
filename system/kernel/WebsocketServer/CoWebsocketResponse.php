<?php


namespace system\kernel\WebsocketServer;

use Swoole\Coroutine\Http\Server;
use Swoole\Table;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;

class CoWebsocketResponse extends WebsocketResponseBase
{
    public $frame;
    protected Response $ws;
    protected Request $request;
    public Table $connections;
    protected Server $server;
    public int $fd;

    public function __construct(Request $request, Response $ws, Table $connections, Server $server)
    {
        $this->request = $request;
        $this->ws = $ws;
        $this->connections = $connections;
        $this->server = $server;
        $this->connections->set($request->fd, ['is_upgraded' => 0]);
        $this->fd = $request->fd;
    }

    public function set_frame($frame)
    {
        $this->frame = $frame;
        $this->fd = $frame->fd;
    }

    function push(int $fd, $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool
    {
        if($fd == $this->fd){
            return $this->ws->push($data, $opcode, $flag);
        }else{
            if(!$this->isEstablished($fd)){
                return false;
            }
            $this->ws->socket->fd = $fd;
            $re = $this->ws->push($data, $opcode, $flag);
            $this->ws->socket->fd = $this->ws->fd;
            return $re;
        }
    }

    function exists(int $fd): bool
    {
        return $this->connections->exist($fd);
    }

    function disconnect(int $fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool
    {
        $close_frame = new CloseFrame();
        $close_frame->reason = $reason;
        $close_frame->code = $code;
        $re = $this->ws->push($close_frame);
        $this->connections->del($this->fd);
        return $re;
    }

    function isEstablished(int $fd): bool
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

    function broadcast(callable $func)
    {
        foreach ($this->connections as $fd => $v) {
            $func($fd);
        }
    }
}