<?php

declare(strict_types=1);

namespace Chiron\Pipe;

use Chiron\Pipe\Decorator\CallableMiddleware;
use Chiron\Pipe\Decorator\FixedResponseMiddleware;
use Chiron\Pipe\Decorator\LazyLoadingMiddleware;
use Chiron\Pipe\Decorator\RequestHandlerMiddleware;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PipelineBuilder
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var array MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @param ContainerInterface|null $container Used for the LazyLoading decorator.
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Add middleware to the beginning of the stack (Prepend).
     *
     * @param string|callable|MiddlewareInterface|RequestHandlerInterface|ResponseInterface $middlewares It could also be an array of such arguments.
     *
     * @return self
     */
    public function addOnTop($middlewares): self
    {
        // Keep the right order when adding an array to the top of the middlewares stack.
        if (is_array($middlewares)) {
            $middlewares = array_reverse($middlewares);
        }

        return $this->add($middlewares, true);
    }

    /**
     * Add middleware to the bottom of the stack by default (Append).
     *
     * @param string|callable|MiddlewareInterface|RequestHandlerInterface|ResponseInterface $middlewares It could also be an array of such arguments.
     * @param bool                                                                          $onTop       Force the middleware position on top of the stack
     *
     * @return self
     */
    public function add($middlewares, bool $onTop = false): self
    {
        if (! is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        foreach ($middlewares as $middleware) {
            $decorated = $this->decorate($middleware);

            if ($onTop) {
                //prepend Middleware
                array_unshift($this->middlewares, $decorated);
            } else {
                // append Middleware
                array_push($this->middlewares, $decorated);
            }
        }

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
        // TODO : vérifier si le type est un Array et dans ce cas on refait un appel à ->pipe()
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
            // TODO : lever une exception si l'objet container $this->container est à null !!!!!

            return new LazyLoadingMiddleware($middleware, $this->container);
        }

        throw new InvalidArgumentException(sprintf(
            'Middleware "%s" is neither a valid string service name, a PHP callable, or an instance of %s/%s/%s',
            is_object($middleware) ? get_class($middleware) : gettype($middleware),
            MiddlewareInterface::class,
            ResponseInterface::class,
            RequestHandlerInterface::class
        ));
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
     * Build a new Pipeline object.
     *
     * @return RequestHandlerInterface
     */
    public function build(): RequestHandlerInterface
    {
        return new Pipeline($this->middlewares);
    }

    /**
     * Execute the middleware stack.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    // TODO : passer en facultatif un paramétre supplémentaire : $response, ce qui permettra d'initialiser le dernier middleware qui sera la réponse par défaut si aucun middleware ne retourne de réponse.
    // TODO : méthode à virer.
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->build();

        return $handler->handle($request);
    }
}
