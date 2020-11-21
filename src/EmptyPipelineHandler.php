<?php

declare(strict_types=1);

namespace Chiron\Pipeline;

use Chiron\Pipeline\Exception\PipelineException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class EmptyPipelineHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new PipelineException('Pipeline reached end of middleware queue and failed to return a response');
    }
}
