<?php

declare(strict_types=1);

namespace Chiron\Pipeline;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Container\Container;
use Chiron\Container\ContainerAwareInterface;

/**
 * Attempts to handle an incoming request by doing the following:
 *
 * - Cloning itself, to produce a request handler.
 * - Dequeuing the first middleware in the cloned handler.
 * - Processing the first middleware using the request and the cloned handler.
 *
 * If the pipeline is empty at the time this method is invoked, it will raise an exception.
 *
 * @see https://www.php-fig.org/psr/psr-15/
 * @see https://www.php-fig.org/psr/psr-15/meta/
 */
// TODO : corriger la phpDoc !!!!
final class Pipeline implements RequestHandlerInterface
{
    /** @var array MiddlewareInterface[] */
    private $middlewares = [];
    /** @var RequestHandlerInterface */
    private $fallback;
    /** @var int */
    private $position = 0;
    /** @ver Container */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->fallback = new EmptyPipelineHandler();
    }

    /**
     * @param MiddlewareInterface $middleware
     *
     * @return self
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        if ($middleware instanceof ContainerAwareInterface && ! $middleware->hasContainer()) {
            $middleware->setContainer($this->container);
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param RequestHandlerInterface $handler
     *
     * @return self
     */
    public function fallback(RequestHandlerInterface $handler): self
    {
        if ($handler instanceof ContainerAwareInterface && ! $handler->hasContainer()) {
            $handler->setContainer($this->container);
        }

        $this->fallback = $handler;

        return $this;
    }

    /**
     * Bind a fresh ServerRequestInterface instance in the container.
     * Iterate and execute the middleare queue, then execute the fallback hander.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws PipelineException if no middleware or handler is present in order to return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Attach a fresh instance of the request in the container.
        $this->bindRequestInstance($request);

        if (isset($this->middlewares[$this->position])) {
            return $this->middlewares[$this->position]->process($request, $this->nextHandler());
        }

        return $this->fallback->handle($request);
    }

    /**
     * Store a "fresh" Request instance in the container.
     * Usefull if you need to retrieve some request attributes.
     *
     * Ex: in the CookieCollectionMiddleware with the CookieCollection::ATTRIBUTE
     *
     * @param ServerRequestInterface $request
     */
    private function bindRequestInstance(ServerRequestInterface $request): void
    {
        $this->container->bind(ServerRequestInterface::class, $request);
    }

    /**
     * Get a handler pointing to the next middleware position.
     *
     * @return RequestHandlerInterface New Pipeline instance used as handler.
     */
    private function nextHandler(): RequestHandlerInterface
    {
        $copy = clone $this;
        $copy->position++;

        return $copy;
    }
}
