<?php

namespace Flame;

class WebSocket
{
    public $address = '0.0.0.0';
    public $port = 10000;

    public $server;
    public $args;
    public $client;
    private $_servers;
    private $read;

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
        $timestamp = date('Y-m-d H:i:s');
        echo "[$timestamp] SERVER > Initializing, Please Wait...\r\n";
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $this->address, $this->port);
        socket_listen($this->server);
        echo "[$timestamp] SERVER > Server Initialized...\r\n";
        echo "[$timestamp] SERVER > Server Address : ws://{$this->address}:{$this->port}\r\n";
        $this->_servers[(int) $this->server] = $this->server;
        while (true) {

            sleep(1);
            $read = $this->_servers;
            $write = $except = null;
            echo "[$timestamp] SERVER > Accepting new Client...\r\n";
            socket_select($read, $write, $except, 0, 10);
            foreach ($read as $_servers) {
                if ($_servers == $this->server) {
                    $this->client = socket_accept($this->server);
                    if ($this->client < 0) {
                        echo "[$timestamp] SERVER > Failed: socket_accept() \r\n";
                        continue;
                    }
                    $this->_servers[(int)$this->client] = $this->client;
                    $this->handshake($this->client);
                    socket_getpeername($this->client, $ip);
                    echo "[$timestamp] SERVER > Accepted new Client with IP: {$ip}...\r\n";
                    $resp = $this->ack($ip);
                    $this->brodcast($resp);
                } else {

                    ($bytes = @socket_recv($_servers, $buffer, 50000, 0));
                    if (in_array($bytes, [0, 8])) {
                        socket_getpeername($_servers, $ip);
                        $connection = $this->ack($ip, 2);
                        $this->brodcast($connection);
                        $index = array_search($_servers, $read);
                        unset($read[$index]);
                        socket_close($_servers);
                    } else {
                        $this->proccess($buffer, $_servers);
                        $this->onMessage();
                    }
                }
            }
        }
    }
    function proccess($buffer, $_servers = null)
    {
        $timestamp = date('Y-m-d H:i:s');
        $this->read = (null !== func_get_arg(0)) ? func_get_arg(0) : $this->client;

        $this->data = $this->unmask($buffer);
        if ($data = json_decode($this->data)) {
            $this->data = $data;
        }
        $this->onReceive($this->read);
        echo "[$timestamp] CLIENT > " . print_r($this->data, true) . "\r\n";
    }

    function onReceive()
    {
        $this->read = (null !== func_get_arg(0)) ? func_get_arg(0) : $this->client;
        return $this->data;
    }

    function onMessage()
    {
        $this->read = (null !== func_get_arg(0)) ? func_get_arg(0) : $this->client;
        if (!empty($this->message)) {
            $this->message = $this->mask($this->message);
            switch ($this->type) {
                case 0:
                    $this->brodcast($this->message);
                    break;
                case 1:
                    $this->send($this->read, $this->message);
                    break;
                default:
                    $this->brodcast($this->message);
            }
        }
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
        $messageLength = strlen($message);
        @socket_write($client, $message, $messageLength);
        return true;
    }
    function ack($ip, $type = 1)
    {
        $message = 'New client ' . $ip . ' joined';
        if ($type == 2)  $message = 'client ' . $ip . ' Disconneted';
        $messageArray = array('message' => $message, 'message_type' => 'ACK');
        $ACK = $this->mask(json_encode($messageArray));
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
    function unmask($text)
    {
        $length = ord($text[1]) & 127;
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } elseif ($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }

    function mask($text)
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCNN', $b1, 127, $length);
        return $header . $text;
    }
}
