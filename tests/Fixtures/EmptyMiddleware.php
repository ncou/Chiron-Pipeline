<?php

declare(strict_types=1);

namespace Chiron\Pipeline\Tests\Fixtures;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function call_user_func;

class EmptyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // do nothing simple use the previous handle to get the response.
        // this class is used to test an pipeline with no returned response (because previous $handler is the empty pipeline class).
        return $handler->handle($request);
    }
}
