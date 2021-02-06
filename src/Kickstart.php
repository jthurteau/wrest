<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for starting up an application and preparing the framework.
 * Also provides autoloading.
 */

namespace Saf;

use Saf\Brray; #TODO generalize to these cases: Options
use Saf\Debug;
use Saf\Resolver;
// use Saf\Environment\Autoloader as Autoloader; 
use Saf\Environment\Define;
use \PUBLIC_PATH;
use \INSTALL_PATH;
use \APPLICATION_INSTANCE as DEFAULT_INSTANCE;
use \APPLICATION_TZ as DEFAULT_TIMEZONE;
#use \TRANSACTION_START_TIME as START_TIME;

require_once(dirname(__FILE__) . '/Brray.php');
require_once(dirname(__FILE__) . '/Debug.php');
require_once(dirname(__FILE__) . '/Resolver.php');
// require_once(LIBRARY_PATH . '/Environment/Autoloader.php');
require_once(dirname(__FILE__) . '/Environment/Define.php');



//#TODO #1.0.0 update function header docs
class Kickstart {

	public const MODE_AUTODETECT = null;
	public const MODE_NONE = 'none';
	public const MODE_SAF = 'saf';
	public const MODE_MEZ = 'mezzio';
	public const MODE_LAMMVC = 'laminas-mvc'; //#TODO #2.1.0 support Laravel
	public const MODE_LF5 = 'laravel5'; //#TODO #2.1.0 support Laravel
	public const MODE_SLIM = 'slim'; //#TODO #2.1.0 support Slim
	public const MODE_ZFMVC = 'zendmvc'; //NOTE deprecated in 2.0
	public const MODE_ZFNONE = 'zendbare'; //NOTE deprecated in 2.0

	/**
	 * indicates the mode that each instance environment has been initialized
	 * @var array
	 */
	protected static $kicked = [];

	/**
	 * indicates that the bare minimum (e.g. autoload) for each instance 
	 * initialization has been performed
	 */
	protected static $laced = [];

	/**
	 * stores the options passed for each instance
	 */
	protected static $instanceOptions = [];

	/**
	 * specifies the path to the exception display view script
	 * defaults to (set to) APPLICATION_PATH . '/views/scripts/error/error.php'
	 * the first time exceptionDisplay() is called if not already set by
	 * setExceptionDisplayScript().
	 * @var string
	 */
	protected static $emergencyView = null;

	protected static function init()
	{
		if (defined('START_TIME')) {
			return;
		}
		$start = Define::get(
			'\TRANSACTION_START_TIME', 
			Define::get('\APPLICATION_START_TIME', microtime(true))
		);
		define('START_TIME', $start);
		$publicPath = Define::get('\PUBLIC_PATH', Define::find('PUBLIC_PATH', realpath('.')));
		defined('PUBLIC_PATH') || define('PUBLIC_PATH', $publicPath);
		$installPath = Define::get('\INSTALL_PATH', Define::find('INSTALL_PATH', realpath('..')));
		defined('INSTALL_PATH') || define('INSTALL_PATH', $installPath);
	}

	/**
	 * Begins the kickstart process, preparing the instance for a framework autowiring ($mode)
	 * @param string $mode Any of the class's MODE_ constants
	 * @return string the mode chosen (useful in the case of default MODE_AUTODETECT)
	 */
	public static function go($mode = self::MODE_AUTODETECT, $options = array())
	{
		self::init();
		$instance = self::selectInstance($options);
		self::instanceInitialize($instance);

		if (!self::$kicked[$instance]) {
			self::$instanceOptions[$instance] = $options;
			if (!self::$laced[$instance]) {
				self::lace($instance);
			}
		 	if ($mode == self::MODE_AUTODETECT) {
				$mode = self::goAutoMode($instance);
		 	}
		 	if ($mode == self::MODE_SAF) {
				self::goIdent($instance, $mode);
			}

			Define::load('\APPLICATION_STATUS', 'online');
			Define::load('\APPLICATION_BASE_ERROR_MESSAGE', 'Please inform your technical support staff.');
			Define::load('\APPLICATION_DEBUG_NOTIFICATION', 'Debug information available.');
		 	if ($mode == self::MODE_LF5) {
				self::goLaravel($instance);
				if (\APPLICATION_FORCE_DEBUG) {
					Debug::init(Debug::DEBUG_MODE_FORCE, null , false);
				}
				self::goResolver($instance);
				self::goIdent($instance);
			} else {
				//Define::load('APPLICATION_CONFIG', \APPLICATION_PATH . "/configs/{$configFile}.xml");
				Define::load('APPLICATION_FORCE_DEBUG', false, Define::TYPE_BOOL);
				if (\APPLICATION_FORCE_DEBUG) {
					Debug::init(Debug::DEBUG_MODE_FORCE, null , false);
				}
				self::goResolver($instance);
				if ($mode != self::MODE_SAF) {
					self::goIdent($instance);
				}
				if ($mode == self::MODE_ZFMVC || $mode == self::MODE_ZFNONE) {
					self::goZend($instance);
				}
				if ($mode == self::MODE_SAF) {
					self::goSaf($instance);
				}
			}
			self::goPreBoot($instance);
			self::$kicked[$instance] = $mode;
		}
 		return self::$kicked[$instance];
	}

	/**
	 * perform kickstart based on the provided $mode
	 */
	public static function kick($mode)
	{
		try {
			$fallbackMainScript = '../main.php';
			$modeClass = ucfirst($mode); #TODO externalize this
			$modeManagerClass = 'Saf\Framework\\' . $modeClass;
			$modeFile = dirname(__FILE__) . '/Framework/Saf.php';
			if (file_exists($modeFile)) {
				require_once($modeFile);
			}
			if (class_exists($modeManagerClass, false)) {
				return $modeManagerClass::run($instance);
			} else {
				if(file_exists($fallbackMainScript)){
					if (!is_readable($fallbackMainScript)){
						throw new Exception('Unable to access main application script.');
					}
					require_once($fallbackMainScript);
				} else {
					throw new Exception('No application to run.');
				}
			}
		} catch (Exception $e) { #TODO handle redirects and forwards
			header('HTTP/1.0 500 Internal Server Error');
			self::emergencyDisplay($e);
		}
	}	

	/**
	 * @return string the mode the application was started in
	 */
	public static function getMode($instance = null)
	{
		if(is_null($instance)) {
			$instance = self::selectInstance();
		}
		return array_key_exists($instance, self::$kicked) ? self::$kicked[$instance] : false;
	}

	/**
	 * @return array lists the known instances
	 */
	public static function getInstances()
	{
		return array_keys(self::$kicked);
	}

	/**
	 * Conveinence setup when not using the kickstart process.
	 * @param null $mode
	 */
	public static function bypass($instance = null, $mode = self::MODE_AUTODETECT)
	{
		self::goPreBoot($instance);
	}

	/**
	 * Outputs in the case of complete and total failure during the kickstart process.
	 * @param Exception $e
	 * @param string $caughtLevel
	 * @param string $additionalError
	 */
	public static function emergencyDisplay($e, $caughtLevel = 'BOOTSTRAP', $additionalError = '')
	{
		$rootUrl = defined('\APPLICATION_BASE_URL') ? \APPLICATION_BASE_URL : '';
		$title = 'Configuration Error';
		if (is_null(self::$emergencyView)) {
			self::$emergencyView = self::findFallbackEmergencyDisplay();
		}
		if (self::$emergencyView) {
			include(self::$emergencyView);
		} else {
			header('HTTP/1.0 500 Internal Server Error');
			die($e->getMessage());
		}
		if (class_exists('Debug', false)) {
			Debug::dieSafe();
		}
	}

	/**
	 * sets the path to the php script used by exceptionDisplay()
	 * @param string $path
	 */
	public static function setEmergencyDisplayScript($path)
	{
		self::$emergencyView = realpath($path);
	}


	protected static function lace($instance)
	{
		#TODO autoloading also belongs here

		#NOTE below is deprecated at this stage as the container/servicemanager should abstract such concerns
		// $detectedAppPath =
		// 	realpath(\INSTALL_PATH . '/application')
		// 	? realpath(\INSTALL_PATH . '/application')
		// 	: realpath(\INSTALL_PATH . '/app');
		// Define::load('APPLICATION_PATH', $detectedAppPath);
		// if (
		// 	'' == \APPLICATION_PATH 
		// 	|| (realpath(\INSTALL_PATH . '/application') && '' == realpath(\APPLICATION_PATH . '/configs'))
		// 	|| !is_readable(\APPLICATION_PATH)
		// ) {
		// 	header('HTTP/1.0 500 Internal Server Error');
		// 	die('Unable to find the application core.');	 		
		// }
		self::$laced[$instance] = true;
	}

	protected static function goAutoMode($instance)
	{
		$detectableModes = [
			self::MODE_SAF, 
			self::MODE_MEZ,
			self::MODE_LAMMVC,
			self::MODE_LF5,
			self::MODE_SLIM
		];
		foreach($detectableModes as $mode)
		{
			if(self::detectMode($mode, $instance)){
				return $mode;
			}
		}
		return self::MODE_NONE;
	}

	protected static function goIdent($instance, $mode = null)
	{
		Define::load('APPLICATION_ENV', 'production');
		Define::load('APPLICATION_ID', $instance);
	}

	/**
	 * steps to prep the resolver (route/pipe)
	 */
	protected static function goResolver($instance, $mode = null)
	{
		Resolver::init($instance, $mode);
	}
	
	/**
	 * steps that can't wait for a bootstrap to kick in
	 */
	protected static function goPreBoot($instance = null, $mode = null)
	{
		#TODO when this list gets long, farm it out to another class
		if (function_exists('libxml_use_internal_errors')) {
			libxml_use_internal_errors(true);
		} else {
			Debug::out('Unable to connect LibXML to integrated debugging. libxml_use_internal_errors() not supported.', 'NOTICE');
		}
		if (defined('DEFAULT_TIMEZONE')) {
			date_default_timezone_set(DEFAULT_TIMEZONE);
		}
	}

	/**
	 * handles any internal initialization for referenced instances
	 */
	protected static function instanceInitialize($instance)
	{
		if (!array_key_exists($instance, self::$kicked)) {
			self::$kicked[$instance] = false;
			self::$laced[$instance] = false; 
		}
	}

	/**
	 * 
	 */
	protected static function selectInstance($options = [])
	{
		$defaultInstance = 
			defined('APPLICATION_ID') && defined('APPLICATION_INSTANCE')
			? (
				APPLICATION_ID . (
					strpos(APPLICATION_INSTANCE, '_') === 0
					? APPLICATION_INSTANCE 
					: ('_' . APPLICATION_INSTANCE)
				)
			) : (defined('DEFAULT_INSTANCE') ? DEFAULT_INSTANCE : 'LOCAL_INSTANCE');
		return Brray::extractIfNotBlank('instance', $options, Brray::TYPE_NONE, $defaultInstance);
		#TODO normalize the string (e.g. upper,-/ws to _,strip other non-alphanum?)
	}

	/**
	 * @return bool true if $mode seems applicable to $instance
	 */
	protected static function detectMode($mode, $instance)
	{
		$modeClass = ucfirst($mode); #TODO externalize this
		$modeManagerClass = 'Saf\Framework\\' . $modeClass;
		require_once(dirname(__FILE__) . '/Framework/Saf.php');
		return $modeManagerClass::detect($instance);
	}

	/**
	 * 
	 */
	protected static function findFallbackEmergencyDisplay()
	{
		$possibilities = [
			'../error.php'
		];
		foreach($possibilities as $path){
			if (file_exists($path)) {
				return $path;
			}
		}
		return realpath(\APPLICATION_PATH . '/views/scripts/error/error.php');
	}

}
