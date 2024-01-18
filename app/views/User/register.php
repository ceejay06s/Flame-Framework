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
    var host = 'ws://localhost:20000/WebSocket.php';
    var socket = new WebSocket(host);
    socket.onmessage = function(e) {
        console.log(e);
        document.getElementById('root').innerHTML = e.data;
    };


    function send() {
        socket.send("test1234");
    }
</script>

</html>