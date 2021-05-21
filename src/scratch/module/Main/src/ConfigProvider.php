<?php

declare(strict_types=1);

namespace Main;

/**
 * The configuration provider for the Main module
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
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [
                Module::class => Module::class,
            ],
            // 'factories'  => [
            //     Handler\HomePageHandler::class => Handler\HomePageHandlerFactory::class,
            // ],
        ];
    }

    /**
     * Returns the templates configuration
     */
    public function getTemplates(): array
    {
        return [
            // 'extension' => 'php',
            // 'paths' => [
            //     'error' => [__DIR__ . '/../templates/error'],
            //     'layout' => [__DIR__ . '/../templates/layout'],
            // ],
        ];
    }
}
