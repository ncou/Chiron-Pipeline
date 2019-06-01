<?php

declare(strict_types=1);

namespace Chiron\Tests\Pipe;

use Chiron\Http\Psr\Response;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Pipe\Decorator\CallableMiddleware;
use Chiron\Pipe\Decorator\FixedResponseMiddleware;
use Chiron\Pipe\Pipeline;
use PHPUnit\Framework\TestCase;

//https://github.com/zendframework/zend-expressive/blob/master/test/MiddlewareFactoryTest.php#L49

class PipelineTest extends TestCase
{
    public $request;

    protected function setUp()
    {
        $this->request = new ServerRequest('GET', new Uri('/'));
    }

    public function testEmptyMiddlewareQueueAfterFirstInstanciation()
    {
        $handler = new Pipeline();

        $middlewaresArray = $this->readAttribute($handler, 'queue');
        $this->assertSame([], $middlewaresArray);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Reached end of middleware queue. Does your controller return a response ?
     */
    public function testPipelineThrowExceptionIfQueueIsEmpty()
    {
        $handler = new Pipeline();

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

        $handler = new Pipeline();

        $handler->pipe($middleware_1);
        $handler->pipe($middleware_2);
        $handler->pipe($middleware_3);

        $middlewaresArray = $this->readAttribute($handler, 'queue');

        $this->assertSame([$middleware_1, $middleware_2, $middleware_3], $middlewaresArray);

        $response = $handler->handle($this->request);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('foobar', (string) $response->getBody());
    }
}
