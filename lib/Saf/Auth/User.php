<?php


/**
 *
* Class for managing user identity.
* @author jthurtea
*
*/

class Saf_Auth_User
{
	protected static $_userName = NULL;

	public static function init()
	{
		if (
			is_null(self::$_userName)
			&& isset($_SESSION)
			&& is_array($_SESSION)
			&& array_key_exists('username', $_SESSION)
		) {
			if (array_key_exists('username', $_SESSION)
			) {
				self::$_userName = $_SESSION['username'];
			}
		}
	}

	public static function setName($username){
		self::$_userName = $username;
	}
	
	public static function getName(){
		self::init();
		return self::$_userName;
	}

}