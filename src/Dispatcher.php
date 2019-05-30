<?php

declare(strict_types=1);

namespace Chiron\Pipe;

use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UnexpectedValueException;

// TODO : renommer la classe en PipeHandler
class Dispatcher implements RequestHandlerInterface
{
    /**
     * @var array MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @var int
     */
    private $index = 0;

    /**
     * @param MiddlewareInterface|MiddlewareInterface[] $middlewares single middleware or an array of middlewares.
     */
    public function __construct(...$middlewares)
    {
        // Allow passing arrays of middleware or individual lists of middleware

        if (is_array($middlewares[0]) && count($middlewares) === 1) {
            $middlewares = array_shift($middlewares);
        }

        $this->middlewares = $middlewares;
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
        if ($this->index >= count($this->middlewares)) {
            throw new OutOfBoundsException('Reached end of middleware stack. Does your controller return a response ?');
        }

        $middleware = $this->middlewares[$this->index++];

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
