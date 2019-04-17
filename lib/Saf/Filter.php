<?php //#SCOPE_OS_PUBLIC
namespace Saf;
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base class for filters

*******************************************************************************/
abstract class Filter
{
	public static function filter($value)
	{
		return $value;
	}
	
}