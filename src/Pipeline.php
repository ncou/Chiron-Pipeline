<?php

declare(strict_types=1);

namespace Chiron\Pipe;

use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Pipeline implements RequestHandlerInterface
{
    /**
     * @var array MiddlewareInterface[]
     */
    private $queue = [];

    /**
     * @param MiddlewareInterface $middleware Middleware to add at the end of the queue.
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->queue[] = $middleware;

        return $this;
    }

    /**
     * Execute the middleware stack.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = array_shift($this->queue);

        if (is_null($middleware)) {
            throw new OutOfBoundsException('Reached end of middleware queue. Does your controller return a response ?');
        }

        return $middleware->process($request, $this);
    }
}
