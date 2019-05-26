<?php

declare(strict_types=1);

namespace Chiron\Tests\Pipe;

use Chiron\Http\Psr\Response;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Pipe\Decorator\CallableMiddleware;
use Chiron\Pipe\Decorator\FixedResponseMiddleware;
use Chiron\Pipe\Dispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class DispatcherTest extends TestCase
{
    public $request;

    protected function setUp()
    {
        $this->request = new ServerRequest('GET', new Uri('/'));
    }

    public function testDispatcherAcceptsMultipleArguments()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware3 = $this->prophesize(MiddlewareInterface::class)->reveal();

        $dispatcher = new Dispatcher($middleware1, $middleware2, $middleware3);

        $middlewaresArray = $this->readAttribute($dispatcher, 'middlewares');

        $this->assertSame([$middleware1, $middleware2, $middleware3], $middlewaresArray);
    }

    public function testDispatcherAcceptsASingleArrayArgument()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware3 = $this->prophesize(MiddlewareInterface::class)->reveal();

        $dispatcher = new Dispatcher([$middleware1, $middleware2, $middleware3]);

        $middlewaresArray = $this->readAttribute($dispatcher, 'middlewares');

        $this->assertSame([$middleware1, $middleware2, $middleware3], $middlewaresArray);
    }

    public function testDispatcherAcceptsASingleArgument()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();

        $dispatcher = new Dispatcher($middleware1);

        $middlewaresArray = $this->readAttribute($dispatcher, 'middlewares');

        $this->assertSame([$middleware1], $middlewaresArray);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage is not an instance of Psr\Http\Server\MiddlewareInterface
     */
    public function testDispatcherThrowExceptionForInvalidMultipleArguments()
    {
        $middleware = new CallableMiddleware(function ($request, $handler) {
                $response = $handler->handle($request);
            });

        $dispatcher = new Dispatcher($middleware, 'invalid type');

        $dispatcher->handle($this->request);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage is not an instance of Psr\Http\Server\MiddlewareInterface
     */
    public function testDispatcherThrowExceptionForInvalidSingleArrayArgument()
    {
        $middleware = new CallableMiddleware(function ($request, $handler) {
                $response = $handler->handle($request);
            });

        $dispatcher = new Dispatcher([$middleware, 'invalid type']);

        $dispatcher->handle($this->request);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage is not an instance of Psr\Http\Server\MiddlewareInterface
     */
    public function testDispatcherThrowExceptionForInvalidSingleArgument()
    {
        $dispatcher = new Dispatcher('invalid type');

        $dispatcher->handle($this->request);
    }
}
