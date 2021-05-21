<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Mute Utility for Saf\Debug
 */

namespace Saf\Utils\Debug;

use Saf\Debug;

class Mute 
{

	protected static $_muted = array();
	protected static $_muteIndex = 0;

	public static function activate()
	{
		self::$_muted[self::$_muteIndex] = Debug::getTrace();
		return self::$_muteIndex++;
	}

	public static function deactivate($index = null)
	{
		if (!is_null($index)) {
			unset(self::$_muted[$index]);
		} else {
			self::$_muted = array();
		}
	}

    public static function active()
    {
        return count(self::$_muted) > 0;
    }

    public static function list()
    {
        return self::$_muted;
    }
}