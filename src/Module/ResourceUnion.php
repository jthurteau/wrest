<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Trait for Resources that can access protected Front methods by proxy closure
 */

namespace Saf\Module;


trait ResourceUnion {

    public static function getFront(array $config):? object
    {
        $frontAccess = key_exists('front', $config) ? $config['front'] : null;
        return is_callable($frontAccess) ? $frontAccess() : $frontAccess;
    }

    public static function proxyFront(array $config)//:callable
    {
        $frontAccess = key_exists('front', $config) ? $config['front'] : null;
        return is_callable($frontAccess) ? $frontAccess : null ;
    }

}