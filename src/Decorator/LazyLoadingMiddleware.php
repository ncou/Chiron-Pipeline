<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LazyLoadingMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $middlewareName;

    public function __construct(ContainerInterface $container, string $middlewareName)
    {
        $this->container = $container;
        $this->middlewareName = $middlewareName;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->container->has($this->middlewareName)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot fetch middleware service "%s"; service not registered',
                $this->middlewareName
            ));
        }

        $middleware = $this->container->get($this->middlewareName);

        if (! $middleware instanceof MiddlewareInterface) {
            throw new InvalidArgumentException(sprintf(
                'Middleware service "%s" did not to resolve to a %s instance; resolved to "%s"',
                $this->middlewareName,
                MiddlewareInterface::class,
                is_object($middleware) ? get_class($middleware) : gettype($middleware)
            ));
        }

        return $middleware->process($request, $handler);
    }
}
