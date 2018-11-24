<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator;

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
        // retrieve the middleware in the container. It could be a : MiddlewareInterface object, or a callable
        // TODO : lever une exception si le type de l'objet n'est pas un middlewareinterface ou un callable !!!!!
        $entry = $this->container->get($this->middlewareName);

        // TODO : gérer le cas ou il s'agit d'une string et que cette classe existe => https://github.com/zendframework/zend-expressive/blob/master/src/MiddlewareContainer.php#L43

        // TODO : vérifier si l'utilisation d'un callable est suffisante ou si il faut faire comme Zend.
        if (is_callable($entry)) {
            return call_user_func_array($entry, [$request, $handler]);
        }

        if ($entry instanceof MiddlewareInterface) {
            return $entry->process($request, $handler);
        }

        // Try to inject the dependency injection container in the middleware
        /*
        if (is_callable([$middleware, 'setContainer']) && $this->container instanceof ContainerInterface) {
            $middleware->setContainer($this->container);
        }*/

        throw new \InvalidArgumentException('The middleware present in the container should be a PHP callable or a Psr\Http\Server\MiddlewareInterface instance');
    }
}
