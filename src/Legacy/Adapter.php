<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for migration adapters
 */

namespace Saf\Legacy;

abstract class Adapter {

    abstract public function __call($name, $args);

    abstract public static function __callStatic($name, $args);

    protected static function error($class, bool $isStatic, $method){
        $sep = $isStatic ? '::': '->';
    	throw new \Exception("Unimplemented legacy adapter {$class}{$sep}{$method}");
    }

}