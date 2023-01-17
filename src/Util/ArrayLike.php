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

//     /**
//      * Serializes an array into a string using the formatting of print_r()
//      *
//      * @param array $array to serialize
//      * @return string representation of $array
//      */
//     public static function toString($array)
//     {
//         ob_start();
//         print_r($array);
//         $return = ob_get_contents();
//         ob_end_clean();
//         return $return;
//     }

//     protected static function introspectData($mixed, $provider = null)
//     //protected static function introspectData(mixed $mixed, $provider = null)
//     { #TODO this is also in debug and self::toString, so consolidate/improve
//         ob_start();
//         print_r($mixed);
//         $output = ob_get_contents();
//         ob_end_clean();
//         return $output;
//     }

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