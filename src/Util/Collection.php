<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for list (array) manipulation
 */

namespace Saf\Util;

use Saf\Util\ArrayLike;

require_once(__DIR__ . '/ArrayLike.php');

class Collection
{
    use ArrayLike;

    public const MODE_VERBOSE = 0;
    public const MODE_TRUNCATE = 1;
    public const MODE_AGRESSIVE_TRUNCATE = 2;
 
    public const TYPE_MIXED = 0;
    public const TYPE_BOOL = 1;
    public const TYPE_INT = 2;
    public const TYPE_STRING = 3;
    public const TYPE_ARRAY = 4;
    public const TYPE_OBJECT = 5;



    /**
     * always returns an array or traversable object
     * if neither of the above are provided, 
     *   will return an array potentially containing the param $maybeArray
     * the returned value is always passed through List::clean first using $mode
     * @param mixed $maybeArray
     * @param int $mode
     * @return array|Traversable
     */
    public static function coerce($maybeArray, ?int $mode = self::MODE_AGRESSIVE_TRUNCATE)
    {
        return
            self::traversable($maybeArray)
            ? self::clean($maybeArray, $mode)
            : self::clean([$maybeArray], $mode);
    }

    public static function traversable($maybeArray)
    {
        return
            is_array($maybeArray)
            || (is_object($maybeArray) && is_a($maybeArray, 'Traversable'));
    }

    /**
     * depending on $mode, will return the literal value, or one scrubbed for blank values
     * MODE_VERBOSE = no scrubbing
     * MODE_TRUNCATE = remove NULL and empty string values
     * MODE_AGGRESSIVE_TRUNCATE remove NULL and white space only strings
     */
    public static function clean(array $array, ?int $mode = self::MODE_AGRESSIVE_TRUNCATE)
    {
        if (is_null($mode) || $mode === self::MODE_VERBOSE) {
            return $array;
        }
        foreach($array as $index => $value) {
            $testValue =
                self::MODE_AGRESSIVE_TRUNCATE && is_string($value)
                ? trim($value)
                : $value;
            if(is_null($value) || $testValue === '') {
                unset($array[$index]);
            }
        }
        return $array;
    }

    /**
     * filters out values in $collection that don't match the specified type
     * @param traversible $collection
     * @param int $type class constant TYPE_* defaults to TYPE_MIXED
     */
    public static function filter($collection, ?int $type = self::TYPE_MIXED) : array
    { //#TODO add coersion
        $return = [];
        foreach($collection as $value) {
            self::typeMatch($value, $type) && ($return[] = $value);
        }
        return $return;
    }

    public static function typeMatch($v, int $type) : bool
    {
        switch ($type) {
            case self::TYPE_MIXED:
                return true;
            case self::TYPE_BOOL:
                return is_bool($v);
            case self::TYPE_INT:
                return is_int($v);
            case self::TYPE_STRING:
                return is_string($v);
            case self::TYPE_ARRAY:
                return is_array($v);
            case self::TYPE_OBJECT:
                return is_object($v);
            default:
                return false;
        }
    }

    //    public static function &first(array|\ArrayAccess $a) // #TODO this should be polyfil until PHP8
    public static function &first($a)
    {
        self::assert($a);
        return $a[array_key_first($a)];
    }

//     /**
//      * takes a value and an array, returning an array where
//      * no value matching the first parameter appears in the new array.
//      *
//      * @param mixed $exclude string or array of strings to exclude
//      * @param array $array from which some keys may be excluded
//      * @param bool $strict indicates if type coersion is (not) allowed
//      * @return array subset of $array
//      */
//     public static function exclude($exclude, $array, $strict = TRUE)
//     {
//         foreach ($array as $key => $value){
//             if ($strict && $value === $exclude) {
//                 unset($array[$key]);
//             } else if (!$strict && $value == $exclude) {
//                 unset($array[$key]);
//             }
//         }
//         return $array;
//     }
    
//     public static function isNumericArray($array)
//     {
//         return is_array($array)
//             && key_exists(0, $array)
//             && key_exists(count($array) - 1, $array);
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

//     public static function match($ids, $data)
//     {
//         if (is_null($ids)) {
//             return $data;
//         }
//         $returnArray = is_array($ids);
//         $ids = is_array($ids) ? $ids : array($ids);
//         $results = array();
//         foreach($ids as $id) {
//             if (array_key_exists($id, $data)) {
//                 $results[$id] = $data[$id];
//             }
//         }
//         return $returnArray ? $results : current($results);
//     }

//     public static function toTags($tagName, $values)
//     {
//         $return = '';
//         if (!is_array($values)) {
//             $values = array($values);
//         }
//         if (count($values) > 0) {
//             $return =
//                 "<{$tagName}>"
//                 . implode("</{$tagName}><{$tagName}>",$values)
//                 . "</{$tagName}>";
//         }
//         return $return;
//     }

}