<?php

declare(strict_types=1);

namespace Saf\Framework\Mode\Mezzio;

use Saf\Agent;

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
        $options = $currentAgent ? $currentAgent->env() : [];
        return key_exists('shell', $options) ? $options['shell']() : $options;
    }
}