<?php

declare(strict_types=1);

namespace Tests\Pipe;

use Chiron\Pipe\Decorator\RequestHandlerMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerMiddlewareTest extends TestCase
{
    public function testInvokeRequestHandlerHandleMethod()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $handlerMock = $this->createMock(RequestHandlerInterface::class);

        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->with($requestMock)
            ->willReturn($responseMock);

        $middleware = new RequestHandlerMiddleware($handlerMock);
        $middleware->process($requestMock, $handlerMock);
    }
}
