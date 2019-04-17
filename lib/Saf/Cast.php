<?php //#SCOPE_OS_PUBLIC
namespace Saf;
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for converting.

*******************************************************************************/
require_once(LIBRARY_PATH . '/Saf/Filter/Truthy.php');
use Saf\Kickstart\Truthy as Truthy;

//#TODO #1.0.0 update function header docs
class Cast {

	const TYPE_STRING = 0;
	const TYPE_BOOL = 1;
	const TYPE_INT = 2;
	const TYPE_FLOAT = 3;
	const TYPE_COMMA_ARRAY = 4;
	const TYPE_SPACE_ARRAY = 5;
	const TYPE_NEWLINE_ARRAY = 6;
	const TYPE_CSV = 7;
	const TYPE_TSV = 8;
	const TYPE_JSON = 9;
	const TYPE_XML_MAP = 10;
	
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

}