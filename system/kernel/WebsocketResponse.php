<?php

namespace system\kernel;

class WebsocketResponse
{
    private \Swoole\Http\Response $response;
    private static array $pool = [];
    private $res_id;

    public function __construct(\Swoole\Http\Response $response)
    {
        $this->res_id = spl_object_id($response);
        self::$pool[$this->res_id] = $response;
        $this->response = $response;
    }

    public function get_pool()
    {
        return self::$pool;
    }

    public function upgrade()
    {
        return $this->response->upgrade();
    }

    public function recv()
    {
        $frame = $this->response->recv();
        if ($frame === '') {
            debug('INFO', '连接关闭');
            unset(self::$pool[$this->res_id]);
            $this->response->close();
            return false;
        } else if ($frame === false) {
            debug('INFO', 'websocket错误 : ' . swoole_last_error());
            return false;
        } elseif ($frame->data == config('swoole', 'websocket')['close_command'] ?? 'close' || get_class($frame) === \Swoole\WebSocket\CloseFrame::class) {
            debug('INFO', '客户端发出关闭指令');
            unset(self::$pool[$this->res_id]);
            $this->response->close();
            return false;
        } else {
            return $frame;
        }
    }

    /**
     * @param string|object $data 发送数据帧（类型为Frame时，忽略后面两个参数）
     * @param int $opcode 数据帧类型，WEBSOCKET_OPCODE_TEXT（文本内容） 或 WEBSOCKET_OPCODE_BINARY（二进制内容）
     * @param bool $flags
     * @return mixed
     */
    public function push($data, $opcode = WEBSOCKET_OPCODE_TEXT, $flags = true)
    {
        return $this->response->push($data, $opcode, $flags);
    }

    public function close()
    {
        return $this->response->close();
    }
}