<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator\Middleware;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallableMiddleware implements MiddlewareInterface
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = call_user_func($this->callable, $request, $handler);

        if (! $response instanceof ResponseInterface) {
            throw new LogicException(sprintf(
                'Decorated callable middleware of type %s failed to produce a response.',
                is_object($this->callable) ? get_class($this->callable) : gettype($this->callable)
            ));
        }

        return $response;
    }
}
