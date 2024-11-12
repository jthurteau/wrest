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
    public const TYPE_FLOAT = 6;
    public const TYPE_STRING = 3;
    public const TYPE_ARRAY = 4;
    public const TYPE_OBJECT = 5;
    public const TYPE_NUMERIC = 7;
    public const TYPE_ATOMIC = 7;



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

    public static function typeMatch($v, int|string $type) : bool
    {
        switch ($type) {
            case self::TYPE_MIXED:
                return true;
            case self::TYPE_BOOL:
            case 'bool':
            case 'boolean':
                return is_bool($v);
            case self::TYPE_INT:
            case 'int':
            case 'integer':
                return is_int($v);
            case self::TYPE_FLOAT:
            case 'float':
            case 'double':
                return is_float($v);
            case self::TYPE_NUMERIC:
            case 'numeric':
                return is_numeric($v);
            case self::TYPE_ATOMIC:
            case 'atomic':
                return is_string($v) || is_bool($v) || is_int($v) || is_float($v) || is_null($v);
            case self::TYPE_STRING:
            case 'string':
                return is_string($v);
            case self::TYPE_ARRAY:
            case 'array':
                return is_array($v);
            case self::TYPE_OBJECT:
            case 'object':
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

    public static function deepMine(array|\Iterator $a, null|int|string $type): array
    {
        $result = [];
        //print(\Saf\Debug::stringR(__FILE__,__LINE__, $a, $type));
        foreach ($a as $value) {
            if (is_null($type) || self::typeMatch($value, $type)){
                //print(\Saf\Debug::stringR(__FILE__,__LINE__, 'match', $value, $type));
                $result[] = $value;
            } elseif (is_array($a) || is_a(\Iterator::class, $value)) {
                //print(\Saf\Debug::stringR(__FILE__,__LINE__, 'iterate', $value));
                $result = array_merge($result, self::deepMine($value, $type));
            }
        }
        //print(\Saf\Debug::stringR(__FILE__,__LINE__, $result, $a, $type));
        return $result;
    }

}