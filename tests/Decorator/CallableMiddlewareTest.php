<?php

declare(strict_types=1);

namespace Tests\Pipe;

use Chiron\Pipe\Decorator\CallableMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallableMiddlewareTest extends TestCase
{
    public function testGivenCallback_InvokeCallback()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $handlerMock = $this->createMock(RequestHandlerInterface::class);

        $callable = function ($request, $handler) use ($requestMock, $handlerMock) {
            $this->assertSame($requestMock, $request);
            $this->assertSame($handlerMock, $handler);

            return $this->createMock(ResponseInterface::class);
        };

        $middleware = new CallableMiddleware($callable);
        $middleware->process($requestMock, $handlerMock);
    }
}
