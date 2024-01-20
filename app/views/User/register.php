<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <div id="root"></div>
    <button onclick="send()">test</button>
</body>
<script>
    var socket = null;
    document.addEventListener('load', connect());

    function connect() {
        var host = 'ws://localhost:10000';
        socket = new WebSocket(host);
    }

    socket.addEventListener('message', function(e) {
        console.log(e);
        document.getElementById('root').innerHTML = e.data;
    });

    socket.close = function(e) {
        setTimeout(connect(), 1000);
    }

    function send() {
        socket.send("test1234");
    }
</script>

</html>