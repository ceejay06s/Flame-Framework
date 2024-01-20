<?php


namespace Flame;


class WebSocket
{

    public $address = '0.0.0.0';
    public $port = 80800;

    public $server;
    public $args;
    public $client;
    private $read;
    private $write;
    function __construct()
    {
        global $argv;
        $skip = 0;
        foreach ($argv as $key => $value) {
            if ($key == $skip) continue;
            if (strstr($value, '--') || strstr($value, '-')) {

                $skip = $key + 1;
                $this->args[trim($value, '-')] = $argv[$skip];
            } elseif ($key == 1) {
                $this->args['address'] = $value;
            } elseif ($key == 2) {
                $this->args['port'] = $value;
            } else {
                $this->args[] = $value;
            }
        }

        //$this->__init__();
    }

    function __init__()
    {
        if (isset($this->args['address'])) $this->address = $this->args['address'];
        if (isset($this->args['port'])) $this->port = $this->args['port'];
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, SO_SNDBUF);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, SO_RCVBUF);
        socket_bind($this->server, $this->address, $this->port);
        socket_listen($this->server); //second parameter is number of connection
        echo "Server Initialized...\r\n
        ws://{$this->address}:{$this->port}\r\n
        Accepting new Client..." . PHP_EOL;
        $this->client = socket_accept($this->server);

        $this->handshake();
    }

    function handshake()
    {
        $request = socket_read($this->client, 5000);
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        $key = base64_encode(pack(
            'H*',
            sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
        socket_write($this->client, $headers, strlen($headers));

        while (true) {
            sleep(1);
            $content = 'Now: ' . time();
            $response = chr(129) . chr(strlen($content)) . $content;
            socket_write($this->client, $response);
            var_dump(
                stream_socket_recvfrom($this->server, 9, STREAM_PEEK)
            );
            // if ($read = socket_read($this->client, 50000)) {
            //     $test2 = pack("s", $read);
            //     var_dump("read", $read, $test2);
            // }
        }
    }
}

$socket = new WebSocket;
$socket->__init__();
