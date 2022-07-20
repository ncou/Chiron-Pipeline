<?php

declare(strict_types=1);

namespace Chiron\Pipeline\Event;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * BeforeMiddlewareEvent is raised before executing a middleware.
 */
final class BeforeMiddlewareEvent
{
    /** @var MiddlewareInterface */
    private MiddlewareInterface $middleware;
    /** @var ServerRequestInterface */
    private ServerRequestInterface $request;

    /**
     * @param MiddlewareInterface $middleware
     * @param ServerRequestInterface $request
     */
    public function __construct(MiddlewareInterface $middleware, ServerRequestInterface $request)
    {
        $this->middleware = $middleware;
        $this->request = $request;
    }

    /**
     * @return MiddlewareInterface
     */
    public function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
