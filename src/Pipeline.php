<?php

declare(strict_types=1);

namespace Chiron\Pipe;

use Chiron\Pipe\Decorator\CallableMiddleware;
use Chiron\Pipe\Decorator\FixedResponseMiddleware;
use Chiron\Pipe\Decorator\LazyLoadingMiddleware;
use Chiron\Pipe\Decorator\PredicateDecorator;
use Chiron\Pipe\Decorator\RequestHandlerMiddleware;
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
    private $middlewares = [];

    /**
     * @var int
     */
    private $index = 0;

    /**
     * @param null|ContainerInterface $container Used for the LazyLoading decorator.
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param string|callable|MiddlewareInterface|RequestHandlerInterface|ResponseInterface $middlewares It could also be an array of such arguments.
     *
     * @return self
     */
    public function pipe($middlewares): self
    {
        //Add middleware to the end of the stack => Append
        //array_push($this->middlewares, $this->decorate($middleware));

        if (! is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        foreach ($middlewares as $middleware) {
            $this->middlewares[] = $this->decorate($middleware);
        }

        return $this;
    }

    /**
     * @param string|callable|MiddlewareInterface|RequestHandlerInterface|ResponseInterface $middlewares It could also be an array of such arguments.
     * @param callable                            $predicate   Used to determine if the middleware should be executed
     *
     * @return self
     */
    public function pipeIf($middlewares, callable $predicate): self
    {
        if (! is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        foreach ($middlewares as $middleware) {
            $this->middlewares[] = new PredicateDecorator($this->decorate($middleware), $predicate);
        }

        return $this;
    }

    /**
     * Add middleware to the beginning of the stack (Prepend).
     *
     * @param string|callable|MiddlewareInterface|RequestHandlerInterface|ResponseInterface $middleware It can't be an array.
     *
     * @return self
     */
    // TODO : permettre de passer des tableaux de middlewares à cette méthode.
    // TODO : créer aussi une méthode pipeOnTopIf()
    public function pipeOnTop($middleware): self
    {
        array_unshift($this->middlewares, $this->decorate($middleware));

        return $this;
    }

    /**
     * Remove all the piped middlewares.
     *
     * @return self
     */
    public function flush(): self
    {
        $this->middlewares = [];

        return $this;
    }

    /**
     * Decorate the middleware if necessary.
     *
     * @param string|callable|MiddlewareInterface|RequestHandlerInterface|ResponseInterface $middleware Doesn't support array !
     *
     * @return MiddlewareInterface
     */
    // TODO : gérer les tableaux de ces type (string|callable...etc)
    private function decorate($middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        } elseif ($middleware instanceof RequestHandlerInterface) {
            return new RequestHandlerMiddleware($middleware);
        } elseif ($middleware instanceof ResponseInterface) {
            return new FixedResponseMiddleware($middleware);
        } elseif (is_callable($middleware)) {
            return new CallableMiddleware($middleware);
        } elseif (is_string($middleware)) {
            return new LazyLoadingMiddleware($middleware, $this->container);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Middleware "%s" is neither a string service name, an autoloadable class name, a PHP callable, or an instance of %s/%s/%s',
                is_object($middleware) ? get_class($middleware) : gettype($middleware),
                MiddlewareInterface::class, ResponseInterface::class, RequestHandlerInterface::class
            ));
        }
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

        return $middleware->process($request, $this);
    }
}
