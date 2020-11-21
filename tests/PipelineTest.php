<?php

declare(strict_types=1);

namespace Chiron\Pipeline\Tests;

use Chiron\Pipeline\Decorator\CallableMiddleware;
use Chiron\Pipeline\Decorator\FixedResponseMiddleware;
use Chiron\Pipeline\Pipeline;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Tests\Pipeline\Fixtures\EmptyMiddleware;
use Nyholm\Psr7\ServerRequest;
use Chiron\Container\Container;

//https://github.com/zendframework/zend-expressive/blob/master/test/MiddlewareFactoryTest.php#L49

class PipelineTest extends TestCase
{
    private $request;

    protected function setUp(): void
    {
        $this->request = new ServerRequest('GET', '/');
    }

    public function testPipelineInstanceOfRequestHandler()
    {
        $handler = new Pipeline(new Container());

        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
    }

    public function testEmptyMiddlewareQueueAfterFirstInstanciation()
    {
        $handler = new Pipeline(new Container());

        $middlewaresArray = $this->readAttribute($handler, 'middlewares');
        $this->assertSame([], $middlewaresArray);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Reached end of middleware queue. Does your controller return a response ?
     */
    public function testPipelineThrowExceptionIfQueueIsEmpty()
    {
        $handler = new Pipeline(new Container());

        $handler->handle($this->request);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Reached end of middleware queue. Does your controller return a response ?
     */
    public function testPipelineThrowExceptionIfMiddlewareDoesntReturnAResponse()
    {
        $handler = new Pipeline(new Container());

        $handler->pipe(new EmptyMiddleware());

        $handler->handle($this->request);
    }

    public function testPipeMiddlewares()
    {
        $middleware_1 = new CallableMiddleware(function ($request, $handler) {
            $response = $handler->handle($request);
            $response->getBody()->write('bar');

            return $response;
        });

        $middleware_2 = new CallableMiddleware(function ($request, $handler) {
            $response = $handler->handle($request);
            $response->getBody()->write('foo');

            return $response;
        });

        $middleware_3 = new FixedResponseMiddleware(new Response(202));

        $handler = new Pipeline(new Container());

        $handler->pipe($middleware_1);
        $handler->pipe($middleware_2);
        $handler->pipe($middleware_3);

        $middlewaresArray = $this->readAttribute($handler, 'middlewares');

        $this->assertSame([$middleware_1, $middleware_2, $middleware_3], $middlewaresArray);

        $response = $handler->handle($this->request);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('foobar', (string) $response->getBody());
    }
}
