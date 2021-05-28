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
        if (is_array($name)) {
            $trunk = array_shift($name);
            $branch = self::getOptional($container, $trunk, $default);
            foreach($name as $next) {
                if(is_array($branch) && key_exists($next, $branch)){
                    $branch = $branch[$next];
                } else {
                    return $default;
                }
            }
            return $branch;
        }
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