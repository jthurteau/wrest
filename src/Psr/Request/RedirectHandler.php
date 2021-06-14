<?php

declare(strict_types=1);

namespace Saf\Psr\Request;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Plates\PlatesRenderer;

use Saf\Debug;

class RedirectHandler implements RequestHandlerInterface
{

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $location = $request->getAttribute('location');
        if (!$location) {
            throw new \Exception('Unable to redirect, no location set');
        }
        $status = $request->getAttribute('permanentRedirect') ? 301 : 303; //#TODO figure out when to use 302 for older agents
        $htmlTemplate = $request->getAttribute('template', 'app::redirect');
        $useHtml = $request->getHeader('Accept');
        $interrupt = Debug::isEnabled();
        $response = 
            $useHtml
            ? new HtmlResponse($this->template->render($htmlTemplate, [
                'location' => $location,
            ]))
            : new JsonResponse([
                'success' => true,
                'continue' => $location,
            ]);
        return $interrupt ? $response : $response->withStatus($status);
    }
}
