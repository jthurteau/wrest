<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for expanded array manipulation
 */

namespace Saf;

use Saf\Exception\NoDefault;
use Saf\Exception\NotAnArray;
use Saf\Utils\Filter\Truthy;

require_once(__DIR__ . '/Exception/NoDefault.php');
require_once(__DIR__ . '/Exception/NotAnArray.php');

class Hash
{

    const TYPE_NONE = 0;
    const TYPE_NULL = 1;
    const TYPE_STRING = 2;
    const TYPE_ARRAY = 4;
    
    const MATCH_EXACT = 0;
    const MATCH_EQUAL = 1;
    const MATCH_LOOSE = 2;

    const MODE_VERBOSE = 0;
    const MODE_TRUNCATE = 1;
    const MODE_AGRESSIVE_TRUNCATE = 2;
 
    /**
     * searches the passed array for the specified key. If
     * it does not exist, return the $default value or
     * throw an exception if no default is given. The default
     * value may not be null.
     * 
     * @param mixed $key key in the array to check
     * @param array $array array to search
     * @param default $default value if key is not in array
     */
    //public static function extract($key, array|\ArrayAccess $array, $default = NULL)
    public static function extract($key, $array, $default = NULL)
    {
        self::assert($array);
        if(NULL === $default && !array_key_exists($key, $array)){
            throw new NoDefault();
        }
        return(
            array_key_exists($key, $array) 
            ? $array[$key]
            : $default
        );
    }
    
    /**
     * searches the passed array for the specified key. If
     * it does not exist or is blank, return the $default value or
     * throw an exception if no default is given. The default
     * value may not be null.
     * 
     * @param mixed $key key in the array to check
     * @param array $array array to search
     * @param int $allowedBlankTypes bitwise integer of blank types that are allowed
     * @param default $default value if key is not in array
     */
    public static function extractIfNotBlank($key, $array, $allowedBlankTypes = self::TYPE_NONE, $default = NULL)
    {
        if (self::keyExistsAndNotBlank($key, $array, $allowedBlankTypes)) {
            return is_null($default)
                ? self::extract($key, $array)
                : self::extract($key, $array, $default);
        } else if (is_null($default)) {
            throw new NoDefault();
        } else {
            return $default;
        }
    }

    /**
     * searches the passed array for the specified key. If
     * it does not exist or is blank, return NULL.
     *
     * @param mixed $key key in the array to check
     * @param array $array array to search
     * @param int $allowedBlankTypes bitwise integer of blank types that are allowed
     * @param default $default value if key is not in array
     */
    public static function extractOptionalIfNotBlank($key, $array, $allowedBlankTypes = self::TYPE_NONE)
    {
        if (self::keyExistsAndNotBlank($key, $array, $allowedBlankTypes)) {
            return self::extractOptional($key, $array);
        } else {
            return NULL;
        }
    }

    /**
     * searches the passed array for the specified key. If
     * it does not exist, return NULL
     * 
     * @param mixed $key key in the array to check
     * @param array $array array to search
     */
    public static function extractOptional($key, $array)
    {
        try {
            return self::extract($key,$array);
        } catch (NoDefault $e) { //#TODO #2.0.0 limit to specific exception
            return NULL;
        }
    }
    
    /**
     * Searches the passed value for the specified key. If
     * it does not exist, return the $default value or
     * throw an exception if no default is given. The default
     * value may not be null.
     * 
     * @param mixed $key key in the array to check
     * @param array $array possible array to search
     * @param default $default value if key is not in array
     */
    //public static function extractIfArray($key, mixed $possibleArray, $default = null)
    public static function extractIfArray($key, $possibleArray, $default = null)
    {
        // return(
        //     ( is_array($possibleArray) || ($possibleArray instanceof \ArrayAccess)) 
        //         && key_exists($key, $possibleArray)
        //     ? $possibleArray[$key]
        //     : (!is_null($default) ? $default : throw new NoDefault())
        // );
		$available = 
			( is_array($possibleArray) || ($possibleArray instanceof \ArrayAccess)) 
			&& key_exists($key, $possibleArray);
		if (!$available && is_null($default)) {
			throw new NoDefault();
		}
		return $available ? $possibleArray[$key] : $default;
    }

    /**
     * takes an array of string keyed arrays, and flattens the values out by
     * adding '1' to the end of the keys of the first array, '2' to the end
     * of keys from the second and so forth.
     *
     * @param array $array to flatten
     * @return array flattened data
     */
    public static function flatten($array)
    {
        $i = 1;
        $flattenedArray = array();
        foreach ($array as $innerArray){
            foreach ($innerArray as $originalKey=>$innerData){
                $flattenedArray[$originalKey . $i] = $innerData;
            }
            $i++;
        }
        return $flattenedArray;
    }

    /**
     * takes an array of string keyed values, including arrays
     * if a value is an array, it expands the name in the style similar to what 
     * PHP expects from multidimensional form data 
     * (i.e. appending "[]" to the key, and then if any of that arrays values 
     * are arrays, appending "[]" again.
     *
     * @param array $array to flatten
     * @return array flattened data
     */
    public static function flattenPostArray($array, $prefix='')
    {
        $flatArray = array();
        foreach($array as $key=>$value) {
            if (is_array($value)) { //#TODO #2.0.0 handle iterable objects, other objects...
                $flatArray = array_merge($flatArray,self::flattenPostArray($value, $key . '[]'));
            } else {
                $flatArray[$prefix.$key] = $value;
            }
        }
        return $flatArray;
    }

    /**
     * always returns an array or traversable object
     * if neither of the above are provided, will return an array containing the param $maybeArray
     * the returned value is always passed through Hash::clean first using $mode
     * @param mixed $maybeArray
     * @param int $mode
     * @return array
     */
    public static function coerce($maybeArray, $mode = self::MODE_VERBOSE)
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
    public static function clean($array, $mode = self::MODE_VERBOSE)
    { //#TODO #2.0.0 the default for this should be switched, but that's not backwards compatible
        if (is_null($mode) || $mode === self::MODE_VERBOSE) {
            return $array;
        }
        foreach($array as $index => $value) {
            $testValue =
                self::MODE_AGRESSIVE_TRUNCATE && is_string($value)
                ? trim($value)
                : $value;
            if(is_null($value) || $testValue == '') {
                unset($array[$index]);
            }
        }
        return $array;
    }

    /**
     * takes string or array of strings and array, returning an array where
     * no string in the first parameter appears as a key in the new array.
     *
     * @param mixed $exclude string or array of strings to exclude
     * @param array $array from which some keys may be excluded
     * @return array subset of $array
     */
    public static function excludeKeys($exclude, $array)
    {
        if (!is_array($exclude)){
                $exclude = array($exclude);
        }
        foreach ($exclude as $key){
              if(array_key_exists($key, $array)) {
                        unset($array[$key]);
                }
        }
        return $array;
    }

    /**
     * takes a value and an array, returning an array where
     * no value matching the first parameter appears in the new array.
     *
     * @param mixed $exclude string or array of strings to exclude
     * @param array $array from which some keys may be excluded
     * @param bool $strict indicates if type coersion is (not) allowed
     * @return array subset of $array
     */
    public static function exclude($exclude, $array, $strict = TRUE)
    {
        foreach ($array as $key => $value){
            if ($strict && $value === $exclude) {
                unset($array[$key]);
            } else if (!$strict && $value == $exclude) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    public static function average($array){
            return(array_sum($array) / count($array));
    }

    /**
     * Serializes an array into a string using the formatting of print_r()
     *
     * @param array $array to serialize
     * @return string representation of $array
     */
    public static function toString(array|\Iterator|\ArrayAccess $array): string
    {
        return Debug::stringR($array);
    }
    
    public static function containsTokens($string, $tokenArray)
    {
        foreach($tokenArray as $token) {
            if (strpos($string, $token) !== false) {
                return true;
            }
        }
        return false;
    }
    public static function urlencode($paramName, $array = NULL){
        //#TODO #2.0 swap the order eventually
        //#TODO #2.0 handle nested arrays
        if (is_null($array)) {
            $array = $paramName;
            $paramName = NULL;
        }
        $return = array();
        foreach($array as $key => $value) {
            if (is_array($value)) {
                foreach($value as $innerValue) {
                    $return[] =
                        urlencode(
                            $paramName
                                ? ($paramName . '[]')
                                : $key
                        ) . '=' . urlencode($innerValue);
                }
            } else {
                $return[] =
                urlencode(
                    $paramName
                    ? ($paramName . '[]')
                    : $key
                ) . '=' . urlencode($value);
            }
        }
        return implode('&', $return);
    }

    /**
     * parses a URL query string into an array of values
     * a query parameter with no matching value is assigned "true"
     * all other values are returned as a literal string, including the empty string
     * @param string $query URLesque (x=y&z=2&present) string of value pairs
     * @return array
     */
    public static function fromQuery(string $query): array
    {// #TODO add encoding/decoding filter param
        $queryDelim = '?';
        strpos($query, $queryDelim) === 0 && $query = substr($query, strlen($queryDelim));
        $data = [];
        $parts = explode('&', $query);
        foreach ($parts as $pair) {
            $components = explode('=', $pair, 2);
            $field = $components[0];
            $value = key_exists(1, $components) ? $components[1] : true;
            $field && ($data[$field] = $value);
        }
        return $data;
    }
    
    public static function toHtml($array, $ordered = false, $nested = true, $typed = false, $boolean = false)
    {
        if (!is_array($array)) {
            return '<ul><li>' . gettype($array) . ' ' . self::introspectData($array) . '</li></ul>';
        }
        $return = '';
        $return .= ($ordered ? '<ol>' : '<ul>');
        if (count($array) == 0) {
            $return .= '<li>None</li>';
        } else {
            foreach($array as $key => $value){
                $type = ($typed ? (' (' . gettype($value) . ')') : '');
                if ($boolean && !is_array($value) && !$value) {
                    continue;
                }
                $return .= '<li>';
                if (is_object($value)) {
                    $value = self::introspectData($value); //#TODO #2.0.0 make a more user friendly version of this output
                } else if ($nested && is_array($value)) {
                    $value = self::toHtml($value, $ordered, $nested, $typed, $boolean);
                }
                $return .= "{$key}{$type}:  {$value}";
                $return .= '</li>';
            }
        }
        $return .= ($ordered ? '</ol>' : '</ul>'); 
        return $return;
    }
    
    /**
     * Searches for string $key in array $array and returns true only if
     * it the key exists and the value it contains is an integer, boolean, or
     * non-empty. Optional third parameter can allow one or more falsy values:
     * NULL : Saf_Array::TYPE_NULL, 
     * empty array : Saf_Array::TYPE_ARRAY, 
     * empty string : Saf_Array::TYPE_STRING
     * 
     * $key may be an array, this method will return true if all are present.
     * 
     * @param string $key string array key to search for
     * @param array $array to be searched
     * @param int $allowedBlankTypes bitwise integer of blank types that are allowed
     * @return bool key exists and value is not blank
     */    
    //public static function keyExistsAndNotBlank($key, array|\ArrayAccess $array, $allowedBlankTypes = self::TYPE_NONE)
    public static function keyExistsAndNotBlank($key, $array, $allowedBlankTypes = self::TYPE_NONE)
    {
        self::assert($array);
        if (!is_array($key)) {
            $key = array($key);
        }
        foreach ($key as $arrayKey) {
            if(
                !array_key_exists($arrayKey, $array)
                || !( $allowedBlankTypes & self::TYPE_NULL || !is_null($array[$arrayKey]))
                || !(
                    is_object($array[$arrayKey])
                    || (
                        is_array($array[$arrayKey]) 
                        && ($allowedBlankTypes & self::TYPE_ARRAY || count($array[$arrayKey]) > 0)
                    ) || (is_string($array[$arrayKey])
                        && ($allowedBlankTypes & self::TYPE_STRING || '' != trim($array[$arrayKey]))
                    ) || is_bool($array[$arrayKey])
                    || (is_numeric($array[$arrayKey]) && !is_string($array[$arrayKey]))
                    || is_resource($array[$arrayKey])
                )
            ) {
                return FALSE;
            }
        }
        return TRUE;
    }
    
    /**
     * Searches for string $key in array $array and returns true only if
     * it the key exists and the value matches $value. The optional fourth 
     * parameter determines how the match is determined, defaults to == :
     * == : Saf_Array::MATCH_EQUAL, 
     * === : Saf_Array::MATCH_EXACT, 
     * string, case insensitive, trimmed : Saf_Array::MATCH_LOOSE
     * 
     * $key may be an array, this method will return true if all are present.
     * 
     * @param string $key string array key to search for
     * @param array $array to be searched
     * @param int $matchType bitwise integer of match type to be performed
     * @return bool key exists and value is not blank
     */    
    public static function keyExistsAndEquals($key, $array, $value, $matchType = self::MATCH_EQUAL)
    {
        if (!is_array($key)) {
            $key = array($key);
        }
        foreach ($key as $arrayKey) {
            if(!array_key_exists($arrayKey, $array)) {
                return FALSE;
            } else {
                switch ($matchType) {
                    case self::MATCH_EXACT:
                        if ($value !== $array[$arrayKey]) {
                            return FALSE;
                        }
                        break;
                    case self::MATCH_LOOSE:
                        if(is_string($value)) {
                            $value = strtolower(trim($value));
                            $array[$arrayKey] = strtolower(trim($array[$arrayKey]));
                        }
                        //no break intentional
                    case  self::MATCH_EQUAL:
                    default:
                        if ($value != $array[$arrayKey]) {
                            return FALSE;
                        }
                }
            }
        }
        return TRUE;
    }
    
    public static function keyExistsAndIsArray($key, $array) //#TODO #2.0.0 handle array like values by match type?
    {
        if (!is_array($key)) {
            $key = array($key);
        }
        foreach ($key as $arrayKey) {
            if(!array_key_exists($arrayKey, $array) || !is_array($array[$arrayKey])) {
                return FALSE;
            }
        }
        return TRUE;
    }
    
    /**
     * Searches for string $key in array $array and returns true only if
     * the key exists and is a value in $list. The optional fourth
     * parameter determines how the match is determined, defaults to == :
     * == : Saf_Array::MATCH_EQUAL,
     * === : Saf_Array::MATCH_EXACT,
     * string, case insensitive, trimmed : Saf_Array::MATCH_LOOSE
     * 
     * $key may be an array, this method will return true if all are present.
     * 
     * @param string $key string array key to search for
     * @param array $array to be searched
     * @param int $allowedBlankTypes bitwise integer of blank types that are allowed
     * @return bool key exists and value is not blank
     */    
    public static function keyExistsAndInArray($key, $array, $list, $matchType = self::MATCH_EQUAL)
    {
        if (!is_array($key)) {
            $key = array($key);
        }
        foreach ($key as $arrayKey) {
            if( !array_key_exists($arrayKey, $array)) {
                return FALSE;
            } else if (in_array($array[$arrayKey], $list, $matchType == self::MATCH_EXACT)) {

                continue;
            } else if ($matchType == self::MATCH_LOOSE) {
                foreach($list as $listValue) {
                    if(strtolower($listValue) == strtolower($array[$arrayKey])) {
                        continue 2;
                    }
                }
                return FALSE;
            } else {
                return FALSE;
            }
        }
        return TRUE;
    }
    
    public static function isNumericArray($array)
    {
        return is_array($array)
            && key_exists(0, $array)
            && key_exists(count($array) - 1, $array);
    }

    //#TODO public static function isNumericallyIndexed(array|X $array, ?bool $strict = true): float|bool //actually check each index

    public static function keysExist($keys, $array)
    {
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        foreach($keys as $key){
            if (!key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Searches for string $key in array $array and returns true only if
     * it the key exists and the value matches $value. The optional fourth
     * parameter determines how the match is determined, defaults to == :
     * == : Saf_Array::MATCH_EQUAL,
     * === : Saf_Array::MATCH_EXACT,
     * string, case insensitive, trimmed : Saf_Array::MATCH_LOOSE
     *
     * $key may be an array, this method will return true if all are present.
     *
     * @param string $key string array key to search for
     * @param array $array to be searched
     * @param int $matchType bitwise integer of match type to be performed
     * @return bool key exists and value is not blank
     */
    public static function inArray($value, $array, $matchType = self::MATCH_LOOSE)
    {
        if (!is_array($array)) {
            return FALSE;
        }
        foreach ($array as $arrayKey=>$arrayValue) {
            switch ($matchType) {
                case self::MATCH_EXACT:
                    if ($value === $arrayValue) {
                        return TRUE;
                    }
                    break;
                case self::MATCH_LOOSE:
                    if(is_string($value)) {
                        $value = strtolower(trim($value));
                        $arrayValue = strtolower(trim($arrayValue));
                    }
                //#NOTE no break intentional
                case  self::MATCH_EQUAL:
                default:
                    if ($value == $arrayValue) {
                        return TRUE;
                    }
            }
        }
        return FALSE;
    }

    /**
     * Searches for string $key in array $array and returns true if
     * 1) $key is not in the array AND $default is TRUE (default behavior)
     *    i.e. the key for the checkbox(es) are not in the array
     * 2) $key is in the array, and is is an array, and
     *    at least one value in the array matches $value
     * 3) $key is in the array, and is not an array, and the value matches
     *    $value
     * $caseSensitive (default false) is considered in the string comparison
     * of $value (casting $value to a string) and any matching result
     * (also cast to string).
     * @param string $key string array key to search for
     * @param mixed $value to look for (always mapped to a string)
     * @param array $array to be searched
     * @param bool $default indicates the default behavior of the checkbox
     * @return bool $caseSentitive set to true for a case-senstive comparison
     */
    public static function checkboxHelper($key, $value, $array, $default = TRUE, $caseSensitive = FALSE)
    {
        return (
            !array_key_exists($key, $array) && $default
        ) || (
            is_array($array[$key]) && self::inArray($value, $array[$key], self::MATCH_LOOSE)
        ) || (
            !is_array($array[$key]) && strtolower($array[$key]) == (string)$value
        );
    }

    public static function traverse($array, $keys, $keyDelim = ':')
    {
        $current = self::coerce($array);
        $searchParts = 
            is_string($keys) && $keyDelim
            ? explode($keyDelim, $keys)
            : self::coerce($keys);
        foreach($searchParts as $name) {
            if (self::traversable($current) && array_key_exists($name, $current)) {
                $current = $current[$name];    
            } else {
                return NULL;
            }
        }
        return $current;
    }


    public static function enumCombinations($values, $branch = '')
    {
        $combinations = array();
        foreach( $values as $i => $v) {
            $combinations[] = $branch . $v;
            if (count($values) > 1) {
                $rest = $values;
                unset($rest[$i]);
                foreach(self::enumCombinations($rest, $branch . $v) as $n) {
                    $combinations[] = $n;
                }
            }            
        }
        return $combinations;
    }

    public static function anyKeyExists($keys, $array)
    {
        foreach($keys as $k) {
            if (array_key_exists($k, $array)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public static function prefixAll($array, $prefix)
    {
        $return = array();
        foreach($array as $value) {
            $return[] = $prefix . $value;
        }
        return $return;
    }

    protected static function introspectData(mixed $mixed, $provider = null): string
    //protected static function introspectData(mixed $mixed, $provider = null)
    { #TODO this is also in debug and self::toString, so consolidate/improve
        return Debug::stringR($mixed);
    }

    public static function match($ids, $data)
    {
        if (is_null($ids)) {
            return $data;
        }
        $returnArray = is_array($ids);
        $ids = is_array($ids) ? $ids : array($ids);
        $results = [];
        foreach($ids as $id) {
            if (key_exists($id, $data)) {
                $results[$id] = $data[$id];
            }
        }
        return $returnArray ? $results : current($results);
    }

    public static function toTags($tagName, $values)
    {
        $return = '';
        if (!is_array($values)) {
            $values = array($values);
        }
        if (count($values) > 0) {
            $return =
                "<{$tagName}>"
                . implode("</{$tagName}><{$tagName}>",$values)
                . "</{$tagName}>";
        }
        return $return;
    }

    public static function arrayMap($data, $flatten = false)
    {
        $return = array();
        $increment = 1;
        if(is_array($data)) { 
            foreach ($data as $key=>$value) {
                if(array_key_exists($key, $return)) {
                    $return[$key . ($increment++)] = self::valueMap($value);
                } else {
                    $return[$key] = self::valueMap($value);
                }
            }
        } else if (is_object($data) && method_exists($data, 'toArray')) {
            $return = self::arrayMap($data->toArray());
        } else if (is_object($data) && method_exists($data, '__toArray')) {
            $return = self::arrayMap($data->__toArray());
        } else if (is_object($data) && in_array('Traversable', class_implements($data))) {
            if(0 ==count($data)) {
                $return = (string)$data;
            } else {
                foreach ($data as $key=>$value) {
                    if(array_key_exists($key, $return)) {
                        $return[$key . ($increment++)] = self::valueMap($value);
                    } else {
                        $return[$key] = self::valueMap($value);
                    }
                }
            }
        } else {
            $return[] = $data;
        }
        if ($flatten && is_array($return)) {
            $flattenedReturn = array();
            foreach($return as $value) {
                if(is_array($value)) {
                    foreach($value as $subKey=>$subValue) {
                        $flattenedReturn[$subKey] = $subValue;
                    }
                } else {
                    $flattenedReturn[] = $value;
                }
            }
            $return = $flattenedReturn;
        }
        return $return;
    }

    public static function valueMap($data, $cast = 'auto'){
        switch($cast)
        {
            case 'boolean' :
            case 'bool' :
                return (boolean) $data;
                break;
            case 'truthy' :
                return Truthy::filter($data);
            case 'string' :
                return (string) $data;
            case 'int' :
            case 'integer' :
                return (int) $data;
            case 'float' :
            case 'double' :
            case 'real' :
                return (float) $data;
            default:
                return is_array($data) || is_object($data)
                ? self::arrayMap($data)
                : $data;
        }
    }

//    public static function &first(array|\ArrayAccess $a)
    public static function &first($a)
    {
        self::assert($a);
        return $a[array_key_first($a)];
    }

//    public static function firstKeyMatching($value, array|\ArrayAccess $array)
    public static function firstKeyMatching($value, $array)
    {
        self::assert($array);
        foreach($array as $key => $member) {
            if ($member == $value) {
                return $key;
            }
        }
        return null;
    }

    /**
     * 
     * @throws Saf\NotAnArray
     */
    public static function assert($array)
    { //#NOTE this needs work, ArrayAccess doesn't fit the bill here, coerce instead?
        if (!is_array($array) && ! $array instanceof \ArrayAccess){ //!is_a($array, 'ArrayAccess')) {
            throw new NotAnArray();
        }
    }
}