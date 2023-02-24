<?php

declare(strict_types=1);

namespace Saf\Framework\Mode\Mezzio;

use Saf\Agent;
use Saf\Util\Handler\CalendarHandler;
use Saf\Psr\RequestHandlerFactory;

require_once(dirname(dirname(dirname(__DIR__))) . '/Agent.php');

/**
 * The configuration provider for an Saf managed Application Instance
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        $currentAgent = Agent::lookup(Agent::last());
        $options =& $currentAgent ? $currentAgent->env() : [];
        $options['dependencies'] = $this->getDependencies();
        $options['templates'] = $this->getTemplates();
        return 
            (is_array($options) && key_exists('shell', $options)) 
                || (
                    is_object($options) 
                    && method_exists($options, 'offsetExists') 
                    && $options->offsetExists('shell')
                )
            ? $options['shell']() 
            : $options;
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                CalendarHandler::class => RequestHandlerFactory::class,
            ]
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'saf'    => [__DIR__ . '/../../../templates/'],
            ]
        ];
    }
}