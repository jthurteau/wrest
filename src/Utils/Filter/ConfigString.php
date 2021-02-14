<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Filter for strings that come out of config files
 */

namespace Saf\Filter;

use Saf\Filter;

require_once(dirname(dirname(__FILE__)) . 'Filter.php');

class ConfigString extends Filter
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