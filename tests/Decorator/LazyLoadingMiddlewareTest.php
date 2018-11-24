<?php

declare(strict_types=1);

namespace Tests\Pipe;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Chiron\Pipe\Decorator\LazyLoadingMiddleware;

class LazyLoadingMiddlewareTest extends TestCase
{
    public function testGivenContainerAndMiddlewareName_InvokeNewlyCreatedMiddlewareInstanceProcess()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $handlerMock = $this->createMock(RequestHandlerInterface::class);
        $middlewareMock = $this->createMock(MiddlewareInterface::class);
        $middlewareMock
            ->expects($this->once())
            ->method('process')
            ->with($requestMock, $handlerMock)
            ->willReturn($responseMock);
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('testMiddleware')
            ->willReturn($middlewareMock);
        $middleware = new LazyLoadingMiddleware($containerMock, 'testMiddleware');
        $response = $middleware->process($requestMock, $handlerMock);
        $this->assertSame($responseMock, $response);
    }
}
