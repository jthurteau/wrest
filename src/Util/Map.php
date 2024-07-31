<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for map (array) manipulation
 */

namespace Saf\Util;

use Saf\Hash;
//use Saf\Exception\NotAnArray;

abstract class Map
{
    /**
     * Checks an array for the existance of one or more keys in an array
     * For any values in $keys that are valid keys (not an Array or Object),
     * true indicates all such keys are present.
     * Any values in $keys that are an array are tested with ::anyKeyExists, so
     * true indicates at least one value in the array is present as a key.
     * These two functions recurse indirectly through each-other.
     * @param $keys
     * @param $array
     * @return bool
     */
    public static function allKeysExist(mixed $keys, array $array)
    { //#TODO handle callables and Objectes
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        foreach($keys as $key){
            if (is_array($key)) {
                return self::anyKeysExist($key, $array);
            } elseif (!key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks an array for the existance of one or more keys in an array
     * For any values in $keys that are valid keys (not an Array or Object),
     * true indicates any such keys are present.
     * Any values in $keys that are an array are tested with ::allKeyExists, so
     * true indicates all values in the array is present as a key.
     * These two functions recurse indirectly through each-other.
     * @param $keys
     * @param $array
     * @return bool
     */
    public static function anyKeysExist(mixed $keys, array $array)
    { //#TODO handle callables and Objectes
        foreach(Hash::coerce($keys) as $key){
            if (is_array($key)) {
                return self::allKeysExist($key, $array);
            } elseif (key_exists($key, $array)) {
                return true;
            }
        }
        return false;
    }
}