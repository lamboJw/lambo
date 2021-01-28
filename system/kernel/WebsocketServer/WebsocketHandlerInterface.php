<?php
/**
 * websocket服务器需要实现该接口
 */

namespace system\kernel\WebsocketServer;


interface WebsocketHandlerInterface
{
    function onOpen();

    function onMessage();

    function onClose();
}