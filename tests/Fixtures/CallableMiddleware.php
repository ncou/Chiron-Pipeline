<?php

declare(strict_types=1);

namespace Chiron\Pipeline\Tests\Fixtures;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Container\ContainerAwareInterface;
use Chiron\Container\ContainerAwareTrait;

class CallableMiddleware implements ContainerAwareInterface, MiddlewareInterface
{
    use ContainerAwareTrait;

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
        return call_user_func($this->callable, $request, $handler);
    }
}
