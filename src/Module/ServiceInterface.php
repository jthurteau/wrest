<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing service modules
 */

namespace Saf\Module;

interface ServiceInterface
{

    public function init($config): ServiceInterface;

    public static function getConfigKey() : string;

}