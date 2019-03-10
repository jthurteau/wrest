<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for array manipulation

*******************************************************************************/

require_once(LIBRARY_PATH . '/Saf/Exception/NotAnArray.php');
require_once(LIBRARY_PATH . '/Saf/Exception/NoDefault.php');

/**
 * Utility functions for Arrays
 */
class Saf_Array
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
	public static function extract($key, $array, $default = NULL)
	{
		if(!is_array($array) && (!is_object($array) && !method_exists($array,'__toArray'))){
			throw new Saf_Exception_NotAnArray();
		}
		if(NULL === $default && !array_key_exists($key, $array)){
			throw new Saf_Exception_NoDefault();
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
			throw new Saf_Exception_NoDefault();
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
		} catch (Saf_Exception_NoDefault $e) { //#TODO #2.0.0 limit to specific exception
			return NULL;
		}
	}
	
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
	public static function extractIfArray($key, $array, $default = NULL)
	{
		return(
			is_array($array) && array_key_exists($key, $array)
			? $array[$key]
			: $default
		);
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
	 * the returned value is always passed through Saf_Array::clean first using $mode
	 * @param mixed $maybeArray
	 * @param int $mode
	 * @return array
	 */
	public static function coerce($maybeArray, $mode = self::MODE_VERBOSE)
	{
		return
			is_array($maybeArray)
				|| (is_object($maybeArray) && is_a('Traversable'))
			? self::clean($maybeArray, $mode)
			: self::clean(array($maybeArray), $mode);
	}

	/**
	 * depending on $mode, will return the literal value, or one scrubbed for blank values
	 * MODE_EXACT = no scrubbing
	 * MODE_TRUNCATE = remove NULL and empty string values
	 * MODE_AGGRESSIVE_TRUNCATE remove NULL and white space only strings
	 */
	public static function clean($array, $mode = self::MATCH_EXACT)
	{
		if (is_null($mode) || $mode === self::MATCH_EXACT) {
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
	public static function toString($array)
	{
		ob_start();
		print_r($array);
		$return = ob_get_contents();
		ob_end_clean();
		return $return;
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
	
	public static function toHtml($array, $ordered = FALSE, $nested = TRUE, $typed = FALSE, $boolean = FALSE)
	{
		if (!is_array($array)) {
			return '<ul><li>' . gettype($array) . ' ' . Saf_Debug::introspectData($array) . '</li></ul>';
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
					$value = Saf_Debug::introspectData($value); //#TODO #2.0.0 make a more user friendly version of this output
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
	public static function keyExistsAndNotBlank($key, $array, $allowedBlankTypes = self::TYPE_NONE)
	{
		if (!is_array($array)) {
			Saf_Debug::out('Saf_Array::keyExistsAndNotBlank got a non-array operand.');
			return FALSE;
		}
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
	public static function keyExistsAndEquals($key, $array, $value, $matchType = Saf_Array::MATCH_EQUAL)
	{
		if (!is_array($key)) {
			$key = array($key);
		}
		foreach ($key as $arrayKey) {
			if(!array_key_exists($arrayKey, $array)) {
				return FALSE;
			} else {
				switch ($matchType) {
					case Saf_Array::MATCH_EXACT:
						if ($value !== $array[$arrayKey]) {
							return FALSE;
						}
						break;
					case Saf_Array::MATCH_LOOSE:
						if(is_string($value)) {
							$value = strtolower(trim($value));
							$array[$arrayKey] = strtolower(trim($array[$arrayKey]));
						}
						//no break intentional
					case  Saf_Array::MATCH_EQUAL:
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
	public static function keyExistsAndInArray($key, $array, $list, $matchType = Saf_Array::MATCH_EQUAL)
	{
		if (!is_array($key)) {
			$key = array($key);
		}
		foreach ($key as $arrayKey) {
			if( !array_key_exists($arrayKey, $array)) {
				return FALSE;
			} else if (in_array($array[$arrayKey], $list, $matchType == Saf_Array::MATCH_EXACT)) {

				continue;
			} else if ($matchType == Saf_Array::MATCH_LOOSE) {
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
			&& array_key_exists(0, $array)
			&& array_key_exists(count($array) - 1, $array);
	}
	
	public static function keysExist($keys, $array)
	{
		if (!is_array($keys)) {
			$keys = array($keys);
		}
		foreach($keys as $key){
			if (!array_key_exists($key, $array)) {
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
	public static function inArray($value, $array, $matchType = Saf_Array::MATCH_LOOSE)
	{
		if (!is_array($array)) {
			return FALSE;
		}
		foreach ($array as $arrayKey=>$arrayValue) {
			switch ($matchType) {
				case Saf_Array::MATCH_EXACT:
					if ($value === $arrayValue) {
						return TRUE;
					}
					break;
				case Saf_Array::MATCH_LOOSE:
					if(is_string($value)) {
						$value = strtolower(trim($value));
						$arrayValue = strtolower(trim($arrayValue));
					}
				//#NOTE no break intentional
				case  Saf_Array::MATCH_EQUAL:
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
}