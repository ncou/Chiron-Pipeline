<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FixedResponseRequestHandler implements RequestHandlerInterface
{
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}
