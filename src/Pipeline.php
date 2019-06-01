<?php

declare(strict_types=1);

namespace Chiron\Pipe;

use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UnexpectedValueException;

class Pipeline implements RequestHandlerInterface
{
    /**
     * @var array MiddlewareInterface[]
     */
    private $queue;

    /**
     * @param null|MiddlewareInterface|MiddlewareInterface[] $queue Can be empty or a single middleware or an array of middlewares.
     */
    public function __construct(...$queue)
    {
        // Allow passing arrays of middleware or individual lists of middleware
        if (isset($queue[0]) && is_array($queue[0]) && count($queue) === 1) {
            $queue = array_shift($queue);
        }

        $this->queue = $queue;
    }

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
            throw new OutOfBoundsException('Reached end of middleware stack. Does your controller return a response ?');
        }

        if (! $middleware instanceof MiddlewareInterface) {
            throw new UnexpectedValueException(sprintf(
                'Middleware "%s" is not an instance of %s',
                is_object($middleware) ? get_class($middleware) : gettype($middleware),
                MiddlewareInterface::class
            ));
        }

        return $middleware->process($request, $this);
    }
}
