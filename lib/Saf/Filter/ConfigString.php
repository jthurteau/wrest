<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Filter for strings that come out of config files

*******************************************************************************/
require_once(LIBRARY_PATH . '/Saf/Filter.php');

class Saf_Filter_ConfigString extends Saf_Filter
{
	
	protected static $_commentDelimiters = array(
		'#','//'
	);
	
	public static function filter($value)
	{
		foreach(self::$_commentDelimiters as $delim) {
			if (strpos($value, $delim) !== FALSE) {
				$value = substr($value, 0, strpos($value, $delim));
			}
		}
		return trim($value);
	}
	
}