<?php //#SCOPE_OS_PUBLIC
namespace Saf;
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for starting up an application and preparing the framework.
Also provides autoloading.

*******************************************************************************/
// require_once(LIBRARY_PATH . '/Saf/Environment/Autoloader.php');
// use Saf\Environment\Autoloader as Autoloader; 
require_once(LIBRARY_PATH . '/Saf/Environment/Path.php');
use Saf\Environment\Path as Path;
require_once(LIBRARY_PATH . '/Saf/Environment/Define.php');
use Saf\Environment\Define as Define;
require_once(LIBRARY_PATH . '/Saf/Cast.php');

require_once(LIBRARY_PATH . '/Saf/Filter/Truthy.php');
require_once(LIBRARY_PATH . '/Saf/Status.php');
require_once(LIBRARY_PATH . '/Saf/Debug.php');
require_once(LIBRARY_PATH . '/Saf/Layout.php');

//#TODO #1.0.0 update function header docs
class Kickstart {

	const MODE_AUTODETECT = NULL;
	const MODE_NONE = 'none';
	const MODE_SAF = 'saf';
	const MODE_ZFMVC = 'zendmvc';
	const MODE_ZFNONE = 'zendbare';
	const MODE_LF5 = 'laravel5'; //#TODO #2.0.0 support Laravel
	const MODE_ZF3 = 'zend3'; //#TODO #2.0.0 support Zend 3

	const REGEX_VAR =
		'/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';
	const REGEX_CLASS =
		'/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s{]/';
	const REGEX_PARENT_CLASS =
		'/class\s+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s+extends\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+[\s{]/';


	/**
	 * indicates that the bare minimum for autoload has been initialized
	 */
	protected static $_laced = FALSE;

	/**
	 * indicates the mode that the environment as been initialized in
	 * @var string
	 */
	protected static $_kicked = NULL;

	/**
	 * indicates that the autoloader internal lists have been normalized
	 */
	protected static $_initialized = FALSE;

	/**
	 * indicates that the internal autoloader is in charge
	 * @var bool
	 */
	protected static $_autoloadingInstalled = FALSE;

	/**
	 * list of class prefixes and paths for autoloading
	 * @var array
	 */
	protected static $_autoloaders = array(
		'' => array(
			'[[APPLIATION_PATH]]/models',
		)
	);

	/**
	 * list of named autoloaders and the callable they use for resolution
	 * @var array
	 */
	protected static $_specialAutoloaders = array(
		'controller' => array(
			'Saf\Environment\Path::resolveControllerPath' => 'Saf\Environment\Path::resolveControllerPath'
		)
	);

	/**
	 * list of library autoloaders and the callable they use for resolution
	 * @var array
	 */
	protected static $_libraries = array(
		'Saf' => array(
			'Saf\Environment\Path::resolveClassPath' => 'Saf\Environment\Path::resolveClassPath'
		)
	);

	/**
	 * specifies the path to the exception display view script
	 * defaults to (set to) APPLICATION_PATH . '/views/scripts/error/error.php'
	 * the first time exceptionDisplay() is called if not already set by
	 * setExceptionDisplayScript().
	 * @var string
	 */
	protected static $_exceptionView = NULL;

	protected static function _lace()
	{
		defined('APPLICATION_START_TIME') || define('APPLICATION_START_TIME', microtime(TRUE));
		Define::load('PUBLIC_PATH', realpath('.'));
		Define::load('INSTALL_PATH', realpath('..'));
		Define::load('LIBRARY_PATH', realpath(__DIR__));
		$detectedAppPath =
			realpath(\INSTALL_PATH . '/application')
			? realpath(\INSTALL_PATH . '/application')
			: realpath(\INSTALL_PATH . '/app');
		Define::load('APPLICATION_PATH', $detectedAppPath);
		if (
			'' == \APPLICATION_PATH 
			|| (realpath(\INSTALL_PATH . '/application') && '' == realpath(\APPLICATION_PATH . '/configs'))
			|| !is_readable(\APPLICATION_PATH)
		) {
			header('HTTP/1.0 500 Internal Server Error');
			die('Unable to find the application core.');	 		
		}
		self::$_laced = TRUE;
	}

	/**
	 * Starts the kickstart process, preparing the environment for a framework matching $mode
	 * @param string $mode Any of the class's MODE_ constants
	 * @return string the mode chosen (useful in the case of default MODE_AUTODETECT)
	 */
	public static function go($mode = self::MODE_AUTODETECT, $options = array())
	{
		#TODO handle multiple apps (sub apps)
		#TODO use $options to pick app/sub app over APPLICATION_ID
		if (!self::$_kicked) {
			if (!self::$_laced) {
				self::_lace();
			}			
		 	if ($mode == self::MODE_AUTODETECT) {
				$mode = self::_goAutoMode();
		 	}
		 	if ($mode == self::MODE_SAF) {
				self::_goIdent();
			}
			switch($mode) {
		 		case self::MODE_ZFMVC:
		 			$configFile = 'zend_application';
		 			break;
		 		case self::MODE_SAF:
					if (defined('APPLICATION_ID') && \APPLICATION_ID){
						$applicationFilePart = strtolower(\APPLICATION_ID); //#TODO #1.0.0 filter file names safely
						$configFile = "saf_application.{$applicationFilePart}";
						if (file_exists(\APPLICATION_PATH . "/configs/{$configFile}.xml")) {
							break;
						}
					}
					$configFile = 'saf_application';
		 			break;
		 		default:
		 			$configFile = 'application';
		 			break;
		 	}
			Define::load('APPLICATION_STATUS', 'online');
			Define::load('APPLICATION_BASE_ERROR_MESSAGE', 'Please inform your technical support staff.');
			Define::load('APPLICATION_DEBUG_NOTIFICATION', 'Debug information available.');
		 	if ($mode == self::MODE_LF5) {
				self::_goLaravel();
				if (\APPLICATION_FORCE_DEBUG) {
					\Saf_Debug::init(\Saf_Debug::DEBUG_MODE_FORCE, NULL , FALSE);
				}
				self::_goPreRoute();
				self::_goIdent();
			} else {
				Define::load('APPLICATION_CONFIG', \APPLICATION_PATH . "/configs/{$configFile}.xml");
				Define::load('APPLICATION_FORCE_DEBUG', FALSE, Cast::TYPE_BOOL);
				if (\APPLICATION_FORCE_DEBUG) {
					\Saf_Debug::init(\Saf_Debug::DEBUG_MODE_FORCE, NULL , FALSE);
				}
				self::_goPreRoute();
				if ($mode != self::MODE_SAF) {
					self::_goIdent();
				}
				if ($mode == self::MODE_ZFMVC || $mode == self::MODE_ZFNONE) {
					self::_goZend();
				}
				if ($mode == self::MODE_SAF) {
					self::_goSaf();
				}
			}
			self::_goPreBoot();
			self::$_kicked = $mode;
		}
 		return self::$_kicked;
	}

	protected static function _goIdent()
	{
		Define::load('APPLICATION_ENV', 'production');
		Define::load('APPLICATION_ID', '');
	}

	protected static function _goAutoMode()
	{
		$applicationClassFile =
			\APPLICATION_PATH . '/' . \APPLICATION_ID . '.php';
		$bootstrapClassFile =
			\APPLICATION_PATH . '/Bootstrap.php';
		$laravelFiles = file_exists(\INSTALL_PATH . '/bootstrap/app.php') && file_exists(\INSTALL_PATH . '/config/app.php');
		if ($laravelFiles) {
			return self::MODE_LF5;
		} else if (file_exists($applicationClassFile)) {
			if (!is_readable($applicationClassFile)) {
				header('HTTP/1.0 500 Internal Server Error');
				die('Unable to autodetect application class.');
			}
			$parentClass = self::getParentClassIn($applicationClassFile);
			if (
				strpos($parentClass, 'Saf_Application') === 0
			) {
				return self::MODE_SAF;
			}
		} else if (
			file_exists($bootstrapClassFile)
		) {
			if (!is_readable($bootstrapClassFile)) {
	 			header('HTTP/1.0 500 Internal Server Error');
	 			die('Unable to autodetect bootstrap class.');
			}
			$parentClass = self::getParentClassIn($bootstrapClassFile);
			if (
				strpos($parentClass, 'Zend_Application_Bootstrap') === 0
				|| strpos($parentClass, 'Saf_Bootstrap_Zend') === 0
			) {
				return self::MODE_ZFMVC;
			}
		}
		return
			defined('ZEND_PATH')
				|| Path::fileExistsInPath('Zend/Application.php')
			? self::MODE_ZFNONE
			: self::MODE_NONE;
	}

	/**
	 * steps to prep the route
	 */
	protected static function _goPreRoute()
	{
		defined('ROUTER_NAME') || define('ROUTER_NAME', 'index');
		$routerIndexInPhpSelf = strpos($_SERVER['PHP_SELF'], strtolower(\ROUTER_NAME) . '.php');
		$routerPathLength = (
			$routerIndexInPhpSelf !== FALSE
			? $routerIndexInPhpSelf
			: PHP_MAXPATHLEN
		);
		defined('ROUTER_PATH') || define('ROUTER_PATH', NULL);
		$defaultRouterlessUrl = substr($_SERVER['PHP_SELF'], 0, $routerPathLength);
		Define::load('APPLICATION_BASE_URL',
			\ROUTER_NAME != ''
			? $defaultRouterlessUrl
			: './'
		);
		Define::load('APPLICATION_HOST', (
			array_key_exists('HTTP_HOST', $_SERVER) && $_SERVER['HTTP_HOST']
			? $_SERVER['HTTP_HOST']
			: 'commandline'
		));
		Define::load('STANDARD_PORT', '80');
		Define::load('SSL_PORT', '443');
		Define::load('APPLICATION_SSL', //#TODO #2.0.0 this detection needs work
			array_key_exists('HTTPS', $_SERVER)
				&& $_SERVER['HTTPS']
				&& $_SERVER['HTTPS'] != 'off'
			, Cast::TYPE_BOOL
		);
		Define::load('APPLICATION_PORT', (
				array_key_exists('SERVER_PORT', $_SERVER) && $_SERVER['SERVER_PORT']
				? $_SERVER['SERVER_PORT']
				: 'null'
		));
		Define::load('APPLICATION_SUGGESTED_PORT',
			(\APPLICATION_SSL && \APPLICATION_PORT != \SSL_PORT)
				|| (!\APPLICATION_SSL && \APPLICATION_PORT == \STANDARD_PORT)
			? ''
			: \APPLICATION_PORT
		);
		if(array_key_exists('SERVER_PROTOCOL', $_SERVER)) {
			$lowerServerProtocol = strtolower($_SERVER['SERVER_PROTOCOL']);
			$cleanProtocol = substr($lowerServerProtocol, 0, strpos($lowerServerProtocol, '/'));
			if ($cleanProtocol == 'https') { //#TODO #2.0.0 figure out what other possible base protocols there might be to filter...
				$baseProtocol = 'http';
			} else {
				$baseProtocol = $cleanProtocol;
			}
			define('APPLICATION_PROTOCOL', $baseProtocol);
		} else {
			define('APPLICATION_PROTOCOL','commandline');
		}
		Define::load('DEFAULT_RESPONSE_FORMAT', (
			'commandline' == \APPLICATION_PROTOCOL
			? 'text'
			: 'html+javascript:css'
		));
	}

	/**
	 * steps that can't wait for a bootstrap to kick in
	 */
	protected static function _goPreBoot()
	{
		if (function_exists('libxml_use_internal_errors')) {
			libxml_use_internal_errors(TRUE);
		} else {
			\Saf_Debug::out('Unable to connect LibXML to integrated debugging. libxml_use_internal_errors() not supported.', 'NOTICE');
		}
		if (defined('APPLICATION_TZ')) {
			date_default_timezone_set(\APPLICATION_TZ);
		}
	}

	/**
	 * Conveinence setup when not using the kickstart process.
	 * @param null $mode
	 */
	public static function goLight($mode = self::MODE_AUTODETECT)
	{
		self::_goPreBoot();
	}

	/**
	 * steps to take when preparing for SAF Applications
	 */
	protected static function _goSaf()
	{
		require_once(\LIBRARY_PATH . '/Saf/Application.php');
	}
	
	/**
	 * steps to take when preparing for a Zend Framework application
	 */
	protected static function _goZend()
	{
		self::defineLoad('ZEND_PATH', '');
		if (\ZEND_PATH != '') {
			Path::addIfNotInPath(\ZEND_PATH);
		}
		if (
			!file_exists(\ZEND_PATH . '/Zend/Application.php')
			&& !file_exists(\LIBRARY_PATH . '/Zend/Application.php')
			&& !Path::fileExistsInPath('Zend/Application.php')
		) {
			header('HTTP/1.0 500 Internal Server Error');
			die('Unable to find Zend Framework.');
		}
		if (
			!is_readable('Zend/Application.php')
			&& !is_readable(\ZEND_PATH . '/Zend/Application.php')
			&& !is_readable(\LIBRARY_PATH . '/Zend/Application.php')
		) {
			header('HTTP/1.0 500 Internal Server Error');
			die('Unable to access Zend Framework.');
		}
		if (
			file_exists(\LIBRARY_PATH . '/Zend/Application.php')
			&& is_readable(\LIBRARY_PATH . '/Zend/Application.php')
			&& !Path::fileExistsInPath('Zend/Application.php')
		) {
			Path::addIfNotInPath(\LIBRARY_PATH);
		}
		require_once('Zend/Application.php');
		self::$_controllerPath = 'controllers';
	}

	/**
	 * steps to take when preparing for Laravel Applications
	 */
	protected static function _goLaravel()
	{
		$env = self::envRead(\INSTALL_PATH . '/.env');
		$detectedEnv = array_key_exists('APP_ENV', $env) ? $env['APP_ENV'] : 'production';
		defined('APPLICATION_ENV') || define('APPLICATION_ENV',	$detectedEnv);
		Define::load(
			'APPLICATION_FORCE_DEBUG',
			array_key_exists('APP_DEBUG', $env) ? $env['APP_DEBUG'] : FALSE,
			Cast::TYPE_BOOL
		);
	}
	
	/**
	 * @return string the mode the application was started in
	 */
	public static function getMode()
	{
		return self::$_kicked;
	}

	/**
	 * perform kickstart based on the provided mode
	 */
	public static function kick($mode)
	{
		try {
			switch ($mode) {
				case self::MODE_ZFMVC:
					$application = new \Zend_Application(APPLICATION_ENV, APPLICATION_CONFIG);
					$application->bootstrap()->run();
					break;
				case self::MODE_SAF:
					$application = \Saf_Application::load(APPLICATION_ID, APPLICATION_ENV, TRUE);
					break;
				default:
				if(file_exists('main.php')){
					if (!is_readable('main.php')){
						header('HTTP/1.0 500 Internal Server Error');
						die('Unable to access main application script.');
					}
					require_once('main.php');
				} else {
					header('HTTP/1.0 500 Internal Server Error');
					die('No application to run.');
				}	
			} 
		} catch (Exception $e) {
			header('HTTP/1.0 500 Internal Server Error');
			self::exceptionDisplay($e);
		}
		\Saf_Debug::dieSafe();
	}

	public static function resolveControllerClassName($controllerName)
	{
		$nameSuffix = 'Controller';
		$className = ucfirst($controllerName);
		return
			strpos($className, $nameSuffix) == FALSE
				|| strrpos($className, $nameSuffix) != strlen($className) - strlen($nameSuffix)
			? $className .= $nameSuffix
			: $className;
	}

	/**
	 * checks to see if a string is a valid class/variable name
	 * @param string $string
	 * @return bool
	 */
	public static function isValidNameToken($string)
	{
		$pattern = self::REGEX_VAR;
		return preg_match($pattern, $string);
	}

	/**
	 * scan a file for the first class definition and return the class name
	 * @param string $file path
	 * @returns string class
	 */
	public static function getClassIn($file)
	{
		$file = file_get_contents($file);
		$pattern = self::REGEX_CLASS;
		$matches = NULL;
		preg_match($pattern, $file, $matches);
		return
		$matches && array_key_exists(1, $matches)
		? $matches[1]
		: '';
	}

	/**
	 * scan a file for the first class definition extending another class
	 * and return the parent class name
	 * @param string $file path
	 * @returns string class
	 */
	public static function getParentClassIn($file)
	{
		$file = file_get_contents($file);
		$pattern = self::REGEX_PARENT_CLASS;
		$matches = NULL;
		preg_match($pattern, $file, $matches);
		return
		$matches && array_key_exists(1, $matches)
		? $matches[1]
		: '';
	}

	public static function isAutoloading()
	{
		return self::$_autoloadingInstalled;
	}

	/**
	 * Turns on autoloading. Optionally, it can prep for explicit use
	 * of self::autoload only.
	 * @param bool $takeover defaults to TRUE
	 */
	public static function initializeAutoloader($takeover = TRUE)
	{
		if (!self::$_laced) {
			self::_lace();
		}
		//#TODO #3.0.0 allow for uninstall
		if ($takeover && !self::$_autoloadingInstalled) {
			spl_autoload_register('Saf\Kickstart::autoload');
			self::$_autoloadingInstalled = TRUE;
		}
		if (self::$_initialized) {
			return;
		}
		foreach(self::$_libraries as $libraryPrefix => $callableList) {
			foreach($callableList as $callableIndex => $callable) {
				if (
					!is_a($callable,'\ReflectionFunctionAbstract', FALSE)
					&& (
						!is_array($callable)
						|| !array_key_exists(0,$callable)
						|| !array_Key_exists(1,$callable)
						|| !is_object($callable[0])
						|| !is_a($callable[1],'\ReflectionFunctionAbstract', FALSE)
					)
				) {
					self::$_libraries[$libraryPrefix][$callableIndex] = self::_instantiateCallable($callable);
				}
			}
		}
		foreach(self::$_specialAutoloaders as $loaderName => $callableList) {
			foreach($callableList as $callableIndex => $callable) {
				if (
					!is_a($callable,'\ReflectionFunctionAbstract', FALSE)
					&& (
						!is_array($callable)
						|| !array_key_exists(0,$callable)
						|| !array_Key_exists(1,$callable)
						|| !is_object($callable[0])
						|| !is_a($callable[1],'\ReflectionFunctionAbstract', FALSE)
					)
				) {
					self::$_specialAutoloaders[$loaderName][$callableIndex] = self::_instantiateCallable($callable);
				}
			}
		}
		foreach(self::$_autoloaders as $prefix => $paths) {
			foreach($paths as $pathIndex => $path) {
				self::$_autoloaders[$prefix][$pathIndex] = Path::translatePath($path);
			}
		}
		self::$_initialized = TRUE;
	}

	/**
	 * Attempts to autoload a class by name, checking it against known class
	 * name prefixes for libraries (using an optional user provided path 
	 * resolution method), or for other files using the Zend Framework naming 
	 * conventions in one or more registered paths.
	 * Autoloading come preconfigured to look in the APPLICATION_PATH/models/
	 * (matching any class), and LIBRARY_PATH/Saf/ (matching prefix 'Saf_') paths.
	 * Libraries alway take precidence over other matching rules.
	 * The first existing match file will be included.
	 * If a $specialLoader is specified, only that loader is searched.
	 * Special loaders are only searched when specified.
	 * @param string $className name of class to be found/loaded
	 * @param string $specialLoader limits the search to a specific special loader
	 *
	 */
	public static function autoload($className, $specialLoader = '')
	{
		if (class_exists($className, FALSE)) {
			return TRUE;
		}
		if (!self::$_autoloadingInstalled && class_exists('Zend_Loader_Autoloader', FALSE)) {
			$zendAutoloader = \Zend_Loader_Autoloader::getInstance();
			$currentSetting = $zendAutoloader->suppressNotFoundWarnings();
			$zendAutoloader->suppressNotFoundWarnings(TRUE);
			$zendResult = \Zend_Loader_Autoloader::autoload($className);
			$zendAutoloader->suppressNotFoundWarnings($currentSetting);
			return $zendResult;
		}
		if (!self::$_initialized) {
			self::initializeAutoloader(FALSE);
		}
		if ($specialLoader) {
			if (!is_array($specialLoader)) {
				$specialLoader = array();
			}
			foreach($specialLoader as $loader) {
				if (array_key_exists($specialLoader, self::$_specialAutoloaders)) {
					foreach(self::$_specialAutoloaders[$specialLoader] as $callableList) {
						foreach ($callableList as $callableSet) {
							if(is_array($callableSet)) {
								$callableInstance = $callableSet[0];
								$callableReflector = $callableSet[1];
							} else {
								$callableInstance = NULL;
								$callableReflector = $callableSet;
							}
							$classFile = $callableReflector->invoke($callableInstance, $className);
							if (
								$classFile
								&& Path::fileExistsInPath($classFile)
							) {
								require_once($classFile);
								if (!class_exists($className, FALSE)) {
									throw new Exception('Failed to special autoload class'
										. (
											class_exists('Saf_Debug', FALSE) && \Saf_Debug::isEnabled()
											? " {$className}"
											: ''
										) . '. Found class file, but no definition inside.'
									);
								}
								return TRUE;
							}
						}
					}
				} else {
					throw new Exception('Failed to special autoload class'
						. (
							class_exists('Saf_Debug', FALSE) && \Saf_Debug::isEnabled()
							? " {$className}"
							: ''
						) . '. invalid loader specified.'
					);
				}
			}
			return FALSE;
		}
		foreach(self::$_libraries as $classPrefix => $callableList) {
			if (strpos($className, $classPrefix) === 0) {
				foreach ($callableList as $callableSet) {
					if(is_array($callableSet)) {
						$callableInstance = $callableSet[0];
						$callableReflector = $callableSet[1];
					} else {
						$callableInstance = NULL;
						$callableReflector = $callableSet;
					}
// 					if (!is_object($callableReflector)) {
// 						print_r(array($className,$classPrefix,$callableList));
// 						throw new Exception('huh');
// 					}
					$classFile = $callableReflector->invoke($callableInstance, $className);
					if (
						$classFile
						&& Path::fileExistsInPath($classFile)
					) {
						require_once($classFile);
						if (!class_exists($className, FALSE)) {
							throw new \Exception('Failed to autoload class'
								. (
									class_exists('Saf_Debug',FALSE) && \Saf_Debug::isEnabled()
									? " {$className}"
									: ''
								)
								. '. Found a library, but no definition inside.'
							);
						}
						return TRUE;
					}
				}
			}
		}
		foreach (self::$_autoloaders as $classPrefix => $pathList) {
			if ('' == $classPrefix || strpos($className, $classPrefix) === 0) {
				foreach($pathList as $path) {
					$classFile = Path::resolveClassPath($className, $path);
					if (
						$classFile
						&& Path::fileExistsInPath($classFile)
					) {
						require_once($classFile);
						if (!class_exists($className, FALSE)) {
							throw new \Exception('Failed to autoload class' 
								. (
									class_exists('Saf_Debug', FALSE) && \Saf_Debug::isEnabled()
									? " {$className}"
									: ''
								) 
								. '. Found a file, but no definition inside.'
							);
						}
						return TRUE;
					}
				}
			}
		}
		if (!class_exists($className, FALSE)) {
			throw new \Exception('Failed resolving file to autoload class' 
				. (
					class_exists('Saf_Debug', FALSE) && \Saf_Debug::isEnabled()
					? " {$className}"
					: ''
				) 
				. '.'
			);
		}
		return TRUE;
	}

	/**
	 * Registers a new library for autoloading. Default behavior is to
	 * try to load from the LIBRARY_PATH using Zend Framework naming conventions.
	 *
	 */
	public static function addLibrary($libraryPrefix, $resolutionCallable = NULL, $preposition = self::POSITION_AFTER)
	{
		self::initializeAutoloader(FALSE);
		if ('' == $resolutionCallable || is_null($resolutionCallable)) {
			$resolutionCallable = 'Saf\Environment\Path::resolveClassPath';
		}
		$instantiatedCallable = self::_instantiateCallable($resolutionCallable);
		if (array_key_exists($libraryPrefix, self::$_libraries)) {
			if ($preposition) {
				self::$_libraries[$libraryPrefix] = array_merge(
					array(
						$resolutionCallable => $instantiatedCallable
					),
					self::$_libraries[$libraryPrefix]
				);
			} else {
				self::$_libraries[$libraryPrefix][$resolutionCallable] =
				self::_instantiateCallable($resolutionCallable);
			}
			return;
		}
		foreach(self::$_libraries as $existingPrefix => $existingLibrary){
			 if (strpos($existingPrefix, $libraryPrefix) === 0) {
				$newLibMap = array();
				foreach(self::$_libraries as $currentPrefix => $currentSpec) {
					if ($currentPrefix == $existingPrefix) {
						$newLibMap[$libraryPrefix] = array($resolutionCallable => $instantiatedCallable);
						$newLibMap[$currentPrefix] = $currentSpec;
					} else {
						$newLibMap[$currentPrefix] = $currentSpec;
					}
				}
				self::$_libraries = $newLibMap;
				return;
			}
		}
		self::$_libraries[$libraryPrefix] = array(
			$resolutionCallable => $instantiatedCallable
		);
	}

	public static function getLibrarySpec()
	{
		self::initializeAutoloader(FALSE);
		return self::$_libraries;
	}

	/**
	 * remove the library resolver specified, or all entries for that library if null
	 * @param string $libraryPrefix
	 * @param string $callable
	 */
	public static function removeLibrary($libraryPrefix, $callable = NULL)
	{ //#TODO #3.0.0 translate non-string callables
		self::initializeAutoloader(FALSE);
		if (!array_key_exists($libraryPrefix, self::$_libraries)) {
			return FALSE;
		}
		if (is_null($callable)) {
			unset(self::$_libraries[$libaryPrefix]);
			return array($libraryPrefix => $callable);
		}
		if (array_key_exists($callable, self::$_libraries[$libaryPrefix])) {
			unset(self::$_libraries[$libaryPrefix][$callable]);
			return array($libraryPrefix => $callable);
		}
		return FALSE;
	}

	/**
	 * Registers one or more new prefix/path pairs. Prefixes may be the empty
	 * string to match any class. Paths may be empty to match the path.
	 *
	 */
	public static function addAutoloader($prefix, $path, $preposition = self::POSITION_AFTER)
	{
		self::initializeAutoloader(FALSE);
		$path = Path::translatePath($path);
		if (array_key_exists($prefix, self::$_autoloaders)) {
			if ($preposition) {
				self::$_autoloaders[$prefix] = array_merge(
					array($path),
					self::$_autoloaders[$prefix]
				);
			} else {
				self::$_autoloaders[$prefix][] = $path;
			}
			return;
		}
		foreach(self::$_autoloaders as $existingPrefix => $existingPaths){
			 if (strpos($existingPrefix, $prefix) === 0) {
				$newMap = array();
				foreach(self::$_autoloaders as $currentPrefix => $currentSpec) {
					if ($currentPrefix == $existingPrefix) {
						$newMap[] = $path;
						$newMap[] = $currentSpec;
					} else {
						$newMap[] = $currentSpec;
					}
				}
				self::$_autoloaders = $newMap;
				return;
			}
		}
		self::$_autoloaders[$prefix] = array($path);
	}

	/**
	 * Unregisters an autoloader. It can remove only one matching path, or 
	 * all that are registered. Libraries cannot be removed once added.
	 *
	 */
	public static function removeAutoloader($prefix, $path = NULL)
	{
		self::initializeAutoloader(FALSE);
		if (!array_key_exists($prefix, self::$_autoloaders)) {
			return FALSE;
		}
		if (is_null($path)) {
			unset(self::$_autoloaders[$prefix]);
			return array($prefix => $path);
		}
		if (array_key_exists($path, self::$_autoloaders[$prefix])) {
			unset(self::$_autoloaders[$prefix][$path]);
			return array($prefix => $path);
		}
		return FALSE;
	}

	public static function getPathSpec()
	{
		self::initializeAutoloader(FALSE);
		return self::$_autoloaders;
	}

	/**
	 * Registers one or more new name/resolution pairs.
	 * @param string $name
	 * @param string $callable
	 * @param bool $preposition
	 */
	public static function addSpecialAutoloader($name, $resolutionCallable = NULL , $preposition = self::POSITION_AFTER)
	{
		self::initializeAutoloader(FALSE);
		if ('' == $resolutionCallable || is_null($resolutionCallable)) {
			$resolutionCallable = 'Saf\Environment\Path::resolveClassPath';
		}
		$instantiatedCallable = self::_instantiateCallable($resolutionCallable);
		if (array_key_exists($name, self::$_specialAutoloaders)) {
			if ($preposition) {
				self::$_specialAutoloaders[$name] = array_merge(
					array(
						$resolutionCallable => $instantiatedCallable
					),
					self::$_specialAutoloaders[$name]
				);
			} else {
				self::$_specialAutoloaders[$name][$resolutionCallable] =
				self::_instantiateCallable($resolutionCallable);
			}
			return;
		}
		self::$_specialAutoloaders[$name] = array(
			$resolutionCallable => $instantiatedCallable
		);
	}

	public static function removeSpecialAutoloader($name, $callable = FALSE)
	{ //#TODO #3.0.0 translate non-string callables
		self::initializeAutoloader(FALSE);
		if (!array_key_exists($name, self::$_specialAutoloaders)) {
			return FALSE;
		}
		if (is_null($callable)) {
			unset(self::$_specialAutoloaders[$name]);
			return array($name => $callable);
		}
		if (array_key_exists($callable, self::$_specialAutoloaders[$name])) {
			unset(self::$_specialAutoloaders[$name][$callable]);
			return array($name => $callable);
		}
		return FALSE;
	}

	public static function getSpecialLoaders()
	{
		self::initializeAutoloader(FALSE);
		return self::$_specialAutoloaders;
	}

	/**
	 * takes a callable, and returns a Reflector that can be invoked.
	 * In the case of instantiated callables, the return is an array
	 * bundling the object [0] and method reflector [1], or a relector class (ReflectionFunction or static ReflectionMethod)
	 * @param string $callable
	 * @return array 0 object or string, 1 Reflection
	 */
	protected static function _instantiateCallable($callable)
	{//#TODO #2.0.0 this is a candidate for being made public
		if (is_string($callable) && strpos($callable, '::') === FALSE) {
			return new \ReflectionFunction($callable);
		}
		if (!is_array($callable)) {
			$callable = explode('::', $callable);
			return new \ReflectionMethod($callable[0],$callable[1]);
		} else {
			return array($callable[0], new \ReflectionMethod($callable[0],$callable[1]));
		}
	}
	
	/**
	 * loads resource configuration data
	 */
	public static function getConfigResource($resource, $compatMode = FALSE)
	{//#TODO #2.0.0 compatMode supports Zend_config objects (i.e. original Room Res implementation), but it's a pretty cryptic solution...
		$resources = $compatMode ? \Saf_Registry::get('config')->get('resources', \APPLICATION_ENV) : \Saf_Registry::get('config');
		//
		if ($resources) {
			$resource = $resources->$resource;
			if ($resource || is_array($resource)) {
				return
					is_array($resource)
					? $resource
					: (method_exists($resource,'toArray') ? $resource->toArray() : array());
			}
		}
		return NULL;
	}

	/**
	 * loads configuration data
	 */
	public static function getConfigItem($item)
	{
		$item = \Saf_Registry::get('config')->get($item, \APPLICATION_ENV);
		if ($item) {
			return
				is_array($item)
				? $item
				: (
					is_object($item)
					? $item->toArray()
					: $item
				);
		}
		return NULL;
	}

	/**
	 * Outputs in the case of complete and total failure during the
	 * application bootstrap process.
	 * @param Exception $e
	 * @param string $caughtLevel
	 * @param string $additionalError
	 */
	public static function exceptionDisplay($e, $caughtLevel = 'BOOTSTRAP', $additionalError = '')
	{
		$rootUrl = defined('APPLICATION_BASE_URL') ? \APPLICATION_BASE_URL : '';
		$title = 'Configuration Error';
		if (is_null(self::$_exceptionView)) {
			self::$_exceptionView = \APPLICATION_PATH . '/views/scripts/error/error.php';
		}
		include(self::$_exceptionView);
		if (class_exists('Saf_Debug', FALSE)) {
			\Saf_Debug::dieSafe();
		}
	}

	/**
	 * sets the path to the php script used by exceptionDisplay()
	 * @param string $path
	 */
	public static function setExceptionDisplayScript($path)
	{
		self::$_exceptionView = realpath($path);
	}
}
