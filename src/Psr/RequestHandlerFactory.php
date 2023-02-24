<?php

declare(strict_types=1);

namespace Saf\Psr;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mezzio\Template\TemplateRendererInterface;
use Saf\Psr\Container;
use Saf\Utils\Breadcrumb;

class RequestHandlerFactory
{

    public function __invoke(ContainerInterface $container, string $serviceName)//, callable $callback)
    {
        if (!strpos($serviceName, 'Saf\\Util\\Handler\\') === 0) {
            throw new \Exception('Invalid Route Factory');
        }
        $options = self::prepare($serviceName, $container);
        $handler = new $serviceName($options);
        
        self::decorate($handler, $container);
        return $handler;
    }

    protected static function decorate(RequestHandlerInterface $handler, ContainerInterface $container)
    {
        $defaults = Container::getOptional($container, ['config','viewDefault'], []);
        $language = Container::getOptional($container, ['config','language'], []);
        $notice = Container::getOptional($container, ['config','noticeBanner'], null);
        $breadcrumbs = Container::getOptional($container, ['config','defaultBreadCrumbs'], null);
        $renderer = Container::getOptionalService($container, TemplateRendererInterface::class, null);
        foreach($defaults as $defaultField => $defaultValue) {
            $renderer->addDefaultParam($renderer::TEMPLATE_ALL, $defaultField, $defaultValue);
        }
        $languageCallback = function($string) use ($language) {
            return key_exists($string, $language) ? $language[$string] : $string;
        };
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 't', $languageCallback);
        if ($notice) {
            $notice = 
                is_string($notice)
                ? [
                    'class' => 'event',
                    'iconText' => 'Information',
                    'icon' => null,
                    'link' => null,
                    'text' => $notice
                ] : (
                    $notice + [
                        'class' => 'event',
                        'iconText' => 'Information',
                        'icon' => null,
                        'link' => null,
                        'text' => 'Notice'
                    ]
                );
            if (!key_exists('class',$notice)) {
                $notice['class'] = 'event';
            }
            $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'notice', $notice);
        }
        $baseUri = Container::getOptional($container, ['config','baseUri'], '/');
        $handler->setBaseUri($baseUri);
        if ($breadcrumbs) {
            Breadcrumb::init($breadcrumbs, $baseUri);
            $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'breadcrumbs', Breadcrumb::link());
        }
        $handler->setTemplate($renderer);
        $handler->setTranslator($languageCallback);
    }

    protected static function prepare(string $serviceName, ContainerInterface $container)
    {
        $return = [];
        if(method_exists($serviceName, 'expects')) {
            foreach($serviceName::expects() as $key => $search) {
                $return[$key] = Container::getOptional($container, $search);
            }
        }
        return $return;
    }
}
