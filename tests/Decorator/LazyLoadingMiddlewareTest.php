<?php

declare(strict_types=1);

namespace Chiron\Tests\Pipe;

use Chiron\Pipe\Decorator\LazyLoadingMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Tests\Pipe\Fixtures\FoobarClass;
use Chiron\Tests\Pipe\Fixtures\FoobarBadClass;

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
        $middleware = new LazyLoadingMiddleware('testMiddleware', $containerMock);
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

        $middleware = new LazyLoadingMiddleware(FoobarClass::class, $containerMock);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testMiddlewareNotFoundInTheContainerButItsAutoloadable_AndContainerIsNull()
    {
        $container = null;
        $middleware = new LazyLoadingMiddleware(FoobarClass::class, $container);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Service "foobar" is not registered in the container or does not resolve to an autoloadable class name
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

        $middleware = new LazyLoadingMiddleware('foobar', $containerMock);
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

        $middleware = new LazyLoadingMiddleware('foo', $containerMock);
        $response = $middleware->process($requestMock, $handlerMock);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Service "Chiron\Tests\Pipe\Fixtures\FoobarBadClass" did not to resolve to a Psr\Http\Server\MiddlewareInterface instance; resolved to "Chiron\Tests\Pipe\Fixtures\FoobarBadClass"
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

        $middleware = new LazyLoadingMiddleware(FoobarBadClass::class, $containerMock);
        $response = $middleware->process($requestMock, $handlerMock);
    }
}
