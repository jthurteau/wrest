<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for converting values.
 */


namespace Saf;
use Saf\Utils\Kickstart\Truthy;

require_once(__DIR__ . '/Utils/Filter/Truthy.php');

//#TODO #1.0.0 update function header docs
class Cast
{

	public const TYPE_STRING = 0;
	public const TYPE_BOOL = 1;
	public const TYPE_INT = 2;
	public const TYPE_FLOAT = 3;
	public const TYPE_COMMA_ARRAY = 4;
	public const TYPE_SPACE_ARRAY = 5;
	public const TYPE_NEWLINE_ARRAY = 6;
	public const TYPE_CSV = 7;
	public const TYPE_TSV = 8;
	public const TYPE_JSON = 9;
	public const TYPE_XML_MAP = 10;
	
	/**
	 * casts a value into a different type
	 * @param mixed $value
	 * @param int $cast matching one of the CAST_ class constants
	 * @return mixed
	 */
	public static function translate($value, $cast)
	{
		switch ($cast) {
			case self::TYPE_BOOL :
				return Truthy::filter($value);
			default: //#TODO #2.0.0 support the other cast features
				return $value;
		}
	}

	/**
	 * unpacks a mixed/bool value to mapped values
	 * @param mixed $unpack value to test
	 * @param mixed $truthy returned if $unpack is boolean true
	 * @param mixed $falsy returned if $unpack if boolean false
	 * @param mixed $valid maps other values of $unpack using :mvm
	 * @return $truthy, $falsy, $unpack (if no $valid[ity] constraint)
	 */
	public static function mvl($unpack, $truthy, $falsy, $valid = null)
	{
		if (is_bool($unpack)){
			return $unpack ? $truthy : $falsy;
		} else{
			return
				is_null($valid)
				? $unpack
				: self::mvm($unpack, $valid);
		}
	}

	/**
	 * packs mapped values to a mixed/bool value
	 * @param mixed $pack value to set
	 * @param mixed $truthy $pack as boolean true if it matches
	 * @param mixed $falsy $pack as boolean false if it matches
	 * @return /true, false, or $pack if no match against $truthy or $falsy
	 */
	public static function dmvl($pack, $truthy, $falsy)
	{
		return
			in_array($pack, [$truthy, $falsy], true)
			? $pack == $truthy
			: $pack;
	}

	/**
	 * maps a value to a map of valid values
	 * @param $value to test
	 * @param mixed $options, $options is always returned if not an array
	 * @return $value in $options, value mapped to key $value in $options, null if no match
	 */
	public static function mvm($value, $options)
	{
		if (is_array($options) && in_array($value, $options)) {
			return $value;
		} elseif (is_string($value) && key_exists($value, $options)) {
			return $options[$value];
		}
		return is_array($options) ? null : $options;
	}

}