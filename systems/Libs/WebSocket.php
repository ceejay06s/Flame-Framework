<?php


namespace Flame;

class WebSocket
{

    public $address = 0;
    public $port = 10000;

    public $server;
    public $args;
    public $client;
    private $_servers;

    public $data;
    public $message = null;
    public $type; // 0 or 1  Brodcast or personal

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
    }

    function __init__()
    {
        if (isset($this->args['address'])) $this->address = $this->args['address'];
        if (isset($this->args['port'])) $this->port = $this->args['port'];
    }

    function start()
    {
        ob_implicit_flush();
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $this->address, $this->port);
        socket_listen($this->server);
        echo "Server Initialized...\r\n";
        echo "ws://{$this->address}:{$this->port}\r\n";
        $this->_servers[(int) $this->server] = $this->server;
        while (true) {

            $read = $this->_servers;
            $write = $except = null;
            echo "Accepting new Client...\r\n";
            socket_select($read, $write, $except, 1);
            foreach ($read as $_servers) {
                if ($_servers == $this->server) {
                    $client = socket_accept($this->server);
                    if ($client < 0) {
                        var_dump("Failed: socket_accept()");
                        continue;
                    }
                    $this->_servers[(int)$client] = $client;
                    $this->handshake($client);
                    socket_getpeername($client, $ip);
                    echo "Accepted new Client IP: {$ip}...\r\n";
                    $resp = $this->ack($ip);
                    $this->brodcast($resp);
                } else {
                    $bytes = @socket_recv($_servers, $buffer, 2048, 0);
                    if ($bytes == 0 || $bytes == false) {
                        socket_getpeername($_servers, $ip);
                        $connection = $this->ack($ip, 2);
                        $this->brodcast($connection);
                        $index = array_search($_servers, $read);
                        unset($read[$index]);
                    } else {
                        $this->data = $this->unseal($buffer);

                        if (!empty($this->message)) {
                            switch ($this->type) {
                                case 0:
                                    $this->brodcast($this->message);
                                    break;
                                case 1:
                                    $this->send($_servers, $this->message);
                                    break;
                            }
                        }
                    }
                }
            }

            sleep(1);
        }
    }
    function proccess($read)
    {
        var_dump($read);
        // foreach ($this->client as $client) {
        //     var_dump($read);
        // }
        return;
    }
    function brodcast($message)
    {

        $messageLength = strlen($message);
        foreach ($this->_servers as $client) {
            @socket_write($client, $message, $messageLength);
        }
    }
    function send($client, $message)
    {
        // global $clientSocketArray;
        $messageLength = strlen($message);
        // foreach ($clientSocketArray as $clientSocket) {
        @socket_write($client, $message, $messageLength);
        //}
        return true;
    }
    function ack($ip, $type = 1)
    {
        $message = 'New client ' . $ip . ' joined';
        if ($type == 2)  $message = 'client ' . $ip . ' Disconneted';
        $messageArray = array('message' => $message, 'message_type' => 'ACK');
        $ACK = $this->seal(json_encode($messageArray));
        return $ACK;
    }
    function handshake($client)
    {

        $request = socket_read($client, 5000);
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
        socket_write($client, $headers, strlen($headers));
    }
    function unseal($socketData)
    {
        $length = ord($socketData[1]) & 127;
        if ($length == 126) {
            $masks = substr($socketData, 4, 4);
            $data = substr($socketData, 8);
        } elseif ($length == 127) {
            $masks = substr($socketData, 10, 4);
            $data = substr($socketData, 14);
        } else {
            $masks = substr($socketData, 2, 4);
            $data = substr($socketData, 6);
        }
        $socketData = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $socketData .= $data[$i] ^ $masks[$i % 4];
        }
        return $socketData;
    }
    function seal($socketData)
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($socketData);

        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCNN', $b1, 127, $length);
        return $header . $socketData;
    }
}

$socket = new WebSocket;
$socket->__init__();
$socket->start();
