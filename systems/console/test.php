<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (getenv('FLAME_ENABLE_WEBSOCKET_TEST') !== '1') {
    throw new RuntimeException('Set FLAME_ENABLE_WEBSOCKET_TEST=1 to run this local-only test server.');
}

$address = getenv('FLAME_WEBSOCKET_TEST_HOST') ?: '127.0.0.1';
$packedAddress = inet_pton($address);
$isLoopback = $packedAddress !== false && (
    (strlen($packedAddress) === 4 && ord($packedAddress[0]) === 127)
    || $packedAddress === inet_pton('::1')
);
if (!$isLoopback) {
    throw new InvalidArgumentException('The WebSocket test server may bind only to a loopback address.');
}

$port = getenv('FLAME_WEBSOCKET_TEST_PORT');
$port = $port === false || $port === '' ? 10000 : filter_var($port, FILTER_VALIDATE_INT);
if ($port === false || $port < 1024 || $port > 65535) {
    throw new InvalidArgumentException('FLAME_WEBSOCKET_TEST_PORT must be between 1024 and 65535.');
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'WebSocket.php';

class Test extends \Flame\WebSocket
{
    public function onReceive()
    {
        $this->message = 'test1234';
        parent::onMessage();
    }
}

$ee = new Test();
$ee->address = $address;
$ee->port = $port;
$ee->start();
