<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<h1>ws_测试</h1>
<script>
    var wsUrl = "ws://192.168.130.235:10086/websocket";
    //这里加上服务端的配置
    var websocket = new WebSocket(wsUrl);
    //实例对象的onopen树形
    websocket.onopen = function (evt) {
        websocket.send(new Date().getTime()+"  hello-swoole");
        console.log("content_swoole_success");
    }
    //实例话 onmessage
    websocket.onmessage = function (evt) {
        console.log(evt);
    }
    //onclose
    websocket.onclose = function (evt) {
        console.log(evt);
        console.log("close");
        websocket.close();
    }
    websocket.onerror = function (evt) {
        console.log("error")
    }
</script>
</body>
</html>