<?php

declare(strict_types=1);

namespace Chiron\Tests\Pipe;

use Chiron\Http\Psr\Response;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Pipe\Decorator\FixedResponseMiddleware;
use Chiron\Pipe\PipelineBuilder;
use Chiron\Pipe\Pipeline;
use PHPUnit\Framework\TestCase;

class PipelineBuilderTest extends TestCase
{
    public $request;

    protected function setUp()
    {
        $this->request = new ServerRequest('GET', new Uri('/'));
    }

    public function testEmptyMiddlewareStackAfterFirstInstanciation()
    {
        $builder = new PipelineBuilder();

        $middlewaresArray = $this->readAttribute($builder, 'stack');
        $this->assertSame([], $middlewaresArray);
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

        $middlewaresArray = $this->readAttribute($builder, 'stack');

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

        $middlewaresArray = $this->readAttribute($builder, 'stack');

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

        $middlewaresArray = $this->readAttribute($builder, 'stack');

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

        $middlewaresArray = $this->readAttribute($builder, 'stack');

        $this->assertSame([], $middlewaresArray);
    }

    public function testBuildPipeline()
    {
        $builder = new PipelineBuilder();
        $handler = $builder->build();

        $this->assertInstanceOf(Pipeline::class, $handler);
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
