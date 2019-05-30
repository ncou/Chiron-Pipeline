<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FixedResponseMiddleware implements MiddlewareInterface
{
    /**
     * @var ResponseInterface
     */

    private $fixedResponse;

    public function __construct(ResponseInterface $response)
    {
        $this->fixedResponse = $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->fixedResponse;
    }
}
