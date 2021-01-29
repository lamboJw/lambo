<?php


namespace system\kernel\WebsocketServer;


abstract class WebsocketResponseBase
{
    public $frame;

    public int $fd;

    abstract function push(int $fd, $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool;

    abstract function exists(int $fd): bool;

    abstract function disconnect(int $fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool;

    abstract function isEstablished(int $fd): bool;

    abstract function set_frame($frame);

    abstract function broadcast(callable $func);
}