<?php
namespace app\websocket;

use \system\kernel\WebsocketServer\WebsocketHandlerInterface;

class WebsocketService implements WebsocketHandlerInterface
{

    function onOpen()
    {
        $ws = ws_response();
        $ws->push($ws->fd, 'self: '.$ws->fd);
    }

    function onMessage()
    {
        $ws = ws_response();
        $ws->broadcast(function ($fd) use ($ws){
            if($fd != $ws->fd){
                $ws->push($fd, "broadcast: {$fd}, data: {$ws->frame->data}");
            }
        });
    }

    function onClose()
    {

    }
}