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
     * @var array holder for set values if session isn't ready
     */
    protected static $buffer = [];

	/**
	 * starts a session only if none exists
	 */
	public static function on(): void
	{
		if ('' == session_id()) {
			session_start();
		}
	}

    /**
     * checks if the current session is ready
     */
    public static function ready(): bool
    {
        return isset($_SESSION) && is_array($_SESSION);
    }

    /**
     *
     */
    public static function has(int|string $index): bool
    {
        return self::ready() && key_exists($index, $_SESSION);
    }

    public static function set(int|string $index, $value): void
    {
        self::ready() ? ($_SESSION[$index] = $value) : (self::$buffer[$index] = $value);
    }

    public static function get(int|string $index): mixed
    {
        return self::ready() && key_exists($index, $_SESSION) ? $_SESSION[$index] : null;
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