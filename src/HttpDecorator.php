<?php

declare(strict_types=1);

namespace Chiron\Pipe;

use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;
use Chiron\Pipe\Decorator\Middleware\CallableMiddleware;
use Chiron\Pipe\Decorator\Middleware\FixedResponseMiddleware;
use Chiron\Pipe\Decorator\Middleware\LazyMiddleware;
use Chiron\Pipe\Decorator\Middleware\RequestHandlerMiddleware;
use Chiron\Pipe\Decorator\RequestHandler\CallableRequestHandler;
use Chiron\Pipe\Decorator\RequestHandler\FixedResponseRequestHandler;
use Chiron\Pipe\Decorator\RequestHandler\LazyRequestHandler;
use InvalidArgumentException;
use LogicException;

//https://github.com/zendframework/zend-expressive/blob/master/src/MiddlewareFactory.php#L67
//https://github.com/mezzio/mezzio/blob/master/src/MiddlewareFactory.php#L68

final class HttpDecorator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container Used for the lazy loading decorator.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Decorate the middleware if necessary.
     *
     * @param string|callable|MiddlewareInterface|RequestHandlerInterface|ResponseInterface $middleware Doesn't support array !
     *
     * @return MiddlewareInterface
     */
    // TODO : gérer les tableaux de ces type (string|callable...etc)
    // TODO : vérifier si le type est un Array et dans ce cas on refait un appel à ->pipe()
    public function toMiddleware($middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if ($middleware instanceof RequestHandlerInterface) {
            return new RequestHandlerMiddleware($middleware);
        }

        if ($middleware instanceof ResponseInterface) {
            return new FixedResponseMiddleware($middleware);
        }

        if (is_callable($middleware)) {
            return new CallableMiddleware($middleware);
        }

        if (is_string($middleware) && $middleware !== '') {
            return new LazyMiddleware($this->container, $middleware);
        }

        throw new InvalidArgumentException(sprintf(
            'Middleware "%s" is neither a valid string service name, a PHP callable, or an instance of %s/%s/%s',
            is_object($middleware) ? get_class($middleware) : gettype($middleware),
            MiddlewareInterface::class,
            ResponseInterface::class,
            RequestHandlerInterface::class
        ));
    }

    public function toHandler($handler): RequestHandlerInterface
    {
        if ($handler instanceof RequestHandlerInterface) {
            return $handler;
        }

        if ($handler instanceof ResponseInterface) {
            return new FixedResponseRequestHandler($handler);
        }

        if (is_callable($handler)) {
            return new CallableRequestHandler($handler);
        }

        if (is_string($handler) && $handler !== '') {
            return new LazyRequestHandler($this->container, $handler);
        }

        throw new InvalidArgumentException(sprintf(
            'RequestHandler "%s" is neither a valid string service name, a PHP callable, or an instance of %s/%s',
            is_object($handler) ? get_class($handler) : gettype($handler),
            ResponseInterface::class,
            RequestHandlerInterface::class
        ));
    }
}
