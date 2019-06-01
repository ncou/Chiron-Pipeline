<?php

declare(strict_types=1);

namespace Chiron\Tests\Pipe;

use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Pipe\Decorator\CallableMiddleware;
use Chiron\Pipe\Pipeline;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;

class PipelineTest extends TestCase
{
    public $request;

    protected function setUp()
    {
        $this->request = new ServerRequest('GET', new Uri('/'));
    }

    public function testPipelineAcceptsMultipleArguments()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware3 = $this->prophesize(MiddlewareInterface::class)->reveal();

        $handler = new Pipeline($middleware1, $middleware2, $middleware3);

        $middlewaresArray = $this->readAttribute($handler, 'queue');

        $this->assertSame([$middleware1, $middleware2, $middleware3], $middlewaresArray);
    }

    public function testPipelineAcceptsASingleArrayArgument()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware3 = $this->prophesize(MiddlewareInterface::class)->reveal();

        $handler = new Pipeline([$middleware1, $middleware2, $middleware3]);

        $middlewaresArray = $this->readAttribute($handler, 'queue');

        $this->assertSame([$middleware1, $middleware2, $middleware3], $middlewaresArray);
    }

    public function testPipelineAcceptsASingleArgument()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();

        $handler = new Pipeline($middleware1);

        $middlewaresArray = $this->readAttribute($handler, 'queue');

        $this->assertSame([$middleware1], $middlewaresArray);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage is not an instance of Psr\Http\Server\MiddlewareInterface
     */
    public function testPipelineThrowExceptionForInvalidMultipleArguments()
    {
        $middleware = new CallableMiddleware(function ($request, $handler) {
            $response = $handler->handle($request);
        });

        $handler = new Pipeline($middleware, 'invalid type');

        $handler->handle($this->request);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage is not an instance of Psr\Http\Server\MiddlewareInterface
     */
    public function testPipelineThrowExceptionForInvalidSingleArrayArgument()
    {
        $middleware = new CallableMiddleware(function ($request, $handler) {
            $response = $handler->handle($request);
        });

        $handler = new Pipeline([$middleware, 'invalid type']);

        $handler->handle($this->request);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage is not an instance of Psr\Http\Server\MiddlewareInterface
     */
    public function testPipelineThrowExceptionForInvalidSingleArgument()
    {
        $handler = new Pipeline('invalid type');

        $handler->handle($this->request);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Reached end of middleware stack. Does your controller return a response ?
     */
    public function testPipelineConstructorEmpty()
    {
        $handler = new Pipeline();

        $handler->handle($this->request);
    }
}
