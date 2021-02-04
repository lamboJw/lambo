<?php


namespace system\kernel\WebsocketServer;

use Swoole\WebSocket\Server;

class SwooleWebsocketResponse extends WebsocketResponseBase
{
    public $frame;

    public $fd;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    function push($fd, $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool
    {
        return $this->server->push($fd, $data, $opcode, $flag);
    }

    function exists($fd): bool
    {
        return $this->server->exists($fd);
    }

    function disconnect($fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool
    {
        return $this->server->disconnect($fd, $code, $reason);
    }

    function isEstablished($fd): bool
    {
        return $this->server->isEstablished($fd);
    }

    function set_frame($frame)
    {
        $this->frame = $frame;
    }

    function broadcast($data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN)
    {
        foreach ($this->server->connections as $fd) {
            if ($fd != $this->fd && $this->isEstablished($fd)) {
                $this->push($fd, $data, $opcode, $flag);
            }
        }
    }

    function set_fd($fd)
    {
        $this->fd = $fd;
    }

    function connection_count(): int
    {
        return count($this->server->connections);
    }
}