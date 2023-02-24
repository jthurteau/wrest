<?php

declare(strict_types=1);

namespace Saf\Psr\Request;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Saf\Psr\Container;
use Saf\Exception\Forward;

class ForwardHandler implements RequestHandlerInterface
{ //#TODO currently coded directly against Mezzio Router
    public const MAX_COUNT = 2;

    protected static $baseRoute = '';
    protected static $router = null;
    protected static $forwardCount = 0;

    //protected $route = '';

    public function __construct(string $route)
    {
        if (!$route) {
            throw new \Exception('Unable to forward, no route set');
        }
        //$this->route = $route;
    }

    public static function register($base, $router)
    {
        self::$baseRoute = $base;
        self::$router = $router;
    }

    public static function reroute(Forward $f, ServerRequestInterface $request) : ResponseInterface
    {
        $forwardRoute = $f->getMessage();
        $originalRequest = 
            $f->hasRequest()
            ? $f->getRequest()
            : $request;
        $forwardedRequest = self::routeStack($forwardRoute, $originalRequest);
        return (new ForwardHandler($forwardRoute))->handle($forwardedRequest);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try{
            $match = self::$router->match($request);
            if ($match->isSuccess()) {
                self::$forwardCount++;
                if (self::$forwardCount > self::MAX_COUNT) {
                    throw new \Exception('Maximum internal forwards exceeded');
                }
                return $match->process($request, $this);
            }
            throw new \Exception('Unable to match route to handler');
        } catch(Forward $f) {
            self::reroute($f, $request);
        }         

    }


    public static function routeStack(string $route, ServerRequestInterface $request) : ServerRequestInterface
    {
        $base = self::$baseRoute;
        $fullRoute = "{$base}/{$route}";
        $myRequest = $request->withUri($request->getUri()->withPath($fullRoute));
        $match = self::$router->match($myRequest);
        $routeObject = $match->getMatchedRoute();
        $baseMatch = self::withoutTrailingSlash(self::stacklessPath($routeObject->getPath()));
        $newPath = self::resourceStackBranch($baseMatch, $fullRoute);
        // print_r([
        //     __FILE__,__LINE__, 'base' => $base, 'route' => $route, 
        //     'routeObjectPath' => $routeObject->getPath(), 
        //     'clean' => $baseMatch, 'branched' => $newPath
        // ]); //die;
        $stackedRequest = $myRequest->withAttribute('resourceStack', $newPath);
        return $stackedRequest;
    }

    // public static function truncate()
    // {
    //     //stackDepth
    // }

    public static function stacklessPath(string $path)
    {
        $stackMatch = '[{resourceStack:.*}]';
        $stackIndex = strpos($path, $stackMatch);
        $tail = $stackIndex !== false ? substr($path, $stackIndex + strlen($stackMatch)) : '';
        return $stackIndex !== false ? (substr($path, 0, $stackIndex) . $tail) : $path;
    }

    public static function withoutTrailingSlash(string $path)
    {
        $trailingSlashMatch = '[/]';
        $trailingSlashIndex = strpos($path, $trailingSlashMatch);
        return $trailingSlashIndex === (strlen($path) - strlen($trailingSlashMatch)) ? (substr($path, 0, $trailingSlashIndex)) : $path;
    }

    public static function withoutInitialSlash(string $path)
    {
        return strpos($path, '/') === 0 ? substr($path, 1) : $path;
    }

    public static function resourceStackBranch($base, $full)
    {
        return 
            strpos($full, $base) === 0
            ? self::withoutInitialSlash(substr($full, strlen($base)))
            : $full;
    }

    // public static function stackDepth($path)
    // {
    //     if (strpos($path, self::$baseRoute) === 0) {
    //         $branch = substr($path, strlen(self::$baseRoute));
    //         if ($branch) {
    //             print_r(['untested code',__FILE__,__LINE__,$path,self::$baseRoute,$branch]); die;
    //         }
    //         $parts = explode('/',$branch);
    //         return count($parts) - 1;
    //     }
    //     return 0;
    // }

}
