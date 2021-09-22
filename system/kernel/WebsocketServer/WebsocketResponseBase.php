<?php


namespace system\kernel\WebsocketServer;


use Swoole\WebSocket\Frame;

abstract class WebsocketResponseBase
{
    /**
     * @var Frame|bool|string
     */
    public $frame;

    public string $fd;

    abstract function push($fd, $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool;

    abstract function exists($fd): bool;

    abstract function disconnect($fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool;

    abstract function isEstablished($fd): bool;

    abstract function set_frame($frame);

    abstract function broadcast($data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN);

    abstract function connection_count(): int;
}