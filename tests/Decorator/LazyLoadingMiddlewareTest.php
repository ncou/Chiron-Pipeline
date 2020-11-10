<?php

declare(strict_types=1);

namespace Chiron\Pipeline\Tests\Decorator;

use Chiron\Pipe\Decorator\LazyLoadingMiddleware;
use Chiron\Tests\Pipe\Fixtures\FoobarBadClass;
use Chiron\Tests\Pipe\Fixtures\FoobarClass;
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

    public function testMiddlewareNotFoundInTheContainerButItsAutoloadable()
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
            ->method('has')
            ->with(FoobarClass::class)
            ->willReturn(false);

        $middleware = new LazyLoadingMiddleware($containerMock, FoobarClass::class);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot fetch middleware service "foobar"; service not registered
     */
    public function testExceptionMiddlewareNotFoundInTheContainerAndNotAutoloadable()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $handlerMock = $this->createMock(RequestHandlerInterface::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot fetch middleware service "Chiron\Tests\Pipe\Fixtures\FoobarBadClass"; service not registered
     */
    public function testExceptionMiddlewareNotFoundInTheContainerButItsAutoloadableWith_Not_A_ValidType()
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $handlerMock = $this->createMock(RequestHandlerInterface::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
            ->method('has')
            ->with(FoobarBadClass::class)
            ->willReturn(false);

        $middleware = new LazyLoadingMiddleware($containerMock, FoobarBadClass::class);
        $response = $middleware->process($requestMock, $handlerMock);
    }
}
