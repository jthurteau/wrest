<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for Google APIs

 *******************************************************************************/

class Saf_Google
{

	protected static $_autoloaderPath  = '/Google/vendor/autoload.php';

	public static function autoload()
	{
		if (is_readable(LIBRARY_PATH . self::$_autoloaderPath)) {
			require_once(LIBRARY_PATH . self::$_autoloaderPath);
		} else {
			throw new Saf_Exception_NotConfigured('Google Integration Unavailable. (' . self::$_autoloaderPath . ')');
		}
	}
}
