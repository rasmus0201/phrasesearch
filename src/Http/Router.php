<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Http;

use Swoole\Http\Request;
use Swoole\Http\Response;

class Router
{
    private const HTTP_POST = 'POST';
    private const HTTP_GET = 'GET';
    private const HTTP_PUT = 'PUT';
    private const HTTP_PATCH = 'PATCH';
    private const HTTP_DELETE = 'DELETE';
    private const HTTP_OPTION = 'OPTION';

    private array $routes = [];

    public function __construct()
    {
        $this->routes[self::HTTP_POST] = [];
        $this->routes[self::HTTP_GET] = [];
        $this->routes[self::HTTP_PUT] = [];
        $this->routes[self::HTTP_PATCH] = [];
        $this->routes[self::HTTP_DELETE] = [];
        $this->routes[self::HTTP_OPTION] = [];
    }

    public function handle(Request $request, Response $response): void
    {
        $method = strtoupper($request->server['request_method']);
        $routes = $this->routes[$method] ?? [];
        if (empty($routes)) {
            $this->dispatch404($response);
            return;
        }

        $path = '/' . trim($request->server['request_uri'], '/');
        $controller = $routes[$path] ?? null;
        if (!isset($controller)) {
            $this->dispatch404($response);
            return;
        }

        if ($method == self::HTTP_POST) {
            $controller($request, $response, $this->getJsonBody($request));
            return;
        }

        $controller($request, $response);
    }

    public function routes(): array
    {
        return $this->routes;
    }

    public function get(string $uri, callable $controller): void
    {
        $this->routes[self::HTTP_GET]['/'.trim($uri, '/')] = $controller;
    }

    public function post(string $uri, callable $controller): void
    {
        $this->routes[self::HTTP_POST]['/'.trim($uri, '/')] = $controller;
    }

    public function dispatch404(Response $response): void
    {
        $response->status(404, 'Not found');
    }

    private function getJsonBody(Request $request): array
    {
        return json_decode($request->rawContent(), true, 16, JSON_THROW_ON_ERROR);
    }
}
