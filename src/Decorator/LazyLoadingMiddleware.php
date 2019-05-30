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
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var string
     */
    private $middlewareName;

    public function __construct(
        string $middlewareName,
        ?ContainerInterface $container = null
    ) {
        $this->middlewareName = $middlewareName;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->hasService($this->middlewareName)) {
            throw new InvalidArgumentException(sprintf(
                'Service "%s" is not registered in the container or does not resolve to an autoloadable class name',
                $this->middlewareName
            ));
        }

        // retrieve the middleware in the container (it MUST be a MiddlewareInterface instance).
        $middleware = $this->getService($this->middlewareName);

        if (! $middleware instanceof MiddlewareInterface) {
            throw new InvalidArgumentException(sprintf(
                'Service "%s" did not to resolve to a %s instance; resolved to "%s"',
                $this->middlewareName,
                MiddlewareInterface::class,
                is_object($middleware) ? get_class($middleware) : gettype($middleware)
            ));
        }

        return $middleware->process($request, $handler);
    }

    /**
     * Returns true if the service is in the container, or resolves to an
     * autoloadable class name.
     *
     * @param string $service
     */
    // https://github.com/zendframework/zend-expressive/blob/master/src/MiddlewareContainer.php#L37
    private function hasService(string $service): bool
    {
        if ($this->container instanceof ContainerInterface && $this->container->has($service)) {
            return true;
        }

        return class_exists($service);
    }

    /**
     * Returns true if the service is in the container, or resolves to an
     * autoloadable class name.
     *
     * @param string $service
     *
     * @return mixed
     */
    // TODO : modifier le doc bloc pour mettre une vraie description.
    // https://github.com/zendframework/zend-expressive/blob/master/src/MiddlewareContainer.php#L56
    private function getService(string $service)
    {
        if ($this->container instanceof ContainerInterface && $this->container->has($service)) {
            return $this->container->get($service);
        }

        return new $service();
    }
}
