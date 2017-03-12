<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/12
 * Time: 上午12:42
 */

namespace Minimalism\A\Server\Http\Middleware;


use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Minimalism\A\Server\Http\Context;
use Minimalism\A\Server\Http\Contract\Middleware;

class Router extends RouteCollector
{
    /**
     * @var Dispatcher
     */
    public $dispatcher;

    public function __construct()
    {
        $routeParser = new \FastRoute\RouteParser\Std();
        $dataGenerator = new \FastRoute\DataGenerator\GroupCountBased();
        parent::__construct($routeParser, $dataGenerator);
    }

    public function routes()
    {
        $this->dispatcher = new \FastRoute\Dispatcher\GroupCountBased($this->getData());
        return [$this, "dispatch"];
    }

    public function dispatch(Context $ctx, $next)
    {
        if ($this->dispatcher === null) {
            $this->routes();
        }

        $uri = $ctx->url;
        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $this->dispatcher->dispatch(strtoupper($ctx->method), $uri);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                $ctx->status = 404;
                // ... 404 Not Found
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $ctx->status = 405;
                // ... 405 Method Not Allowed
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $handler($ctx, $next, $vars);
                // ... call $handler with $vars
                break;
        }
    }
}