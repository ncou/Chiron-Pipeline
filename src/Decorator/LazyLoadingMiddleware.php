<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class LazyLoadingMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $middlewareName;

    public function __construct(
        ContainerInterface $container,
        string $middlewareName
    ) {
        $this->container = $container;
        $this->middlewareName = $middlewareName;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->container->has($this->middlewareName)) {
            throw new InvalidArgumentException(sprintf('The middleware "%s" is not present in the container', $this->middlewareName));
        }

        // retrieve the middleware in the container (it MUST be a MiddlewareInterface instance).
        $middleware = $this->container->get($this->middlewareName);

        if (! $middleware instanceof MiddlewareInterface) {
            throw new InvalidArgumentException('The middleware present in the container should be a Psr\Http\Server\MiddlewareInterface instance');
        }

        return $middleware->process($request, $handler);
    }
}
