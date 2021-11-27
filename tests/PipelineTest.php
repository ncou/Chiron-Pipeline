<?php

declare(strict_types=1);

namespace Chiron\Pipeline\Tests;

use Chiron\Container\Container;
use Chiron\Container\ContainerAwareInterface;
use Chiron\Pipeline\Exception\PipelineException;
use Chiron\Pipeline\Pipeline;
use Chiron\Pipeline\Tests\Fixtures\CallableMiddleware;
use Chiron\Pipeline\Tests\Fixtures\CallableRequestHandler;
use Chiron\Pipeline\Tests\Fixtures\EmptyMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;
use Psr\EventDispatcher\EventDispatcherInterface;

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
        $pipeline = $this->makePipeline();

        $this->assertInstanceOf(RequestHandlerInterface::class, $pipeline);
    }

    public function testPipelineThrowExceptionIfQueueIsEmpty()
    {
        $pipeline = $this->makePipeline();

        $this->expectException(PipelineException::class);
        $this->expectExceptionMessage('Pipeline reached end of middleware queue and failed to return a response.');

        $pipeline->handle($this->request);
    }

    public function testPipelineThrowExceptionIfMiddlewareDoesntReturnAResponse()
    {
        $pipeline = $this->makePipeline();

        $pipeline->pipe(new EmptyMiddleware());

        $this->expectException(PipelineException::class);
        $this->expectExceptionMessage('Pipeline reached end of middleware queue and failed to return a response.');

        $pipeline->handle($this->request);
    }

    public function testPipeMiddlewaresWithLastMiddlewareReturnResponse()
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

        $middleware_3 = new CallableMiddleware(function ($request, $handler) {
            return new Response(202);
        });

        $pipeline = $this->makePipeline();

        $pipeline->pipe($middleware_1);
        $pipeline->pipe($middleware_2);
        $pipeline->pipe($middleware_3);

        $response = $pipeline->handle($this->request);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('foobar', (string) $response->getBody());
    }

    public function testPipeMiddlewaresWithHandlerReturnResponse()
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

        $fallback = new CallableRequestHandler(function ($request) {
            return new Response(202);
        });

        $pipeline = $this->makePipeline();

        $pipeline->pipe($middleware_1);
        $pipeline->pipe($middleware_2);

        $pipeline->fallback($fallback);

        $response = $pipeline->handle($this->request);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('foobar', (string) $response->getBody());
    }

    public function testWithoutMiddlewaresAndWithHandlerReturnResponse()
    {
        $fallback = new CallableRequestHandler(function ($request) {
            return new Response(404);
        });

        $pipeline = $this->makePipeline();

        $pipeline->fallback($fallback);

        $response = $pipeline->handle($this->request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    private function makePipeline(): Pipeline
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        return new Pipeline($eventDispatcher);
    }
}
