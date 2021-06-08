<?php

declare(strict_types=1);

namespace Chiron\Pipeline;

use Chiron\Container\Container;
use Chiron\Container\ContainerAwareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Chiron\Pipeline\Event\BeforeMiddleware;
use Chiron\Pipeline\Event\AfterMiddleware;

// TODO : exemple avec les événements !!!!
//https://github.com/yiisoft/middleware-dispatcher/blob/master/src/MiddlewareStack.php#L98

// TODO : vérifier qu'on a pas de doublons de middlewares : https://github.com/illuminate/routing/blob/f0908784ce618438be1a8b99f4613f62d18d8013/Router.php#L1256

/**
 * Attempts to handle an incoming request by doing the following:
 *
 * - Inject the container in the middleware or fallback handler if not already presents.
 * - Bind in the container the request instance to the ServerRequestInterface::class key.
 * - Cloning itself, to produce a request handler and increase the array index position.
 * - Processing the middleware in the queue (using the array index position) with the cloned handler.
 * - Using the fallback handler to return a default response (step skipped if the middleware queue return a response).
 *
 * The pipeline use a default fallback handler to throw a PipelineException for missing response reason.
 *
 * @see https://www.php-fig.org/psr/psr-15/
 * @see https://www.php-fig.org/psr/psr-15/meta/
 */
// TODO : actualiser la phpDoc et ajouter un @see sur les specs psr14 pour les events !!!!
final class Pipeline implements RequestHandlerInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;
    /** @var RequestHandlerInterface */
    private $fallback;
    /** @var int */
    private $position = 0; // TODO : renommer la variable en $index !!!!
    /** @var array<MiddlewareInterface> */
    private $middlewares = []; // TODO : renommer la variable en $queue ???

    /** @var callable */
    //public $beforeMiddleware = null; // TODO : code temporaire à virer !!!


    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->fallback = new EmptyPipelineHandler();
    }

    /**
     * @param MiddlewareInterface $middleware
     *
     * @return self
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param RequestHandlerInterface $handler
     *
     * @return self
     */
    public function fallback(RequestHandlerInterface $handler): self
    {
        $this->fallback = $handler;

        return $this;
    }

    /**
     * Bind a fresh ServerRequestInterface instance in the container.
     * Iterate and execute the middleare queue, then execute the fallback hander.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO : remplacer cette appel au binding par un événement "BeforeMiddleware" : exemple : https://github.com/yiisoft/middleware-dispatcher/blob/master/src/MiddlewareStack.php#L85
        // Attach a fresh instance of the request in the container.
        //$this->bindRequestInstance($request); // TODO : transformer ce bout de code en une propriété public de classe "$onHandle" de type callable qu'on executerait avant chaque Handle. Cela permettrait au package pipeline de ne plus avoir de dépendance avec le package container.

        // TODO : code temporaire le temps d'ajouter un event dispatcher !!!!
        /*
        if ($this->beforeMiddleware !== null) {
            call_user_func_array($this->beforeMiddleware, [$request]);
        }*/

        $middleware = $this->middlewares[$this->position] ?? null;

        //if (isset($this->middlewares[$this->position])) {
        if ($middleware !== null) {
            $this->dispatcher->dispatch(new BeforeMiddleware($middleware, $request));

            try {
                return $response = $middleware->process($request, $this->nextHandler());
            } finally {
                $this->dispatcher->dispatch(new AfterMiddleware($middleware, $response ?? null));
            }
        }

        return $this->fallback->handle($request);
    }

    /**
     * Store a "fresh" Request instance in the container.
     * Usefull if you need to retrieve some request attributes.
     *
     * Ex: in the CookieCollectionMiddleware with the CookieCollection::ATTRIBUTE
     *
     * @param ServerRequestInterface $request
     */
    /*
    private function bindRequestInstance(ServerRequestInterface $request): void
    {
        $this->container->bind(ServerRequestInterface::class, $request);
    }*/

    /**
     * Get a handler pointing to the next middleware position.
     *
     * @return RequestHandlerInterface New Pipeline instance used as handler.
     */
    private function nextHandler(): RequestHandlerInterface
    {
        $copy = clone $this;
        $copy->position++;

        return $copy;
    }
}
