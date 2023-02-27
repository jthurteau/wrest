<?php

declare(strict_types=1);

namespace Saf\Psr\Request;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Saf\Exception\Forward;

class ForwardHandler implements RequestHandlerInterface
{ //#TODO currently coded directly against Mezzio Router
    public const DEFAULT_MAX_COUNT = 2;

    protected static $baseRoute = '';
    protected static $router = null;
    protected static $forwardCount = 0;
    protected static $maxForwards = self::DEFAULT_MAX_COUNT;

    public function __construct(string $route)
    {
        if (!$route) {
            throw new \Exception('Unable to forward, no route set');
        }
    }

    public static function register($baseRoutePath, $router) //#TODO type control router
    {
        self::$baseRoute = $baseRoutePath;
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
        if (self::$forwardCount > self::$maxForwards) {
            throw new \Exception('Maximum internal forwards exceeded');
        } //#TODO PHP8 allows inlie throw
        try{
            $match = self::$router->match($request);
            if ($match->isSuccess()) {
                self::$forwardCount++;
                return $match->process($request, $this);
            }
            throw new \Exception('Unable to match route to handler');
        } catch(Forward $f) {
            self::reroute($f, $request);
        }         

    }

    /**
     * returns a new PSR ServerRequestInterface with the resourceStack attribute set 
     * based on a best guess. During Forwarding these don't get reset when sent back
     * through routing.
     */
    public static function routeStack(string $route, ServerRequestInterface $request) : ServerRequestInterface
    {
        $base = self::$baseRoute;
        $fullRoute = "{$base}/{$route}";
        $myRequest = $request->withUri($request->getUri()->withPath($fullRoute));
        $match = self::$router->match($myRequest);
        $routeObject = $match->getMatchedRoute();
        $baseMatch = self::withoutTrailingSlash(self::stacklessPath($routeObject->getPath()));
        $newPath = self::resourceStackBranch($baseMatch, $fullRoute);
        $stackedRequest = $myRequest->withAttribute('resourceStack', $newPath);
        return $stackedRequest;
    }

    /**
     * returns a FastRoute style routing path stripping out a greedy 
     * resourceStack parameter.
     */
    public static function stacklessPath(string $path) : string
    {
        $stackMatch = '[{resourceStack:.*}]';
        $stackIndex = strpos($path, $stackMatch);
        $tail = $stackIndex !== false ? substr($path, $stackIndex + strlen($stackMatch)) : '';
        return $stackIndex !== false ? (substr($path, 0, $stackIndex) . $tail) : $path;
    }

    public static function withoutTrailingSlash(string $path) : string
    {
        $trailingSlashMatch = '[/]';
        $trailingSlashIndex = strpos($path, $trailingSlashMatch);
        return $trailingSlashIndex === (strlen($path) - strlen($trailingSlashMatch)) ? (substr($path, 0, $trailingSlashIndex)) : $path;
    }

    public static function withoutInitialSlash(string $path) : string
    {
        return strpos($path, '/') === 0 ? substr($path, 1) : $path;
    }

    public static function resourceStackBranch($base, $full) :string
    {
        return 
            strpos($full, $base) === 0
            ? self::withoutInitialSlash(substr($full, strlen($base)))
            : $full;
    }

}
