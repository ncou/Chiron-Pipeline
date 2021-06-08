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
        // TODO : Lever une emptypipelineexception ???? https://github.com/zendframework/zend-stratigility/blob/9d544e7696712d8571ef6893a083cfc1fa0b4233/src/Exception/EmptyPipelineException.php
        throw new PipelineException('Pipeline reached end of middleware queue and failed to return a response.');
    }
}
