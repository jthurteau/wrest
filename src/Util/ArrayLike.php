<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for list (array) manipulation
 */

namespace Saf\Util;

use Saf\Exception\NotAnArray;

require_once(dirname(__DIR__) . '/Exception/NoDefault.php');
require_once(dirname(__DIR__) . '/Exception/NotAnArray.php');

trait ArrayLike
{

    public static function traversable($maybeArray)
    {
        return
            is_array($maybeArray)
            || (is_object($maybeArray) && is_a($maybeArray, 'Traversable'));
    }

    /**
     * Tests for array compataiblity
     * @throws Saf\NotAnArray
     * @param mixed $array to test
     */
    public static function assert($array) : bool
    {
        if ( //#TODO PHP8 allows inline thrown
            !is_array($array) 
            && !($array instanceof \ArrayAccess)
            && !($array instanceof \Traversable)
        ){
            throw new NotAnArray();
        }
        return true;
    }
}