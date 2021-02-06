<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for managing framework instances
 */

namespace Saf\Framework;

abstract class Manager{

    abstract public static function detect($instance);
    
    public static function run($instance, $options = null);

}