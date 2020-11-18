<?php

declare(strict_types=1);

namespace Chiron\Pipeline;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use OutOfBoundsException;

final class EmptyPipelineHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO : créer une PipelineException, ou une Exception\EmptyPipelineException ????  https://github.com/zendframework/zend-stratigility/blob/master/src/Exception/EmptyPipelineException.php
        throw new OutOfBoundsException('Reached end of middleware queue. Does your controller return a response ?');
    }
}
