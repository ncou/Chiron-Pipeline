<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PredicateDecorator implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface
     */
    private $middleware;

    /**
     * @var callable
     */
    private $predicate;

    public function __construct(MiddlewareInterface $middleware, callable $predicate)
    {
        $this->middleware = $middleware;
        $this->predicate = $predicate;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $predicate = $this->predicate;

        return $predicate($request)
            ? $this->middleware->process($request, $handler)
            : $handler->handle($request);
    }
}
