<?php

declare(strict_types=1);

namespace Chiron\Pipe;

/**
 * Import classes
 */
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use OutOfBoundsException;


final class EmptyPipelineHandler implements RequestHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        throw new OutOfBoundsException('Reached end of middleware queue. Does your controller return a response ?');
    }
}
