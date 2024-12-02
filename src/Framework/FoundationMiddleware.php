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
use Mezzio\Template\TemplateRendererInterface; #TODO this shouldn't be hard coded for Mezzio
use Saf\Agent;
#use Saf\Framework\AutoPipe;
use Saf\Psr\Request\RedirectHandler;
use Saf\Psr\Request\ForwardHandler;
use Saf\Psr\Container;
use Saf\Exception\Redirect;
use Saf\Exception\Forward;

class FoundationMiddleware implements MiddlewareInterface
{ //#TODO currently coded directly against Mezzio Router
    protected static $router = null;
    protected static $baseRoute = '/';
    protected static $renderer = null;

    public static function register($app, $container)
    {
        self::$router = $container->get(\Mezzio\Router\RouterInterface::class);
        self::$baseRoute = AutoPipe::baseRoute($app, $container);
        self::$renderer = Container::getOptionalService($container, TemplateRendererInterface::class, null);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {

        \Saf\Util\Profile::ping('foundation pipeline entered');
        \Saf\Debug::sessionReadyListner(); // auth is before this section of the pipe and that seems to be what starts the session,

        try {
            ForwardHandler::register(self::$baseRoute, self::$router);
            try {
                return $handler->handle($request->withAttribute('agent', Agent::last()));
            }  catch (Forward $f) {
                return ForwardHandler::reroute($f, $request);
            }
        } catch (Redirect $r) {
            $redirected = 
                $request
                ->withAttribute('location', $r->getMessage())
                ->withAttribute('permanentRedirect', $r->isPermanent())
                ->withAttribute('redirectMethod', $r->isAutomatic() ? Redirect::METHOD_HEADER : Redirect::METHOD_BODY);
            return (new RedirectHandler(self::$renderer))->handle($redirected);
        }
    }

}