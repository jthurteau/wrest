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

	public const INTERPOLATE_START = '{$';
	public const INTERPOLATE_END = '}';

	protected const UNLIMITED = -1;

	protected static $studlyDelim = "/([a-z\x80-\xbf\xd7\xdf-\xff][A-Z\xc0-\xd6\xd8-\xde])/";

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
	#TODO #2.0.0 store the shared reference for internal writing/updates and a separate internal canonical copy

	/**
	 * performs initialization of core environment settings and instances
	 * @param array $options
	 * @param string $instance or null
	 */
	public static function init(array &$options, ?string $instance = null)
	{
		foreach(self::$defaultOptions as $key => $value) {
			if (!array_key_exists($key, $options)) {
				$options[$key] = self::$defaultOptions[$key];
			}
		}
		if(!defined(self::constant(self::OPTION_START_TIME))) {
			//$initOptions = $options + self::$defaultOptions;
			self::detectStartTime($options);
			self::detectPath('public', $options);
			self::detectPath('install', $options);
			if (array_key_exists(self::OPTION_STUDLY_DELIM, $options)) {
				self::$studlyDelim = $options[self::OPTION_STUDLY_DELIM];
			}
		}
		if (!is_null($instance)){
			self::initInstance($instance, $options);
			$options['instanceName'] = $instance;
		}
	}

    /**
     * Delegates the request to find values to Environment\Define
     */
    public static function find($constantName, $constantDefault, $cast = Define::CAST_TYPE_STRING)
    {
        return Define::find($constantName, $constantDefault, $cast);
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
			return self::$defaultOptions;
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
	{
		foreach ($list as $option => $default){
			if (is_null(self::instanceOption($instance, $option))) {
				$parsedDefault = 
					strpos($default, self::INTERPOLATE_START) !== false
					? self::parse($default, self::$instanceOptions[$instance])
					: $default;
				if (!is_null($parsedDefault)) {
					self::set($instance, $option, self::find(
						self::optionToEnv($option), 
						$parsedDefault
					));
				}
			}
		}
	}

	public static function optionToEnv(string $string)
	{
		$parts = preg_split(self::$studlyDelim, $string, self::UNLIMITED, \PREG_SPLIT_DELIM_CAPTURE);
		for($i = 0; $i< count($parts); $i++){
			if (
				array_key_exists($i + 1, $parts) 
				&& strlen($parts[$i + 1]) == 2
				&& array_key_exists($i + 2, $parts) 
			) {
				$parts[$i] .= substr($parts[$i + 1], 0, 1);
				$parts[$i + 2] = substr($parts[$i + 1], 1, 1) . $parts[$i + 2];
				array_splice($parts, $i+1, 1);
			}
		}
		return strtoupper(implode('_', $parts));
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
	 * @param string $string to be evaluated
	 * @param array $options exisiting values to parse from
	 * @return null|string parsed $string value or null if unparsable (i.e. unbound variables)
	 */
	public static function parse(string $string, array $options)
	{
		$parts = explode(self::INTERPOLATE_START, $string);
		$result = [array_shift($parts)];
		for($i = 0; $i< count($parts); $i++){
			$subParts = explode(self::INTERPOLATE_END, $parts[$i], 2);
			$optionKey = $subParts[0];
			if (!array_key_exists($optionKey, $options)) {
				return null;
			}
			$result[] = $options[$optionKey];
			if (array_key_exists(1, $subParts) && '' !== $subParts[1]) {
				$result[] = $subParts[1];
			}
		}
		return implode('', $result);
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
			defined(self::constant($path)) 
			? constant(self::constant($path))
			: (defined($default) ? constant($default) : null);
	}

	/**
	 * 
	 */
	public static function startTime()
	{
		return 
			defined(self::constant(self::OPTION_START_TIME)) 
			? constant(self::constant(self::OPTION_START_TIME)) 
			: null;
	}

	public static function constant($string)
	{
		return (__NAMESPACE__ . "/{$string}");
	}

	/**
	 * @param array $options
	 */
	protected static function detectStartTime(array $options)
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
		define(self::constant(self::OPTION_START_TIME), $start);
		self::$defaultOptions[self::OPTION_START_TIME] = $start;
	}

	/**
	 * @param string $path
	 * @param array $options
	 */
	protected static function detectPath(string $pathType, array $options)
	{
		$target = strtoupper("{$pathType}_PATH");
		$source = strtoupper("OPTION_{$pathType}_PATH");
		$default = strtoupper("DEFAULT_{$pathType}_PATH");
		$defaultValue = defined("self::{$default}") ? constant("self::{$default}") : '.';
		$value = 
			defined("self::{$source}") && array_key_exists(constant("self::{$source}"), $options)
				? $options[constant("self::{$source}")]
				: $defaultValue;
		defined(self::constant($target)) || define(self::constant($target),	$value);
		self::$defaultOptions[self::envToOption($target)] = $value;
	}

	/**
	 * 
	 */
	protected static function &initInstance($instance, &$options)
	{
		#TODO #2.0.0 handle multi-instance logic
		if (!array_key_exists($instance, self::$instanceOptions)) {
			#TODO if (array_key_exists(OPTION_INSTANCES, $options)) {
			#} else {
			self::$instanceOptions[$instance] = &$options;
		}
		$reference = &self::$instanceOptions[$instance];
		return $reference;
	}

}