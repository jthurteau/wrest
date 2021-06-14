<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Middleware for Framework integration, binds the Agent to the Request
 */

namespace Saf\Framework;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Saf\Agent;
#use Saf\Framework\AutoPipe;
use Saf\Psr\Request\RedirectHandler;
use Saf\Psr\Request\ForwardHandler;
use Saf\Exception\Redirect;
use Saf\Exception\Forward;

class FoundationMiddleware implements MiddlewareInterface
{ //#TODO currently coded directly against Mezzio Router
    protected static $router = null;
    protected static $baseRoute = '/';

    public static  function register($app, $container)
    {
        self::$router = $container->get(\Mezzio\Router\RouterInterface::class);
        self::$baseRoute = AutoPipe::baseRoute($app, $container);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        try {
            ForwardHandler::register(self::$baseRoute, self::$router);
            return $handler->handle($request->withAttribute('agent', Agent::last()));
        } catch (Redirect $r) {
            $redirected = 
                $request
                ->withAttribute('location', $r->getMessage())
                ->withAttribute('permanentRedirect', $r->isPermanent());
            return (new RedirectHandler())->handle($redirected);
        } catch (Forward $f) {
            $forwardRoute = $f->getMessage();
            $forwarded = 
                $f->hasRequest()
                ? $f->getRequest()
                : $request;
            return (new ForwardHandler($forwardRoute))->handle($forwarded);
        }
    }

}