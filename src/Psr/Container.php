<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility Class for PSR11
 */

namespace Saf\Psr;

use Psr\Http\Message\ServerRequestInterface;

class Container {

    protected static $returnObject = false;

    public static function getOptional($container, $name, $default = null)
    {
        if ($container && $container->has($name)) {
            return self::filter($container->get($name));
        }
        return $default;
    }

    public static function filter($returnValue)
    {
        return self::$returnObject ? $returnValue : $returnValue->getArrayCopy();
    }
}