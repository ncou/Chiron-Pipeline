<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator\RequestHandler;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LazyRequestHandler implements RequestHandlerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $handlerName;

    public function __construct(ContainerInterface $container, string $handlerName)
    {
        $this->container = $container;
        $this->handlerName = $handlerName;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! $this->container->has($this->handlerName)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot fetch request handler service "%s"; service not registered',
                $this->middlewareName
            ));
        }

        $handler = $this->container->get($this->handlerName);

        if (! $handler instanceof RequestHandlerInterface) {
            throw new InvalidArgumentException(sprintf(
                'RequestHandler service "%s" did not to resolve to a %s instance; resolved to "%s"',
                $this->handlerName,
                RequestHandlerInterface::class,
                is_object($handler) ? get_class($handler) : gettype($handler)
            ));
        }

        return $handler->handle($request);
    }
}
