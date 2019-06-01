<?php

declare(strict_types=1);

namespace Chiron\Tests\Pipe;

use Chiron\Http\Psr\Response;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Pipe\Decorator\CallableMiddleware;
use Chiron\Pipe\Decorator\FixedResponseMiddleware;
use Chiron\Pipe\PipelineBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PipelineBuilderTest extends TestCase
{
    public $request;

    protected function setUp()
    {
        $this->request = new ServerRequest('GET', new Uri('/'));
    }

    public function testEmptyMiddlewareAfterInstanciation()
    {
        $builder = new PipelineBuilder();

        $middlewaresArray = $this->readAttribute($builder, 'middlewares');
        $this->assertSame([], $middlewaresArray);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Reached end of middleware stack. Does your controller return a response ?
     */
    public function testExecuteEmpty()
    {
        $builder = new PipelineBuilder();
        $response = $builder->dispatch($this->request);
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

        $builder = new PipelineBuilder();

        $builder->add($middlewares);

        $response = $builder->dispatch($this->request);

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

        $builder = new PipelineBuilder();

        $builder->add($middlewares);

        $response = $builder->dispatch($this->request);

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

        $builder = new PipelineBuilder($containerMock);

        $builder->add('middlewareName');
        $builder->add(new FixedResponseMiddleware(new Response(200)));

        $response = $builder->dispatch($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('foobar', (string) $response->getBody());
    }

    public function testWithOnlyOneBasicDecoratedResponse()
    {
        $response = new Response();
        $response->getBody()->write('EMPTY');
        $middleware = new FixedResponseMiddleware($response);

        $builder = new PipelineBuilder();
        $builder->add($middleware);

        $response = $builder->dispatch($this->request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('EMPTY', (string) $response->getBody());
    }

    public function testWithOnlyOneBasicResponse()
    {
        $response = new Response();
        $response->getBody()->write('SUCCESS');

        $builder = new PipelineBuilder();
        $builder->add($response);

        $result = $builder->dispatch($this->request);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('SUCCESS', (string) $result->getBody());
    }

    public function testRequestHandlerDecorated()
    {
        $response = new Response();
        $response->getBody()->write('SUCCESS');

        $handlerMock = $this->createMock(RequestHandlerInterface::class);

        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($response);

        $builder = new PipelineBuilder();
        $builder->add($handlerMock);

        $result = $builder->dispatch($this->request);

        $this->assertSame($response, $result);
        $this->assertEquals('SUCCESS', (string) $result->getBody());
    }

    public function testPipeOnTopMiddleware()
    {
        $response = new Response();
        $response->getBody()->write('FIRST MIDDLEWARE');
        $middleware_1 = new FixedResponseMiddleware($response);

        $response = new Response();
        $response->getBody()->write('MOVE ON TOP');
        $middleware_2 = new FixedResponseMiddleware($response);

        $builder = new PipelineBuilder();
        $builder->add($middleware_1);
        $builder->addOnTop($middleware_2);

        $middlewaresArray = $this->readAttribute($builder, 'middlewares');

        $this->assertSame($middleware_2, $middlewaresArray[0]);
    }

    public function testPipeOnTopMiddlewareArray()
    {
        $response = new Response();
        $response->getBody()->write('FIRST MIDDLEWARE');
        $middleware_1 = new FixedResponseMiddleware($response);

        $response = new Response();
        $response->getBody()->write('MOVE ON TOP_1');
        $middleware_2 = new FixedResponseMiddleware($response);

        $response = new Response();
        $response->getBody()->write('MOVE ON TOP_2');
        $middleware_3 = new FixedResponseMiddleware($response);

        $response = new Response();
        $response->getBody()->write('MOVE ON TOP_3');
        $middleware_4 = new FixedResponseMiddleware($response);

        $builder = new PipelineBuilder();
        $builder->add($middleware_1);
        $builder->addOnTop([$middleware_2, $middleware_3, $middleware_4]);

        $middlewaresArray = $this->readAttribute($builder, 'middlewares');

        $this->assertSame($middleware_2, $middlewaresArray[0]);
        $this->assertSame($middleware_3, $middlewaresArray[1]);
        $this->assertSame($middleware_4, $middlewaresArray[2]);
        $this->assertSame($middleware_1, $middlewaresArray[3]);
    }

    public function testPipeAtBottomByDefaultMiddleware()
    {
        $response = new Response();
        $response->getBody()->write('FIRST MIDDLEWARE');
        $middleware_1 = new FixedResponseMiddleware($response);

        $response = new Response();
        $response->getBody()->write('MOVE AT BOTTOM');
        $middleware_2 = new FixedResponseMiddleware($response);

        $builder = new PipelineBuilder();
        $builder->add($middleware_1);
        $builder->add($middleware_2);

        $middlewaresArray = $this->readAttribute($builder, 'middlewares');

        $this->assertSame($middleware_2, $middlewaresArray[1]);
    }

    public function testFlushPipe()
    {
        $response = new Response();
        $response->getBody()->write('MIDDLEWARE');
        $middleware = new FixedResponseMiddleware($response);

        $builder = new PipelineBuilder();
        $builder->add($middleware);
        $builder->flush();

        $middlewaresArray = $this->readAttribute($builder, 'middlewares');

        $this->assertSame([], $middlewaresArray);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionWhenIncompatibleTypeIsUsed()
    {
        $builder = new PipelineBuilder();
        $builder->add(123456);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionWhenStringEmptyIsUsed()
    {
        $builder = new PipelineBuilder();
        $builder->add('');
    }
}
