<?php


namespace system\kernel\WebsocketServer;

use Swoole\Http\Response;
use Swoole\WebSocket\Frame;

class CoWebsocketResponse extends WebsocketResponseBase
{
    protected Response $ws;
    protected Frame $frame;

    public function __construct($request, $ws)
    {
        $this->ws = $ws;
    }

    public function set_frame(Frame $frame)
    {
        $this->frame = $frame;
    }

    public function frame(): Frame
    {
        return $this->frame;
    }

    function push(int $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool
    {
        $this->ws->push($data, $opcode, $flag);
    }

    function exists(int $fd): bool
    {
        // TODO: Implement exists() method.
    }

    function disconnect(int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool
    {
        $this->ws->push($reason, );
    }

    function isEstablished(int $fd): bool
    {
        // TODO: Implement isEstablished() method.
    }
}