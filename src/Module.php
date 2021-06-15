<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing service modules
 */

namespace Saf;

use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
use Saf\Module\ServiceInterface;

class Module
{

    protected static $loaded = [];

    public function __invoke(ContainerInterface $container, string $name, callable $callback)
    {
        $return = $callback();
        $source = 
            $name && class_exists($name) && class_implements($name, ServiceInterface::class)
            ? $name::getConfigKey()
            : $name;
        if (!$source) {
            throw new \Exception("Unable to load configuration for Service Module: {$name}");
        }
        $serviceConfig = Container::getOptional($container, ['config', $source], []);
        return $return->init($serviceConfig);
    }

 
}