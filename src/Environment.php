<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing and insulating environment
 */

namespace Saf;

use Saf\Auto;
use Saf\Environment\Define;

require_once(dirname(__FILE__) . '/Auto.php');
require_once(dirname(__FILE__) . '/Environment/Define.php');

class Environment
{
	
	public const OPTION_INSTANCES = 'instances';
	public const OPTION_INHERITS = 'inherits';
	public const OPTION_START_TIME = 'startTime'; 
	public const OPTION_PUBLIC_PATH = 'publicPath';
	public const OPTION_INSTALL_PATH = 'installPath';
	public const OPTION_STUDLY_DELIM = 'studlyDelim';

	public const DEFAULT_PUBLIC_PATH = '.';
	public const DEFAULT_INSTALL_PATH = '..';
	public const DEFAULT_AUTOLOAD = true;

	protected static $studlyDelim = "/[a-z\x80-\xbf\xd7\xdf-\xff][A-Z\xc0-\xd6\xd8-\xde]/";

	/**
	 * allows (neglegible) JIT hits to performance
	 */
	protected static $allowJitHits = true;

	/**
	 * default options to pass to the environment if absent in the options 
	 * passed to lace()
	 */
	protected static $defaultOptions = [
		self::OPTION_START_TIME => null,
		self::OPTION_PUBLIC_PATH => self::DEFAULT_PUBLIC_PATH,
		self::OPTION_INSTALL_PATH => self::DEFAULT_INSTALL_PATH
	];

	/**
	 * stores the options passed for each instance
	 */
	protected static $instanceOptions = [];

	/**
	 * performs initialization of core environment settings and instances
	 * @param array $options
	 * @param string $instance or null
	 */
	public static function &init(array &$options, ?string $instance = null)
	{
		$reference = null;
		if ($instance){
			$reference = &self::initInstance($instance, $options);
			return $reference;
		} elseif(!defined(self::OPTION_START_TIME)) {
			$options += self::$defaultOptions;
			self::detectStartTime($options);
			self::detectPath('public', $options);
			self::detectPath('install', $options);
			if (array_key_exists(self::OPTION_STUDLY_DELIM,$options)) {
				self::$studlyDelim = $options[self::OPTION_STUDLY_DELIM];
			}
		}
		return $reference;
	}

    /**
     * Delegates the request to find values to Environment\Define
     */
    public static function find($constantName, $constantDefault, $cast = Define::CAST_TYPE_STRING)
    {
        Define::find($constantName, $constantDefault, $cast);
    }

	/**
	 * Returns the options for an instance
	 * @param string $instance
	 * @return array options
	 */
	public static function &options(string $instance)
	{
		if (array_key_exists($instance, self::$instanceOptions)) {
			return self::$instanceOptions[$instance];
		} else {
			$nullReference = null;
			return $nullReference;
		}
	}

	/**
	 * @param string $instance
	 * @param string $option
	 * @param mixed $value
	 */
	public static function set(string $instance, string $option, $value)
	{
		if (array_key_exists($instance, self::$instanceOptions)) {
			self::$instanceOptions[$instance][$option] = $value;
		}
	}

	/**
	 * 
	 */
	public static function prep(string $instance, array $list)
	{ #TODO, why here and not in init/instanceInit?
		foreach ($list as $option => $default){
			if (is_null(self::instanceOption($instance, $option))) {
				$default = self::parse($instance, $default);
				self::set($instance, $option, self::find(self::optionToEnv($option), $default));
			}
		}
	}

	public static function optionToEnv(string $string)
	{
		$parts = preg_split(self::$studlyDelim,$string);
		return strtoupper(implode('_',$parts));
	}

	public static function envtoOption(string $string)
	{
		return lcfirst(
			str_replace(' ', '', ucwords(
				strtolower(str_replace('_', ' ', $string))
			))
		);		
	}

	/**
	 * parses variable default options
	 * @param string $instance
	 * @param string $string to be evaluated
	 * @return string parsed $string value
	 */
	public static function parse(string $instance, string $string)
	{ #TODO flesh this out, decide what to support (find, instanceOption, etc.)
		return 
			$string === '$instance'
			? $instance
			: $string;
	}

	/**
	 * returns or sets the JIT setting for the foundation
	 */
	public static function allowJitHits($set = null)
	{
		if (!is_null($set)) {
			self::$allowJitHits = $set;
		}
		return self::$allowJitHits;
	}

	public static function autoload($string)
	{
		#TODO more anaylsis on how to route
		Auto::loadInternalClass($string);
	}

	/**
	 * returns the value for $option in $instance's options, 
	 * or $default if it is not present
	 * @param string $instance to search 
	 * @param string $option to return
	 * @param mixed $default if no match is found
	 * @return mixed matching option value, or default
	 */
	public static function instanceOption(string $instance, string $option, $default = null)
	{
		return 
			array_key_exists($instance, self::$instanceOptions)
			&& array_key_exists($option, self::$instanceOptions[$instance]) 
			? self::$instanceOptions[$instance][$option] 
			: $default;
	}

	/**
	 * 
	 */
	public static function path($type)
	{
		$path = strtoupper("{$type}_PATH");
		$default = strtoupper("self::DEFAULT_{$path}");
		return
			defined($path) 
			? constant($path)
			: (defined($default) ? constant($default) : null);
	}

	/**
	 * 
	 */
	public static function startTime()
	{
		return defined(self::OPTION_START_TIME) ? constant(self::OPTION_START_TIME) : null;
	}

	/**
	 * @param array $options
	 */
	protected static function detectStartTime(array &$options)
	{
		$start = 
			array_key_exists(self::OPTION_START_TIME, $options)
				&& !is_null($options[self::OPTION_START_TIME])
			? $options[self::OPTION_START_TIME]
			: (
				self::allowJitHits() 
					&& isset($_SERVER) 
					&& is_array($_SERVER)
					&& array_key_exists('REQUEST_TIME_FLOAT', $_SERVER) 
				? $_SERVER['REQUEST_TIME_FLOAT'] 
				: microtime(true)
			);
		define(self::OPTION_START_TIME, $start);
		$options[self::OPTION_START_TIME] = $start;
	}

	/**
	 * @param string $path
	 * @param array $options
	 */
	protected static function detectPath(string $pathType, array &$options)
	{
		$target = strtoupper("{$pathType}_PATH");
		$source = strtoupper("OPTION_{$pathType}_PATH");
		$default = strtoupper("DEFAULT_{$pathType}_PATH");
		$defaultValue = defined("self::{$default}") ? constant("self::{$default}") : '.';
		$value = 
			defined("self::{$source}") && array_key_exists(constant("self::{$source}"), $options)
				? $options[constant("self::{$source}")]
				: $defaultValue;
		defined($target) || define($target,	$value);
		$options[self::envToOption($target)] = $value;
	}

	/**
	 * 
	 */
	protected static function &initInstance($instance, $options)
	{
		#TODO #2.0.0 handle multi-instance logic
		if (!array_key_exists($instance, self::$instanceOptions)) {
			#TODO if (array_key_exists(OPTION_INSTANCES, $options)) {
			#} else {
			self::$instanceOptions[$instance] = $options;
		}
		$reference = &self::$instanceOptions[$instance];
		return $reference;
	}
}