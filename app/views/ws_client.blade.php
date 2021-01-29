<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <script src="/static/common/js/jquery-3.5.1.min.js"></script>
</head>
<body>
<h1>ws_测试</h1>
<input id="i" type="text" value="" placeholder="输入发送的文字">
<script>
    let timer;
    var wsUrl = "ws://192.168.130.235:10086/websocket";
    //这里加上服务端的配置
    var websocket = new WebSocket(wsUrl);
    //实例对象的onopen树形
    websocket.onopen = function (evt) {
        console.log("content_swoole_success");
    }
    //实例话 onmessage
    websocket.onmessage = function (evt) {
    }
    //onclose
    websocket.onclose = function (evt) {
        console.log("close");
        clearInterval(timer);
        // websocket.close();
    }
    websocket.onerror = function (evt) {
        console.log("error")
    }

    timer = setInterval(function () {
        websocket.send('ping');
    }, 5000);

    $("#i").on('change', function () {
        websocket.send($(this).val());
    })
</script>
</body>
</html>