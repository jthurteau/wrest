<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Introspection Utility for Saf\Debug
 */

namespace Saf\Utils\Debug;

use Saf\Debug;

class Analysis 
{
    public static function data($message)
    {
        return Debug::stringR($message);
    }

}