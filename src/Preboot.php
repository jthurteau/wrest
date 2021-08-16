<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing preboot (kickstart, framework agnostic) steps
 */

namespace Saf;

use Saf\Kickstart;
use Saf\Auto;
#use Saf\Legacy\Autoloader;
use Saf\Resolver;
use Saf\Agent;
use Saf\Environment;

#require_once(__DIR__ . '/Kickstart.php');
require_once(__DIR__ . '/Auto.php');
require_once(__DIR__ . '/Resolver.php');
require_once(__DIR__ . '/Agent.php');
require_once(__DIR__ . '/Environment.php');

class Preboot
{
	/**
	 * stores order and state of various preboot steps
	 * false indicates that step will not be auto-attempted
	 * null indicates the step has not been attempted yet
	 * otherwise each value is an array of the instance idents that executed the step
	 */
	protected static $steps = [
		self::STEP_LIBXML => null,
		self::STEP_TZ => null,
	];
	
	public const STEP_LIBXML = 'LibXml';
	public const STEP_TZ = 'TimeZone';
	public const STEP_AUTOLOAD = 'Autoload';

	/**
	 * steps that can't wait for a bootstrap to kick in, 
	 * @param string $instance
	 * @param string $mode
	 */
	public static function go(?string $instance, ?string $mode)
	{
		$mode = 
			$mode == Agent::MODE_AUTODETECT 
			? Agent::MODE_NONE 
			: $mode;
		$options = &Environment::options($instance);
		Environment::expand($instance, Kickstart::INSTANCE_DEFAULTS); #TODO #2.0.0 expand Environment::parse so it can handle managerClass, autoload, etc.
		Environment::set($instance, Kickstart::OPTION_RESOLVER, self::resolve($instance, $options));
		if (array_key_exists('meditationScript', $options)) {
			Agent::setMeditation($options['meditationScript']);
		}
		if (
			$mode != Agent::MODE_NONE
			&& is_null(Environment::instanceOption($instance, Kickstart::OPTION_MANAGER))
		) {
			Environment::set($instance, Kickstart::OPTION_MANAGER, Agent::getModeClass($mode));
		} elseif ($mode === Agent::MODE_NONE) {
			self::$steps[self::STEP_AUTOLOAD] = null;
		}
		if (is_null(Environment::instanceOption($instance, Kickstart::OPTION_AUTOLOAD))) {
			Environment::set($instance, Kickstart::OPTION_AUTOLOAD, Environment::DEFAULT_AUTOLOAD);
		}
		foreach(self::$steps as $prebootOption => $value) {
			if (is_null($value)) {
				self::step($prebootOption, $instance, $mode);
			}
		}
		$modeClass = Environment::instanceOption($instance, Kickstart::OPTION_MANAGER);
		if ($modeClass && class_exists($modeClass, false)) { #TODO #2.0.0 change autoload to a step for custom ordering
			if (Environment::instanceOption($instance, Kickstart::OPTION_AUTOLOAD)){
				$modeClass::autoload($instance, $options);
			}
			$modeClass::preboot($instance, $options, self::$steps);
		} elseif ($modeClass) {
			//Environment::autoload($modeClass);
			//#TODO #2.0.0 warn about invalid mode class?
		}
	}

    /**
	 * Handling for various preboot steps, #TODO #2.0.0 move into separate Saf\Preboot class
	 * @param string $step matching PREBOOT_STEP_*
	 * @param string $instance 
	 * @param string $mode
	 */
	protected static function step($step, $instance = 'INTERNAL', $mode = 'INIT')
	{
		$options = &Environment::options($instance);
		if (key_exists($step, self::$steps) && self::$steps[$step]) {
			$ident = Agent::instanceIdent($instance, $mode);
			if (
				self::$steps[$step] == $ident 
				|| in_array($ident, self::$steps[$step])
			) {
				return self::$steps[$step];
			}
		}
		switch ($step) {
			case self::STEP_LIBXML :
				if (function_exists('libxml_use_internal_errors')) {
					libxml_use_internal_errors(true);
				} else {
					Agent::mediate('libxml_use_internal_errors() not supported.', 'NOTICE');
				}
				break;
			case self::STEP_TZ :
				if (defined('DEFAULT_TIMEZONE')) {
					date_default_timezone_set(DEFAULT_TIMEZONE);
				}				
				break;
				case self::STEP_AUTOLOAD :
					$options = &Environment::options($instance, $options);
					self::defaultAutoloader($instance, );
					break;
			default:
			#TODO implement plugins?
		}
		if (!key_exists($step, self::$steps) || !self::$steps[$step]) {
			self::$steps[$step] = [Agent::instanceIdent($instance, $mode)];
		} else {
			self::$steps[$step][] = Agent::instanceIdent($instance, $mode);
		}
	}
    
    /**
	 * returns a resolver to help other frameworks route cases not natively 
	 * supported (like multi-views)
	 */
	public static function resolve($instance, $options)
	{
		return Resolver::init($instance, $options);
	}


	/**
	 * 
	 */
	public static function defaultAutoloader($instance, $options = [])
	{
        $installPath = 
			key_exists('installPath', $options)
			? $options['installPath']
			: Environment::DEFAULT_INSTALL_PATH;
        $srcPath = 
			key_exists('srcPath', $options)
			? $options['srcPath']
			: ("{$installPath}/src");
		$applicationPath = 
			key_exists('applicationPath', $options)
			? $options['applicationPath']
			: "{$installPath}/src/App";
		$vendorPath = 
            key_exists('vendorPath', $options) 
            ? $options['vendorPath']
            : '/var/www/application/vendor';
        $foundationPath = 
            key_exists('foundationPath', $options) 
            ? $options['foundationPath'] 
            : "{$vendorPath}/Saf/src";
		$safLoaderGenerator = function(string $path){
			return function(string $className) use ($path){
				return Auto::classPathLookup($className, "{$path}", 'Saf');
			};
		};
		$appLoaderGenerator = function(string $path){
			return function(string $className) use ($path){
				return Auto::classPathLookup($className, "{$path}/src/", 'App');
			};
		};
		$moduleLoaderGenerator = function(string $path){
			return function(string $className) use ($path){
				#TODO how to resolve the root of the module namespace
				$nameComponents = explode('\\', $className);
				if (count($nameComponents) < 2) {
					return null;
				}
				$moduleName = $nameComponents[0];
				return Auto::classPathLookup($className, "{$path}/module/{$moduleName}/src/", $moduleName);
			};
		};
		Auto::registerLoader(
			'App\\', 
			$appLoaderGenerator($applicationPath), 
			Auto::ADD_PREPEND
		);
		Auto::registerLoader(
			'Saf\\', 
			$safLoaderGenerator($foundationPath), 
			Auto::ADD_PREPEND
		);
		Auto::registerLoader(
			'', 
			$moduleLoaderGenerator($installPath), 
			Auto::ADD_PREPEND
		);
	}

}