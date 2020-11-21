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
 * - Inject the container in the middleware or fallback handler if not already presents.
 * - Bind in the container the request instance to the ServerRequestInterface::class key.
 * - Cloning itself, to produce a request handler and increase the array index position.
 * - Processing the middleware in the queue (using the array index position) with the cloned handler.
 * - Using the fallback handler to return a default response (step skipped if the middleware queue return a response).
 *
 * The pipeline use a default fallback handler to throw a PipelineException for missing response reason.
 *
 * @see https://www.php-fig.org/psr/psr-15/
 * @see https://www.php-fig.org/psr/psr-15/meta/
 */
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
