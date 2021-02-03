<?php


namespace system\kernel\WebsocketServer;


abstract class WebsocketResponseBase
{
    public $frame;

    public $fd;

    abstract function push($fd, $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool;

    abstract function exists($fd): bool;

    abstract function disconnect($fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool;

    abstract function isEstablished($fd): bool;

    abstract function set_frame($frame);

    abstract function broadcast($data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN);
}