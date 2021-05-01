<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Traits for for Agent identity
 */

namespace Saf\Agent;

use Saf\Auto;

require_once(dirname(dirname(__FILE__)) . '/Auto.php');

trait Identity {

    /**
     * returns the instance name option key
     */
    abstract public static function instanceOption();

    /**
     * returns the kickstart mode option key
     */
    abstract public static function modeOption();

    /**
     * returns the agent's signifier for running with no framework
     */
    abstract public static function noMode();

    /**
     * returns the agent's signifier for auto-detecting frameworks
     * if passed parameters, return a mode that supports the paramerters
     */
    abstract public static function autoMode();

    /**
     * returns the agent's chosen default instance
     */
    abstract public static function defaultInstance();

    /**
     * returns the agent mode delimiter
     */
    abstract public static function modeDelim();

    /**
	 * generates a unique id for passed meditation
	 */
	abstract protected static function agentIdStrategy(string $instance);

    /**
	 * returns the mode name for a given instance specification
	 * @param string $mode to parse for mode name
     * @param array $options #TODO #2.0.0
	 * @return string
	 */
	public static function parseMode($mode, $options = [])
	{
        $optionsMode = #TODO #2.0.0 handle non-supported modes
            array_key_exists(self::modeOption(), $options)
            ? $options[self::modeOption()]
            : self::autoMode();
		if (is_string($mode)) {
			$modeDelimPosition = strpos($mode, self::modeDelim());
			if ($modeDelimPosition) {
				$mode = substr($mode, $modeDelimPosition + 1);
				//return in_array($mode, self::availableModes()) ? $mode : self::nomode();
                return $mode ? $mode : self::nomode();
			}
			return in_array($mode, self::availableModes()) ? $mode : $optionsMode;
		}
		return $optionsMode;
	}

    /**
     * returns the full instance identifier for a given $instance and $mode
     * @param string $instance
     * @param string|null $mode
     * @return string full identifier
     */
    public static function instanceIdent(string $instance, $mode = '')
    {
		$mode === false && ($mode = '');
        $delim = self::modeDelim();
        return "{$instance}{$delim}{$mode}";
    }

	/**
	 * returns the instance name for a given instance specification
	 * @param string $instance to parse for instance name
	 * @return string
	 */
	public static function parseInstance($instance, $options = [])
	{
        $optionsInstance = 
            array_key_exists(self::instanceOption(), $options)
            ? $options[self::instanceOption()]
            : self::defaultInstance();
		if (is_string($instance)) {
			$modeDelimPosition = strpos($instance, self::modeDelim());
			if ($modeDelimPosition) {
				$instance = substr($instance, 0, $modeDelimPosition);
				return $instance ? $instance : $optionsInstance;
			}
			return !in_array($instance, self::availableModes()) ? $instance : $optionsInstance;
		}
		return $optionsInstance;
	}

    /**
	 * @param string mode name of framework kickstarting mode
	 * @return string class for the mode manager
	 */
	public static function getModeClass($mode)
	{
		$classString = str_replace(' ', '', ucwords(str_replace('-', ' ', $mode))); #TODO externalize this to Auto
		$namespaceString = __NAMESPACE__;
        $firstSub = strpos($namespaceString, '\\');
        $rootNamespaceString = substr($namespaceString, 0, $firstSub !== false ? $firstSub : null);
		return "{$rootNamespaceString}\\Framework\\Mode\\{$classString}";
	}

	/**
	 * Scans for supported Framework modes
	 * @return array list of modes
	 */
	public static function detectAvailableModes()
	{
		return [];
	}	

    /**
     * Tests $mode for compatability with $instance
	 * @param string $instance 
	 * @param string $mode mode name of framework kickstarting mode
     * @param array $options additional instance options to test for compatability with $mode
	 * @return bool true if $mode seems applicable to $instance
	 */
	public static function test($instance, $mode, $options = [])
	{
        #TODO  #2.0.0 look for cached answers (upstream, e.g. in trait implementers) to this question since it's intensive
		$modeClass = self::getModeClass($mode);
        return self::loadModeClass($modeClass) && $modeClass::detect($instance, $options);
		#TODO  #2.0.0 cacheMode($mode, $instance); again upstream
	}

	/**
	 * assesses an instance and returns a different mode if it can be better supported
	 * may alter the passed instance $options to make transitions possible
	 * @param string $instance name
	 * @param string $mode requested
	 * @param array $options
	 * @return string supported alternative to $mode
	 */
	public static function negotiate($instance, $mode, &$options = [])
	{
		$modeClass = self::getModeClass($mode);
		return 
            self::loadModeClass($modeClass) 
            ? $modeClass::negotiate($instance, $mode, $options)
            : false;
	}

    /**
     * Instantiates a Framework mode manager
     */
    protected static function loadModeClass($modeClass)
    {
        class_exists($modeClass, false) || Auto::loadInternalClass($modeClass, false);
        return class_exists($modeClass, false);
    }

}
