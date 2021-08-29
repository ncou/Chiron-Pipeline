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
use Chiron\Pipeline\Event\BeforeMiddlewareEvent;
use Chiron\Pipeline\Event\AfterMiddlewareEvent;
use Chiron\Pipeline\Event\BeforeHandlerEvent;
use Chiron\Pipeline\Event\AfterHandlerEvent;

// TODO : exemple avec les événements !!!!
//https://github.com/yiisoft/middleware-dispatcher/blob/master/src/MiddlewareStack.php#L98

//https://github.com/middlewares/utils/blob/master/src/Dispatcher.php#L43

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
// TODO : ajouter des tests pour les events
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
        // TODO : ajouter des tests pour ce package notamment sur les événements et le comportement en cas de throw d'exception s'assurer que le finaly fonctionne correctement !!!


        $middleware = $this->middlewares[$this->position] ?? null;

        if ($middleware !== null) {
            $this->dispatcher->dispatch(new BeforeMiddlewareEvent($middleware, $request));
            try {
                return $response = $middleware->process($request, $this->nextHandler());
            } finally {
                $this->dispatcher->dispatch(new AfterMiddlewareEvent($middleware, $response ?? null));
            }
        }

        // TODO : eventuellement permettre de laisser le fallback à null et dans ce cas lever l'exception qui est présente dans la classe EmptyPipelineHandler directement à la fin de cette méthode. Ca évitera de conserver la classe EmptyPipelineHandler qui sera inutile !!!!
        $this->dispatcher->dispatch(new BeforeHandlerEvent($this->fallback, $request));
        try {
            return $response = $this->fallback->handle($request);
        } finally {
            $this->dispatcher->dispatch(new AfterHandlerEvent($this->fallback, $response ?? null));
        }
    }

    /**
     * Get a handler pointing to the next middleware position.
     *
     * @return RequestHandlerInterface New Pipeline instance used as handler.
     */
    // TODO : vérifier si le clone de cette classe ne posera pas de probléme lors du clone de la propriété $this->dispatcher qui est un objet !!!
    private function nextHandler(): RequestHandlerInterface
    {
        $copy = clone $this;
        $copy->position++;

        return $copy;
    }
}
