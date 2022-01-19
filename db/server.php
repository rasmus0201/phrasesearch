<?php

// extension="openswoole.so"
// extension="dba-lmdb.so"
// cp /opt/homebrew/lib/php/pecl/20190902/openswoole.so /opt/homebrew/Cellar/php@7.4/7.4.27/lib/php/20190902/openswoole.so
// sudo cp /usr/local/lib/php/extensions/no-debug-non-zts-20190902/dba.so /opt/homebrew/Cellar/php@7.4/7.4.27/lib/php/20190902/dba.so

require __DIR__ . '/../vendor/autoload.php';

$server = new Swoole\Server("127.0.0.1", 6060);

$server->on('connect', function ($server, $fd) {
    echo "connection open: {$fd}\n";
});
$server->on('receive', function ($server, $fd, $from_id, $data) {
    $server->send($fd, "Swoole: {$data}");
    $server->close($fd);
});
$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});
$server->start();
