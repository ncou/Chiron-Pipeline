<?php

declare(strict_types=1);

namespace Chiron\Pipe\Decorator;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

//use Zend\Stratigility\Exception;

/**
 * Decorate callable middleware as PSR-15 middleware.
 *
 * Decorates middleware with the following signature:
 *
 * <code>
 * function (
 *     ServerRequestInterface $request,
 *     RequestHandlerInterface $handler
 * ) : ResponseInterface
 * </code>
 *
 * such that it will operate as PSR-15 middleware.
 *
 * Neither the arguments nor the return value need be typehinted; however, if
 * the signature is incompatible, a PHP Error will likely be thrown.
 */
final class FixedResponseMiddleware implements MiddlewareInterface
{
    /**
     * fixed response to return.
     *
     * @var ResponseInterface
     */
    private $fixedResponse;

    public function __construct(ResponseInterface $response)
    {
        $this->fixedResponse = $response;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\MissingResponseException if the decorated middleware
     *                                            fails to produce a response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->fixedResponse;
    }
}
