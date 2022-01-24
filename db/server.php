<?php

require __DIR__ . '/../vendor/autoload.php';

use Swoole\Server;

$server = new Server("127.0.0.1", 6060);

$server->on('connect', function ($server, $fd) {
    echo "connection open: {$fd}\n";
});
$server->on('receive', function ($server, $fd, $from_id, $data) {
    $server->send($fd, "Swoole: {$data}");
});
$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});
$server->start();
