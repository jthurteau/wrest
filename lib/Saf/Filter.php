<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base class for filters

*******************************************************************************/
abstract class Saf_Filter
{
	public static function filter($value)
	{
		return $value;
	}
	
}