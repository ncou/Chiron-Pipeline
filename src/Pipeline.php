<?php

declare(strict_types=1);

namespace Chiron\Pipe;

use Chiron\Pipe\Decorator\CallableMiddleware;
use Chiron\Pipe\Decorator\LazyLoadingMiddleware;
use Chiron\Pipe\Decorator\PredicateDecorator;
use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Pipeline implements RequestHandlerInterface
{
    /**
     * @var null|ContainerInterface
     */
    private $container;

    /**
     * @var array MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * @var int
     */
    private $index = 0;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param string|callable|MiddlewareInterface or an array of such arguments $middlewares
     */
    public function pipe($middlewares): self
    {
        if (! is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        foreach ($middlewares as $middleware) {
            $this->middlewares[] = $this->prepare($middleware);
        }

        return $this;
    }

    /**
     * @param string|callable|MiddlewareInterface or an array of such arguments $middlewares
     * @param callable                                                          $predicate   Used to determine if the middleware should be executed
     */
    public function pipeIf($middlewares, callable $predicate): self
    {
        if (! is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        foreach ($middlewares as $middleware) {
            $this->middlewares[] = new PredicateDecorator($this->prepare($middleware), $predicate);
        }

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->index >= count($this->middlewares)) {
            throw new OutOfBoundsException('Reached end of middleware stack. Does your controller return a response ?');
        }

        $middleware = $this->middlewares[$this->index++];

        return $middleware->process($request, $this);
    }

    /**
     * Decorate the middleware if necessary.
     *
     * @param string|callable|MiddlewareInterface $middleware
     *
     * @return MiddlewareInterface
     */
    // TODO : gérer les tableaux de ces type (string|callable...etc)
    private function prepare($middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        } elseif (is_callable($middleware)) {
            return new CallableMiddleware($middleware);
        } elseif (is_string($middleware)) {
            return new LazyLoadingMiddleware($middleware, $this->container);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Middleware "%s" is neither a string service name, an autoloadable class name, a PHP callable, or a %s instance',
                is_object($middleware) ? get_class($middleware) : gettype($middleware),
                MiddlewareInterface::class
            ));
        }
    }
}
