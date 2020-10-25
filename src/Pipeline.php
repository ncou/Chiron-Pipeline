<?php

declare(strict_types=1);

namespace Chiron\Pipe;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * RequestHandler
 *
 * @link https://www.php-fig.org/psr/psr-15/
 * @link https://www.php-fig.org/psr/psr-15/meta/
 */
final class Pipeline implements RequestHandlerInterface
{

    /** @var array MiddlewareInterface[] */
    private $middleware = [];

    /** @var RequestHandlerInterface */
    private $fallback;

    /** @var int */
    private $index = 0;

    /**
     * Constructor of the class
     */
    public function __construct(iterable $middlewares = [], ?RequestHandlerInterface $fallback = null)
    {
        // Use the pipe() method to enforce the typehint as MiddlewareInterface.
        foreach ($middlewares as $middleware) {
            $this->pipe($middleware);
        }

        $this->fallback = $fallback ?? new EmptyPipelineHandler();
    }

    /**
     * @param MiddlewareInterface $middleware Middleware to add at the end of the array.
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * @param RequestHandlerInterface $fallback RequestHandler used if the last middleware doesn't return a response.
     */
    public function setFallback(RequestHandlerInterface $fallback): self
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if (isset($this->middleware[$this->index])) {
            return $this->middleware[$this->index]->process($request, $this->nextHandler());
        }

        return $this->fallback->handle($request);
    }

    /**
     * Get a handler pointing to the next middleware.
     *
     * @return static
     */
    private function nextHandler()
    {
        $copy = clone $this;
        $copy->index++;

        return $copy;
    }
}
