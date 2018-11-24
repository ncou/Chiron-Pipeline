<?php

declare(strict_types=1);

namespace Tests\Pipe;

use Chiron\Pipe\Decorator\LazyLoadingMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
            ->method('has')
            ->with('testMiddleware')
            ->willReturn(true);

        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('testMiddleware')
            ->willReturn($middlewareMock);
        $middleware = new LazyLoadingMiddleware($containerMock, 'testMiddleware');
        $response = $middleware->process($requestMock, $handlerMock);
        $this->assertSame($responseMock, $response);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The middleware "foobar" is not present in the container
     */
    public function testExceptionMiddlewareNotFoundInTheContainer()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $handlerMock = $this->createMock(RequestHandlerInterface::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with('foobar')
            ->willReturn(false);

        $middleware = new LazyLoadingMiddleware($containerMock, 'foobar');
        $response = $middleware->process($requestMock, $handlerMock);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testResolvedMiddlewareIsNot_A_ValidType()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $handlerMock = $this->createMock(RequestHandlerInterface::class);

        $containerMock = $this->createMock(ContainerInterface::class);

        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with('foo')
            ->willReturn(true);

        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn('bar');

        $middleware = new LazyLoadingMiddleware($containerMock, 'foo');
        $response = $middleware->process($requestMock, $handlerMock);
    }
}
