<?php

declare(strict_types=1);

namespace Saf\Psr\Request;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Saf\Psr\Container;

class ForwardHandler implements RequestHandlerInterface
{ //#TODO currently coded directly against Mezzio Router
    public const MAX_COUNT = 2;

    protected $route = '';
    protected static $baseRoute = '';
    protected static $router = null;
    protected static $forwardCount = 0;

    public function __construct(string $route)
    {
        if (!$route) {
            throw new \Exception('Unable to forward, no route set');
        }
        $this->route = $route;
    }

    public static function register($base, $router)
    {
        self::$baseRoute = $base;
        self::$router = $router;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $base = self::$baseRoute;
        $myRequest = $request->withUri($request->getUri()->withPath("{$base}/{$this->route}"));
        $match = self::$router->match($myRequest);
        if ($match->isSuccess()) {
            self::$forwardCount++;
            if (self::$forwardCount > self::MAX_COUNT) {
                throw new \Exception('Maximum internal forwards exceeded');
            }
            return $match->process($myRequest, $this);
        }
        throw new \Exception('Unable to match route to handler');
    }

}
