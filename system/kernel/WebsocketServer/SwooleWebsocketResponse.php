<?php


namespace system\kernel\WebsocketServer;

use Swoole\WebSocket\Server;

class SwooleWebsocketResponse extends WebsocketResponseBase
{
    public $frame;

    public int $fd;

    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }
    function push(int $fd, $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool
    {
        return $this->server->push($fd, $data, $opcode, $flag);
    }

    function exists(int $fd): bool
    {
        return $this->server->exists($fd);
    }

    function disconnect(int $fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool
    {
        return $this->server->disconnect($fd, $code, $reason);
    }

    function isEstablished(int $fd): bool
    {
        return $this->server->isEstablished($fd);
    }

    function set_frame($frame)
    {
        $this->frame = $frame;
    }

    function broadcast(callable $func)
    {
        foreach ($this->server->connections as $fd) {
            $func($fd);
        }
    }

    function set_fd($fd)
    {
        $this->fd = $fd;
    }
}