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
	public const OPTION_DEFAULT_SOURCES = 'defaultSources';

	public const DEFAULT_PUBLIC_PATH = '.';
	public const DEFAULT_INSTALL_PATH = '..';
	public const DEFAULT_AUTOLOAD = true;

	public const INTERPOLATE_START = '{$';
	public const INTERPOLATE_END = '}';

	public const SOURCE_INSTANCE = 100;	
	public const SOURCE_JSON_FILE = 101;
	public const SOURCE_XML_FILE = 102;
	public const SOURCE_ROOT_FILE = 103;
	public const SOURCE_GLOBAL_ENVIRONMENT = 1;
	public const SOURCE_GLOBAL_CONSTANT = 2;
	public const SOURCE_DOTFILES = 3;
	public const SOURCE_FIRST_INSTANCE = 0;
	public const SOURCE_DEFAULT = null;

	protected const UNLIMITED = -1;

	protected static $studlyDelim = "/([a-z\x80-\xbf\xd7\xdf-\xff][A-Z\xc0-\xd6\xd8-\xde])/";
	protected static $constantMatch = "/^[A-Z\xc0-\xd6\xd8-\xde]+[_A-Z\xc0-\xd6\xd8-\xde]*$/";
	protected static $environmentMatch = "/^[a-zA-Z\x80-\xff]*$/";

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
		self::OPTION_INSTALL_PATH => self::DEFAULT_INSTALL_PATH,
		self::OPTION_DEFAULT_SOURCES => [
			self::SOURCE_FIRST_INSTANCE,
			self::SOURCE_DOTFILES
		],
	];

	/**
	 * stores the options passed for each instance
	 */
	protected static $instanceOptions = [];

	/** 
	 * sets the behavior of operations when no instance is specified (see SCOPE_ constants)
	 */
	protected static $autoscope = null;

	/**
	 * locks the state of $autoscope
	 */
	protected static $lockAutoscope = false;

	/**
	 * cache of loaded root/canisters, reduces overhead from multiple loads
	 */
	protected static $canisters = []; #TODO centralize these for caching

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
	 * Attempts to get values for CONSTANT_NAME or environmentName automatically
	 * @param string|array one or more values to load
	 * @param string|array one or more sources to pull from
	 * @return string|array returns matched value(s)
	 */
	public static function load($list, $sources = self::SOURCE_DEFAULT)
	{
		$returnSingle = !is_array($list);
		$results = [];
		if ($returnSingle) {
			$list = [$list];
		}
		if (!is_array($sources)) {
			$sources = 
				$sources === self::SOURCE_DEFAULT
				? self::$defaultOptions[self::OPTION_DEFAULT_SOURCES]
				: [$sources];
		}
		foreach($list as $lookup) {
			foreach($sources as $sourceIdent => $source) {
				if(is_int($source)) {
					$result = self::lookup($lookup, $source);
				} else {
					$result = self::lookup($lookup, $source, $sourceIdent);
				}
				if(!is_null($result)){
					$results[$lookup] = $result;
					break;
				}
			}
			if(!array_key_exists($lookup, $results)) {
				$results[$lookup] = null;
			}
		}
		return $results;
	}


	/**
	 * 
	 * @param string $name
	 * @param int|array $source SOURCE_TYPE_*
	 * @param string|null $path 
	 * @return mixed lookup result
	 */
	public static function lookup(string $name, int $source, ?string $path = null)
	{
		try {
			$envName = self::envToOption($name);
			switch ($source) {
				case self::SOURCE_INSTANCE :
				case self::SOURCE_FIRST_INSTANCE :
					$instance = 
						$source == self::SOURCE_INSTANCE 
						? $path 
						: array_key_first(self::$instanceOptions);
					return self::instanceOption($instance, $envName);
					break;
				case self::SOURCE_ROOT_FILE :
					if(
						!array_key_exists($path, self::$canisters)
						&& file_exists($path) 
						&& is_readable($path)
					) {
						self::$canisters[$path] = include($path);

					}
					#TODO #2.0.0 meditate on file errors?
					$sourceCanister = 
						array_key_exists($path, self::$canisters)
						? self::$canisters[$path]
						: [];
					return 
						array_key_exists($envName, $sourceCanister) 
						? $sourceCanister[$envName]
						: null;
					break;
				case self::SOURCE_DOTFILES :
					return self::find($name);
					break;
				default:
					return null;
			}
		} catch (Saf\Exception\NoResource $e) {
			return null;
		}
	}

	/**
	 * Attempts to set CONSTANT_NAME => value pairs
	 * @param array one or more sources to pull from
	 */
	public static function dump(array $map = array())
	{
		foreach($map as $constant => $value) {
			defined($constant) || define($constant, $value);
		}
	}

	/**
	 * Returns the options for an instance, or a copy of the current default options
	 * @param null|string $instance
	 * @return array options reference, if $instance is null or not an initialized instance this is a distinct copy of the default options
	 */
	public static function &options(?string $instance)
	{
		if (!is_null($instance) && array_key_exists($instance, self::$instanceOptions)) {
			return self::$instanceOptions[$instance];
		} else {
			$defaultCopy = self::$defaultOptions;
			return $defaultCopy;
		}
	}

	/**
	 * Sets an option for a specific instance
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
     * Delegates request to Define::find, but blocks thrown failure from 
	 * @param string $constantName to find
	 * @param mixed $constantDefault if not found
	 * @param $cast Define::CAST_ type for found value casting, defaults to string
	 * @return mixed found value, default value, or null
     */
    public static function find(string $constantName, $constantDefault = null, $cast = Define::CAST_TYPE_STRING)
    {
		try{
	        return Define::find($constantName, $constantDefault, $cast);
		} catch (Saf\Exception\NoResource $e) {
			return null;
		}
    }

	/**
	 * expands the options for an instance using a list of values to set
	 * each entry in the list can be a value, parsable variable string (see ::parse), 
	 * or null to fallback to searching.
	 * @param string $instance
	 * @param string $list of options to set
	 */
	public static function expand(string $instance, array $list)
	{
		foreach ($list as $option => $default){
			if (is_null(self::instanceOption($instance, $option))) {
				if (is_null($default)) {
					$parsedDefault = self::find(self::optionToEnv($option));
				} else {
					$parsedDefault = 
						strpos($default, self::INTERPOLATE_START) !== false
						? self::parse($default, self::$instanceOptions[$instance])
						: $default;
				}
				if (!is_null($parsedDefault)) {
					self::set($instance, $option, $parsedDefault);
				}
			}
		}
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
	 * converts standard camelCase/StudlyCase to CONSTANT_CASE
	 * @param string $string camelCase/StudlyCase format
	 * @return string CONSTANT_CASE format
	 */
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

	/**
	 * converts CONSTANT_CASE to camelCase/StudlyCase
	 * @param string $string CONSTANT_CASE format
	 * @return string camelCase/StudlyCase format depending on $studly
	 */
	public static function envToOption(string $string, bool $studly = false)
	{
		$convertedString = str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $string))));
		return $studly ? $convertedString : lcfirst($convertedString);		
	}

	/**
	 * returns or sets the JIT setting for the foundation
	 * @param null|bool new setting
	 * @return bool current setting
	 */
	public static function allowJitHits($set = null)
	{
		if (!is_null($set)) {
			self::$allowJitHits = $set;
		}
		return self::$allowJitHits;
	}

	/**
	 * delegate autoloading internal, modernized(namespaced), SAF class
	 * cannot load legacy objects in /lib 
	 */
	public static function autoload($string)
	{
		#TODO more anaylsis on how to route
		Auto::loadInternalClass($string);
	}

	/**
	 * returns the value for $option in $instance's options, 
	 * or $default if it is not present
	 * @param string|array $instance to search, or search the provided array
	 * @param string $option to return
	 * @param mixed $default if no match is found
	 * @return mixed matching option value, or default
	 */
	public static function instanceOption($instance, string $option, $default = null)
	{
		if (is_array($instance)) {
			$search = $instance;
		} elseif (array_key_exists($instance, self::$instanceOptions)) {
			$search = self::$instanceOptions[$instance];
		} else {
			return $default;
		}
		return array_key_exists($option, $search) ? $search[$option] : $default;
	}

	/**
	 * returns the stored path constant for a given path type
	 * @param string $type (install, public, etc).
	 * @return string|null path
	 */
	public static function path($type, $instance = null)
	{
		$path = strtoupper("{$type}_PATH");
		$default = strtoupper("self::DEFAULT_{$path}");
		return
			defined(self::constant($path)) 
			? constant(self::constant($path))
			: (defined($default) ? constant($default) : null);
	}

	/**
	 * returns the registered transaction start time
	 * @return float|null start time
	 */
	public static function startTime($instance = null)
	{
		return 
			defined(self::constant(self::OPTION_START_TIME)) 
			? constant(self::constant(self::OPTION_START_TIME)) 
			: null;
	}

	/**
	 * returns a namespace insulated constant.
	 * Constants not "dumped" by this object are insulated.
	 * @param string global constant name
	 * @return string Saf\Environment namespaced constant of the same name
	 */
	public static function constant($string)
	{
		return (__NAMESPACE__ . "/{$string}");
	}

	/**
	 * tests if $option is present and set in an $options set
	 * @param string $option to check
	 * @param array|string|null $options list to search
	 * @return bool exists and not null 
	 */	
	public static function optionIsSet(string $option, ?array $options)
	{
		return 
			!is_null($options)
			&& array_key_exists($option, $options)
			&& !is_null($options[$option]);
	}

	
	/**
	 * tests if $option is present in an $options set
	 * @param string $option to check
	 * @param array|null $options list to search
	 * @return bool exists
	 */	
	public static function hasOption(string $option, ?array $options)
	{
		return 
			!is_null($options)
			&& array_key_exists($option, $options);
	}

	/**
	 * loads the transaction start time into insulated environment from: already set options, 
	 * server environment if present and allowed, or the current time, in that order of preference.
	 * @param array|null $options source for existing captured start time
	 */
	protected static function detectStartTime(?array $options = [])
	{
		$start = 
			self::optionIsSet(self::OPTION_START_TIME, $options)
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