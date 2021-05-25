<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for session management
 */

namespace Saf;

class Session
{
    public const DEFAULT_KEEPERS = ['debug'];

    protected static $configuredKeepers = self::DEFAULT_KEEPERS;

	/**
	 * starts a session only if none exists
	 */
	public static function on()
	{
		if ('' == session_id()) {
			session_start();
		}
	}

    /**
	 * clears all but select values from the session
	 * @param array $keepers values to preserve
	 */
    public static function clean($keepers = null)
    {
        if (is_null($keepers)) {
            $keepers = self::$configuredKeepers;
        }
        self::on();
		$keeperValues = array();
		foreach($keepers as $keeperName){
			if (array_key_exists($keeperName, $_SESSION)) {
				$keeperValues[$keeperName] = $_SESSION[$keeperName];
			}
		}
		session_unset();
		foreach($keeperValues as $keeperName=>$keeperValue){
			$_SESSION[$keeperName] = $keeperValue;
		}
    }


}