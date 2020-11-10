<?php

declare(strict_types=1);

namespace Chiron\Pipeline;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chiron\Container\Container;
use Chiron\Injector\Injector;
use Chiron\Injector\Exception\InvocationException;
use Chiron\Http\Exception\Client\BadRequestHttpException;
use JsonSerializable;
use UnexpectedValueException;
use Chiron\Container\ContainerAwareTrait;
use Chiron\Container\ContainerAwareInterface;

// TODO : mieux gérer les exceptions dans le cas ou il y a une erreur lors du $injector->call()    exemple :   https://github.com/spiral/framework/blob/e63b9218501ce882e661acac284b7167b79da30a/src/Hmvc/src/AbstractCore.php#L67       +         https://github.com/spiral/framework/blob/master/src/Router/src/CoreHandler.php#L199

/**
 * Callback wraps arbitrary PHP callback into object matching [[MiddlewareInterface]].
 * Usage example:
 *
 * ```php
 * $middleware = new CallbackMiddleware(function(ServerRequestInterface $request, RequestHandlerInterface $handler) {
 *     if ($request->getParams() === []) {
 *         return new Response();
 *     }
 *     return $handler->handle($request);
 * });
 * $response = $middleware->process(Yii::$app->getRequest(), $handler);
 * ```
 *
 * @see MiddlewareInterface
 */
// TODO : corriger le phpdoc de la classe !!!!
class CallableHandler implements RequestHandlerInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var callable|array|string a PHP callback matching signature of [RequestHandlerInterface->handle(ServerRequestInterface $request)]]. // TODO : non c'est faux ce n'est pas obligatoirement une signature de type requesthandler !!!!
     */
    protected $callable;

    /**
     * @param callable|array|string $callable A PHP callback matching signature of [RequestHandlerInterface->handle(ServerRequestInterface $request)]]. // TODO : non c'est faux ce n'est pas obligatoirement une signature de type requesthandler !!!!
     */
    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->executeCallable($this->callable, $request->getAttributes());
    }

    protected function executeCallable($callable, $parameters): ResponseInterface
    {
        // TODO : faire un $this->hasContainer et si le résultat est false dans ce cas lever une une HandlerException en indiquant que le container doit être setter pour executer le handler ????
        /*
        if (! $this->hasContainer()) {
            throw new RouteException('Unable to configure route pipeline without associated container');
        }*/

        try {
            //https://github.com/PHP-DI/Slim-Bridge/blob/master/src/ControllerInvoker.php#L43
            $response = $this->getContainer()->call($callable, $parameters);
        } catch (InvocationException $e) {
            // TODO : améliorer le code pour permettre de passer en paramétre l'exception précédente ($e) à cette http exception
            // TODO : il faudrait surement lever une exception NotFoundHttpException dans le cas ou la mathode du callable n'existe pas dans la classe du callable, mais il faut pour cela séparer ce type d'excpetion dans la classe Injector pour ne pas remonter systématiquement une Exception InvocationException qui gére à la fois les probléme de callable qui n'existent pas et les callables qui n'ont pas le bon nombre d'arguments en paramétres.
            throw new BadRequestHttpException();
        }

        // TODO : il faudrait réussir via la reflexion à récupérer la ligne php ou se trouve le callable et utiliser ce file/line dans l'exception, ca serait plus simple à débugger !!! ou à minima si c'est un tableau on affiche le détail du tableau (qui sera au format, [class, 'method'])
        if (! $response instanceof ResponseInterface) {
            // TODO : retourner plutot une HandlerException ????
            throw new LogicException(sprintf(
                'Decorated callable request handler of type "%s" failed to produce a response.',
                is_object($callable) ? get_class($callable) : gettype($callable)
            ));
        }

        return $response;
    }
}
