<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Class for managing user identity
 */

namespace Saf\Auth;

class User
{

	public const NAME_KEY = 'username';

	protected static $username = null;

	public static function init()
	{
		if (
			is_null(self::$username)
			&& isset($_SESSION)
			&& is_array($_SESSION)
			&& key_exists(self::NAME_KEY, $_SESSION)
		) {
			if (key_exists(self::NAME_KEY, $_SESSION)) {
				self::$username = $_SESSION[self::NAME_KEY];
			}
		}
	}

	public static function setName($name){
		self::$username = $name;
	}
	
	public static function getName(){
		self::init();
		return self::$username;
	}

}