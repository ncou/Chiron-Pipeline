<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator\RequestHandler;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallableRequestHandler implements RequestHandlerInterface
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = call_user_func($this->callable, $request);

        if (! $response instanceof ResponseInterface) {
            throw new LogicException(sprintf(
                'Decorated callable request handler of type %s failed to produce a response.',
                is_object($this->callable) ? get_class($this->callable) : gettype($this->callable)
            ));
        }

        return $response;
    }
}
