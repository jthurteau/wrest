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
    protected ?TemplateRendererInterface $template = null;

    public function __construct($renderer)
    {
        $this->template = $renderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $location = $request->getAttribute('location');
        if (!$location) {
            throw new \Exception('Unable to redirect, no location set');
        }
        $status = $request->getAttribute('permanentRedirect') ? 301 : 303; //#TODO figure out when to use 302 for older agents
        $htmlTemplate = $request->getAttribute('template', 'app::redirect');
        $useHtml = $request->getHeader('Accept');
        $interrupt = true; //Debug::isEnabled();
        $response = 
            $useHtml
            ? new HtmlResponse($this->template->render($htmlTemplate, [
                'location' => $location,
                'interceptedText' => $interrupt ? '(intercepted by Debug Mode)' : '',
                'hostUri' => self::autoHost($request),
                'requestScheme' => $request->getUri()->getScheme(),
            ]))
            : new JsonResponse([
                'success' => true,
                'continue' => $location,
            ]);
        return $interrupt ? $response : $response->withStatus($status);
    }

    protected static function autoHost(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $requestPort = $uri->getPort();
        $port = 
            $scheme == 'https'
            ? ($requestPort == '443' ? '' : ":{$requestPort}")
            : ($requestPort == '80' ? '' : ":{$requestPort}");
        return "{$scheme}://{$host}{$port}";
    }
}
