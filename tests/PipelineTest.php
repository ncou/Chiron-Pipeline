<?php

declare(strict_types=1);

namespace Chiron\Pipeline\Tests;

use Chiron\Pipeline\Tests\Fixtures\CallableMiddleware;
use Chiron\Pipeline\Tests\Fixtures\CallableRequestHandler;
use Chiron\Pipeline\Pipeline;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Pipeline\Tests\Fixtures\EmptyMiddleware;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Chiron\Container\Container;
use Chiron\Pipeline\Exception\PipelineException;
use Chiron\Container\ContainerAwareInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function testPipelineThrowExceptionIfQueueIsEmpty()
    {
        $handler = new Pipeline(new Container());

        $this->expectException(PipelineException::class);
        $this->expectExceptionMessage('Pipeline reached end of middleware queue and failed to return a response');

        $handler->handle($this->request);
    }

    public function testPipelineThrowExceptionIfMiddlewareDoesntReturnAResponse()
    {
        $handler = new Pipeline(new Container());

        $handler->pipe(new EmptyMiddleware());

        $this->expectException(PipelineException::class);
        $this->expectExceptionMessage('Pipeline reached end of middleware queue and failed to return a response');

        $handler->handle($this->request);
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

        $handler = new Pipeline(new Container());

        $handler->pipe($middleware_1);
        $handler->pipe($middleware_2);
        $handler->pipe($middleware_3);

        $response = $handler->handle($this->request);

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

        $handler = new Pipeline(new Container());

        $handler->pipe($middleware_1);
        $handler->pipe($middleware_2);

        $handler->fallback($fallback);

        $response = $handler->handle($this->request);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('foobar', (string) $response->getBody());
    }

    public function testWithoutMiddlewaresAndWithHandlerReturnResponse()
    {
        $fallback = new CallableRequestHandler(function ($request) {
            return new Response(404);
        });

        $handler = new Pipeline(new Container());

        $handler->fallback($fallback);

        $response = $handler->handle($this->request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testContainerIsInjectedIfNotAlreadyPresents()
    {
        $middleware = new CallableMiddleware(function ($request, $handler) {
            return $handler->handle($request);
        });

        $fallback = new CallableRequestHandler(function ($request) {
            return new Response();
        });

        $container = new Container();
        $handler = new Pipeline($container);

        $this->assertFalse($middleware->hasContainer());
        $this->assertFalse($fallback->hasContainer());

        $handler->pipe($middleware);
        $handler->fallback($fallback);

        $this->assertTrue($middleware->hasContainer());
        $this->assertTrue($fallback->hasContainer());

        $this->assertSame($container, $this->reflectContainer($middleware));
        $this->assertSame($container, $this->reflectContainer($fallback));
    }

    public function testContainerIsNotInjectedIfAlreadyPresents()
    {
        $middleware = new CallableMiddleware(function ($request, $handler) {
            return $handler->handle($request);
        });

        $fallback = new CallableRequestHandler(function ($request) {
            return new Response();
        });

        $container = new Container();
        $handler = new Pipeline($container);

        $containerNew = new Container();
        $middleware->setContainer($containerNew);
        $fallback->setContainer($containerNew);

        $handler->pipe($middleware);
        $handler->fallback($fallback);

        $this->assertSame($containerNew, $this->reflectContainer($middleware));
        $this->assertSame($containerNew, $this->reflectContainer($fallback));
    }

    private function reflectContainer(ContainerAwareInterface $containerAwareInstance): Container
    {
        $r = new \ReflectionProperty($containerAwareInstance, 'container');
        $r->setAccessible(true);

        return $r->getValue($containerAwareInstance);
    }

    public function testBindLatestRequestInContainer()
    {
        $container = new Container();

        $middleware_1 = new CallableMiddleware(function ($request, $handler) use ($container) {
            $this->assertSame($request, $container->get(ServerRequestInterface::class));
            $request = $request->withAttribute('foo', true);

            return $handler->handle($request);
        });

        $middleware_2 = new CallableMiddleware(function ($request, $handler) use ($container) {
            $this->assertSame($request, $container->get(ServerRequestInterface::class));
            $request = $request->withAttribute('bar', true);

            return $handler->handle($request);
        });

        $fallback = new CallableRequestHandler(function ($request) use ($container) {
            $this->assertSame($request, $container->get(ServerRequestInterface::class));
            $this->assertTrue($container->get(ServerRequestInterface::class)->getAttribute('foo'));
            $this->assertTrue($container->get(ServerRequestInterface::class)->getAttribute('bar'));

            return new Response();
        });

        $handler = new Pipeline($container);

        $handler->pipe($middleware_1);
        $handler->pipe($middleware_2);

        $handler->fallback($fallback);

        $this->assertFalse($container->has(ServerRequestInterface::class));

        $response = $handler->handle($this->request);

        $this->assertTrue($container->has(ServerRequestInterface::class));
        $this->assertTrue($container->get(ServerRequestInterface::class)->getAttribute('foo'));
        $this->assertTrue($container->get(ServerRequestInterface::class)->getAttribute('bar'));
    }

}
