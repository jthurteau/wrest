<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Pipe Middleware Manager
 */

namespace Saf\Framework;

use Mezzio\Application as Mezzio;
use Psr\Container\ContainerInterface;
use Main\Module as Main;

class AutoPipe 
{

    public static function mezzio(Mezzio $app, ContainerInterface $container)
    {
        //#TODO main pipe is implemented in routes for now since the routing 
        //# (FastRoute) implementation does not handle it properly and 
        //# laminas-router uses an unsupported component
        $pipes = self::getPipes($container);
        $main = self::getMain($pipes);
        //throw new \Saf\Exception\Inspectable($main, $pipes);
        if ($main) { #NOTE the main pipe allows serving the application from sub-folders of the docroot
            $app->pipe("/{$main}", Main::class);
        }
    }

    public static function baseRoute($app, ContainerInterface $container)
    {
        $pipes = self::getPipes($container);
        if (is_null($pipes)) {
            //throw new Exception('No pipes defined by the foundation.');
            trigger_error('No pipes defined by the foundation.', E_USER_NOTICE);
        }
        $main = self::getMain($pipes);
        return $main ? "/{$main}" : '';
    }

    protected static function getPipes(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : null;
        // #TODO see above
        $pipes = 
            $config && is_object($config) && $config->offsetExists('pipes')
            ? $config->offsetGet('pipes') 
            : (
                $config && is_array($config) && key_exists('pipes', $config)
                ? $config['pipes']
                : null
            );
        if (is_null($pipes)) {
            //throw new Exception('No pipes defined by the foundation.');
            trigger_error('No pipes defined by the foundation.', E_USER_NOTICE);
        }
        return $pipes;
    }

    protected static function getMain($pipeConfig)
    {
        return 
            is_array($pipeConfig) 
                && key_exists('main', $pipeConfig) 
            ? $pipeConfig['main'] 
            : null;
    }

}