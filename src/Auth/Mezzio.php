<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Class for adapting Mezzio Auth
 */

namespace Saf\Auth;

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Saf\Agent;
use Saf\Psr\Request;
use Saf\Utils\Template;

class Mezzio implements AuthenticationInterface
{

    public function authenticate(ServerRequestInterface $request): ?UserInterface
    {
        return null;
    }

    public function unauthorizedResponse(ServerRequestInterface $request): ResponseInterface
    {
        $agentId = $request->getAttribute('agent');
        $message = 'Access Denied';

        $canister = Agent::lookup($agentId)->env();
        $canister += [
            'title' => 'Application Error',
            'helpText' => $message,
        ];     
        $response = 
            Request::isJson($request) 
            ? new JsonResponse(
                [
                    'success' => false, 
                    'message' => $message
                ] 
            ) : new HtmlResponse(Template::render($canister['installPath'] . '/src/views/gateway.php', $canister));
        return $response->withStatus('401');
    }
}