<?php

require __DIR__ . '/../vendor/autoload.php';

$client = new Swoole\Client(SWOOLE_SOCK_TCP);

if (!$client->connect('127.0.0.1', 6060, 0.5)) {
    exit("connect failed. Error: {$client->errCode}\n");
}

for ($i=0; $i < 10; $i++) {
    $client->send("hello world\n");
    echo $client->recv();
}

sleep(1);
$client->close();
