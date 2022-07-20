<?php

declare(strict_types=1);

namespace Chiron\Pipeline\Event;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * BeforeHandlerEvent is raised before executing the fallback handler.
 */
final class BeforeHandlerEvent
{
    /** @var RequestHandlerInterface */
    private RequestHandlerInterface $handler;
    /** @var ServerRequestInterface */
    private ServerRequestInterface $request;

    /**
     * @param RequestHandlerInterface $handler
     * @param ServerRequestInterface $request
     */
    public function __construct(RequestHandlerInterface $handler, ServerRequestInterface $request)
    {
        $this->handler = $handler;
        $this->request = $request;
    }

    /**
     * @return RequestHandlerInterface
     */
    public function getHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
