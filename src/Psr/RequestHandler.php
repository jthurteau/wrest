<?php

declare(strict_types=1);

namespace Saf\Psr;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Plates\PlatesRenderer;
use Mezzio\Template\TemplateRendererInterface;
use Saf\Util\Layout;

use Saf\Psr\RequestHandlerCommon;
use Saf\Exception\Redirect;
use Saf\Exception\Forward;
use Saf\Auto;

abstract class RequestHandler implements RequestHandlerInterface
{
    use RequestHandlerCommon;

    public const STACK_ATTRIBUTE = 'resourceStack';
    public const DEFAULT_REQUEST_SEARCH = 'apg';
    public const MESSAGE_UNSUPPORTED = 'unsupported-request';
    public const MESSAGE_DENIED = 'access denied';

    protected $baseUri = '/';
    protected $template = null;
    protected $dictionary = null;
    protected $ems = null;
    protected $db = null;
    protected $emsDb = null;
    protected $sisDb = null;
    protected $spaceAuth = null;
    protected $accessList = [
        'open' => [],
        'key' => [],
        'any-user' => [],
        'admin-role' => ['*'],
        'sysAdmin-role' => ['*'],
    ];

    // public function __construct($a)
    // {
        
    // }

    public static function defaultRequestSearchOrder() : string
    {
        return self::DEFAULT_REQUEST_SEARCH;
    }

    public static function stackAttributeField() : string
    {
        return self::STACK_ATTRIBUTE;
    }

    public function prehandle(ServerRequestInterface $request)
    {

    }

    protected function handleFunction($function, $resourceStack, $request, &$status)
    {
        $method = "{$function}Process";
        try {
            if (Auto::validMethodName($method) && method_exists($this, $method)) {
                return $this->$method($resourceStack, $request, $status);
            } else {
                $status = 400;
                //die(\Saf\Debug::stringR(__FILE__,__LINE__,$function,get_class($this),$method,$request->getUri(),$request->getAttribute('resourceStack')));
                return [
                    'success' => false, 
                    'message' => $this->translate(self::MESSAGE_UNSUPPORTED)
                ];
            }
        } catch (\Error | \Exception $e) {
            if (is_a($e, Redirect::class) || is_a($e, Forward::class)) { //#TODO PHP 8 inline throw
                throw $e;
            }
            $status = 500;
            return [
                'success' => false, 
                'message' => $e->getMessage(),
                'meditation' => $e
            ];
        }
    }

    public function setBaseUri(string $base)
    {
        $this->baseUri = $base;
        Layout::setBaseUri($base);
    }

    public function setTemplate(TemplateRendererInterface $template)
    {
        $this->template = $template;
    }

    public function setTranslator(callable $translator)
    {
        $this->dictionary = $translator;
    }

    public function translate(string $string) : string
    {
        return is_callable($this->dictionary) ? ($this->dictionary)($string) : $string;
    }

}
