<?php


namespace system\kernel\WebsocketServer;


use Swoole\WebSocket\Frame;

abstract class WebsocketResponseBase
{
    abstract function push(int $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool;

    abstract function exists(int $fd): bool;

    abstract function disconnect(int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool;

    abstract function isEstablished(int $fd): bool;

    abstract function set_frame(Frame $frame);

    abstract function frame(): Frame;
}