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
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class PipelineTest extends TestCase
{
    public $request;

    protected function setUp()
    {
        $this->request = new ServerRequest('GET', new Uri('/'));
    }

    protected function tearDown()
    {
    }

    public function testPipeMiddlewares()
    {
        $middlewares = [
            new CallableMiddleware(function ($request, $handler) {
                $response = $handler->handle($request);
                $response->getBody()->write('bar');

                return $response;
            }),
            new CallableMiddleware(function ($request, $handler) {
                $response = $handler->handle($request);
                $response->getBody()->write('foo');

                return $response;
            }),
            new FixedResponseMiddleware(new Response(200)),
        ];

        $pipeline = new Pipeline();

        $pipeline->pipe($middlewares);

        $response = $pipeline->handle($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('foobar', (string) $response->getBody());
    }

    public function testPipeCallableMiddleware()
    {
        $middlewares = [
            function ($request, $handler) {
                $response = $handler->handle($request);
                $response->getBody()->write('bar');

                return $response;
            },
            function ($request, $handler) {
                $response = $handler->handle($request);
                $response->getBody()->write('foo');

                return $response;
            },
            new FixedResponseMiddleware(new Response(200)),
        ];

        $pipeline = new Pipeline();

        $pipeline->pipe($middlewares);

        $response = $pipeline->handle($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('foobar', (string) $response->getBody());
    }

    public function testPipeLazyMiddleware()
    {
        $middleware = new CallableMiddleware(function ($request, $handler) {
            $response = $handler->handle($request);
            $response->getBody()->write('foobar');

            return $response;
        });

        $containerMock = $this->createMock(ContainerInterface::class);

        $containerMock
            ->method('has')
            ->with('middlewareName')
            ->willReturn(true);

        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('middlewareName')
            ->willReturn($middleware);

        $pipeline = new Pipeline($containerMock);

        $pipeline->pipe('middlewareName');
        $pipeline->pipe(new FixedResponseMiddleware(new Response(200)));

        $response = $pipeline->handle($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('foobar', (string) $response->getBody());
    }

    public function testPipeIfMiddlewares()
    {
        $middlewareOne =
            new CallableMiddleware(function ($request, $handler) {
                $response = $handler->handle($request);
                $response->getBody()->write('bar');

                return $response;
            });

        $middlewareTwo = new CallableMiddleware(function ($request, $handler) {
            $response = $handler->handle($request);
            $response->getBody()->write('foo');

            return $response;
        });

        $pipeline = new Pipeline();

        $pipeline->pipeIf($middlewareOne, function () {
            return false;
        });
        $pipeline->pipeIf($middlewareTwo, function () {
            return true;
        });
        $pipeline->pipe(new FixedResponseMiddleware(new Response()));

        $response = $pipeline->handle($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('foo', (string) $response->getBody());
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Reached end of middleware stack. Does your controller return a response ?
     */
    public function testExceptionWhenNoResponse()
    {
        $pipeline = new Pipeline();

        $response = $pipeline->handle($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('foo', (string) $response->getBody());
    }

    public function testWithOnlyOneBasicResponse()
    {
        $response = new Response();
        $response->getBody()->write('EMPTY');

        $pipeline = new Pipeline();
        $pipeline->pipe(new FixedResponseMiddleware($response));

        $response = $pipeline->handle($this->request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('EMPTY', (string) $response->getBody());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionWhenIncompatibleTypeIsUsed()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(123456);
    }
}
