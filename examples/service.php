#!php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use League\Route\Http\Exception\NotFoundException;
use League\Route\Router;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('127.0.0.1', 9501, SWOOLE_BASE);

$server->set([
    'package_max_length' => 128 * 1024 * 1024,
    'worker_num' => 4,
    'task_worker_num' => 128,
]);

$router = new Router();
$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
);

$router->map('GET', '/', function (ServerRequestInterface $request): ResponseInterface {
    return new Psr7Response(
        200,
        [],
        '<h1>Phrasesearch API reached!</h1>'
    );
});

$router->map('GET', '/{.*}', function (ServerRequestInterface $request): ResponseInterface {
    return new Psr7Response(404, [], '404');
});

$router->post('/index', function (Request $request, Response $response, array $documents) use ($server) {
    print_r('Adding ' . count($documents) . ' documents for indexing...' . PHP_EOL);

    $server->task(['task' => 'index', 'data' => $documents], -1);
    $response->end(
        'Will add ' . count($documents) . ' documents to index-queue'
    );
});

$server->on('Request', function (Request $request, Response $response) use ($router, $creator) {
    $serverRequest = $creator->fromArrays(
        array_change_key_case($request->server, CASE_UPPER),
        $request->header ?? [],
        $request->cookie ?? [],
        $request->get ?? [],
        $request->post,
        $request->files ?? [],
        $request->rawContent()
    );
    try {
        $httpResponse = $router->dispatch($serverRequest);
    } catch (NotFoundException $e) {
        var_dump($serverRequest);
        $response->setStatusCode(404, 'Not found');
        $response->end('404');
        $response->close();

        return;
    }

    $response->setStatusCode($httpResponse->getStatusCode(), $httpResponse->getReasonPhrase());
    $response->end($httpResponse->getBody()->getContents());
    $response->close();
});

$server->on('Task', function (Server $server, $task_id, $reactorId, $documents) {
    echo "Task Worker Process received data";

    echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=" . count($documents) . "." . PHP_EOL;

    var_dump($documents['task']);

    sleep(10);

    $server->finish($documents);
});

$server->on('Finish', function (Server $server, $task_id, $documents) {
    echo "Task#$task_id finished, data_len=" . count($documents) . PHP_EOL;
});

$server->start();
