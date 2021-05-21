<?php

namespace Main;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Module implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        throw new \Saf\Exception\Inspectable($request->getRequestTarget(), $request->getUri());
        $response = $handler->handle($request);
        return $response;
    }
}
