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
use Saf\Psr\Request;
use Saf\Auth as Front;
use Saf\Auth\User\Mezzio as User;
use Saf\Agent;
use Saf\Utils\Template;

class Mezzio implements AuthenticationInterface
{
    protected static $singleton = null;

    public function authenticate(ServerRequestInterface $request): ?UserInterface
    {
        $authenticatedUsername = Front::authenticate($request);
        if (!$authenticatedUsername && !Front::allowGuest()) {
            return null;
        }
        return new User($authenticatedUsername);
    }

    public function unauthorizedResponse(ServerRequestInterface $request): ResponseInterface
    {
        $agentId = $request->getAttribute('agent');
        $message = 'Access Denied';

        $canister = Agent::lookup($agentId)->env();
        $canister += [
            'title' => 'Application Error',
            'helpText' => $message,
            'fatalError' => new \Exception('Unable to authenticate request.')
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