<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Legacy Autoloader for patching older SAF instances
 */

namespace Saf\Legacy;

class Autoloader {

    /**
	 * instructs an inserter to add the new item to the start of the list
	 * @var bool
	 */
	const POSITION_BEFORE = true;

	/**
	 * instructs an inserter to add the new item to the end of the list
	 * @var unknown_type
	 */
	const POSITION_AFTER = false;

    protected static $debuggingEnabled = false;

	/**
	 * indicates that the internal lists have been normalized
	 */
	protected static $initialized = false;

	/**
	 * indicates that the internal autoloader is in charge
	 * @var bool
	 */
	protected static $enabled = false;

	/**
	 * list of class prefixes and paths for autoloading
	 * @var array
	 */
	protected static $autoloaders = array(
		'' => array(
			'[[APPLIATION_PATH]]/models',
        ),
        'Saf_' => array(
			'[[LEGACY_SAF_PATH]]',
		)
	);

	/**
	 * list of named autoloaders and the callable they use for resolution
	 * @var array
	 */
	protected static $special = array(
		'controller' => array(
			'Saf\Legacy\Autoloader::resolveControllerPath' => 'Saf\Legacy\Autoloader::resolveControllerPath'
		)
	);

	/**
	 * list of library autoloaders and the callable they use for resolution
	 * @var array
	 */
	protected static $libraries = array(
		'Saf' => array(
			'Saf\Legacy\Autoloader::resolveClassPath' => 'Saf\Legacy\Autoloader::resolveClassPath'
		)
	);

    /**
     * 
     */
    protected static $envAdapter = [
        'LIBRARY_PATH' => null,
        'APPLICATION_PATH' => null,
    ];

    /**
     * returns true if the legacy autoloader has been registered with the spl
     */
	public static function isAutoloading()
	{
		return self::$enabled;
	}

	/**
	 * Turns on autoloading. Optionally, it can prep for explicit use
	 * of self::autoload only.
     * @param array $options injected from Environment, needed to handle previous engtablements
	 * @param bool $takeover defaults to true
	 */
	public static function init(array $options, bool $takeover = true)
	{
        self::initOptions($options);
		if ($takeover && !self::$enabled) {
			spl_autoload_register('Saf\Legacy\Autoloader::autoload');
			self::$enabled = true;
		}

		if (self::$initialized) {
			return;
		}
		foreach(self::$libraries as $libraryPrefix => $callableList) {
			foreach($callableList as $callableIndex => $callable) {
				if (
					!is_a($callable,'\ReflectionFunctionAbstract', false)
					&& (
						!is_array($callable)
						|| !array_key_exists(0,$callable)
						|| !array_Key_exists(1,$callable)
						|| !is_object($callable[0])
						|| !is_a($callable[1],'\ReflectionFunctionAbstract', false)
					)
				) {
					self::$libraries[$libraryPrefix][$callableIndex] = self::_instantiateCallable($callable);
				}
			}
		}
		foreach(self::$special as $loaderName => $callableList) {
			foreach($callableList as $callableIndex => $callable) {
				if (
					!is_a($callable,'\ReflectionFunctionAbstract', false)
					&& (
						!is_array($callable)
						|| !array_key_exists(0,$callable)
						|| !array_Key_exists(1,$callable)
						|| !is_object($callable[0])
						|| !is_a($callable[1],'\ReflectionFunctionAbstract', false)
					)
				) {
					self::$special[$loaderName][$callableIndex] = self::_instantiateCallable($callable);
				}
			}
		}
		foreach(self::$autoloaders as $prefix => $paths) {
			foreach($paths as $pathIndex => $path) {
				self::$autoloaders[$prefix][$pathIndex] = self::translatePath($path);
			}
		}
		self::$initialized = true;
	}

	/**
	 * Attempts to autoload a class by name, checking it against known class
	 * name prefixes for libraries (using an optional user provided path 
	 * resolution method), or for other files using the Zend Framework naming 
	 * conventions in one or more registered paths.
	 * Autoloading comes preconfigured to look in the APPLICATION_PATH/models/
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
		if (class_exists($className, false)) {
			return true;
		}
		if (!self::$enabled && class_exists('Zend_Loader_Autoloader', false)) {
			$zendAutoloader = \Zend_Loader_Autoloader::getInstance();
			$currentSetting = $zendAutoloader->suppressNotFoundWarnings();
			$zendAutoloader->suppressNotFoundWarnings(true);
			$zendResult = \Zend_Loader_Autoloader::autoload($className);
			$zendAutoloader->suppressNotFoundWarnings($currentSetting);
			return $zendResult;
		}
		if (!self::$initialized) {
			self::init(false);
		}
		if ($specialLoader) {
			if (!is_array($specialLoader)) {
				$specialLoader = array();
			}
			foreach($specialLoader as $loader) {
				if (array_key_exists($specialLoader, self::$special)) {
					foreach(self::$special[$specialLoader] as $callableList) {
						foreach ($callableList as $callableSet) {
							if(is_array($callableSet)) {
								$callableInstance = $callableSet[0];
								$callableReflector = $callableSet[1];
							} else {
								$callableInstance = null;
								$callableReflector = $callableSet;
							}
							$classFile = $callableReflector->invoke($callableInstance, $className);
							if (self::fileExistsInPath($classFile)) {
								require_once($classFile);
								if (!class_exists($className, false)) {
									throw new \Exception('Failed to special autoload class'
										. (
											self::$debuggingEnabled
											? " {$className}"
											: ''
										) . '. Found class file, but no definition inside.'
									);
								}
								return true;
							}
						}
					}
				} else {
					throw new \Exception('Failed to special autoload class'
						. (
							self::$debuggingEnabled
							? " {$className}"
							: ''
						) . '. invalid loader specified.'
					);
				}
			}
			return false;
		}
		foreach(self::$libraries as $classPrefix => $callableList) {
			if (strpos($className, $classPrefix) === 0) {
				foreach ($callableList as $callableSet) {
					if(is_array($callableSet)) {
						$callableInstance = $callableSet[0];
						$callableReflector = $callableSet[1];
					} else {
						$callableInstance = null;
						$callableReflector = $callableSet;
					}
// 					if (!is_object($callableReflector)) {
// 						print_r(array($className,$classPrefix,$callableList));
// 						throw new \Exception('huh');
// 					}
					$classFile = $callableReflector->invoke($callableInstance, $className);
					if (self::fileExistsInPath($classFile)) {
						require_once($classFile);
						if (!class_exists($className, false)) {
							throw new \Exception('Failed to autoload class'
								. (
									self::$debuggingEnabled
									? " {$className}"
									: ''
								)
								. '. Found a library, but no definition inside.'
							);
						}
						return true;
					}
				}
			}
		}
		foreach (self::$autoloaders as $classPrefix => $pathList) {
			if ('' == $classPrefix || strpos($className, $classPrefix) === 0) {
				foreach($pathList as $path) {
					$classFile = self::resolveClassPath($className, $path);
					if (self::fileExistsInPath($classFile)) {
						require_once($classFile);
						if (!class_exists($className, false)) {
							throw new \Exception('Failed to autoload class' 
								. (
									self::$debuggingEnabled
									? " {$className}"
									: ''
								) 
								. '. Found a file, but no definition inside.'
							);
						}
						return true;
					}
				}
			}
		}
		if (!class_exists($className, false)) {
			throw new \Exception('Failed resolving file to autoload class' 
				. (
					self::$debuggingEnabled
					? " {$className}"
					: ''
				) 
				. '.'
			);
		}
		return true;
	}

	/**
	 * Registers a new library for autoloading. Default behavior is to
	 * try to load from the LIBRARY_PATH using Zend Framework naming conventions.
	 *
	 */
	public static function addLibrary($libraryPrefix, $resolutionCallable = null, $preposition = self::POSITION_AFTER)
	{
		self::init(false);
		if ('' == $resolutionCallable || is_null($resolutionCallable)) {
			$resolutionCallable = 'Saf\Legacy\Autoloader::resolveClassPath';
		}
		$instantiatedCallable = self::_instantiateCallable($resolutionCallable);
		if (array_key_exists($libraryPrefix, self::$libraries)) {
			if ($preposition) {
				self::$libraries[$libraryPrefix] = array_merge(
					array(
						$resolutionCallable => $instantiatedCallable
					),
					self::$libraries[$libraryPrefix]
				);
			} else {
				self::$libraries[$libraryPrefix][$resolutionCallable] =
				self::_instantiateCallable($resolutionCallable);
			}
			return;
		}
		foreach(self::$libraries as $existingPrefix => $existingLibrary){
			 if (strpos($existingPrefix, $libraryPrefix) === 0) {
				$newLibMap = array();
				foreach(self::$libraries as $currentPrefix => $currentSpec) {
					if ($currentPrefix == $existingPrefix) {
						$newLibMap[$libraryPrefix] = array($resolutionCallable => $instantiatedCallable);
						$newLibMap[$currentPrefix] = $currentSpec;
					} else {
						$newLibMap[$currentPrefix] = $currentSpec;
					}
				}
				self::$libraries = $newLibMap;
				return;
			}
		}
		self::$libraries[$libraryPrefix] = array(
			$resolutionCallable => $instantiatedCallable
		);
	}

	public static function getLibrarySpec()
	{
		self::initialize(FALSE);
		return self::$libraries;
	}

	/**
	 * remove the library resolver specified, or all entries for that library if null
	 * @param string $libraryPrefix
	 * @param string $callable
	 */
	public static function removeLibrary($libraryPrefix, $callable = null)
	{ //#TODO #3.0.0 translate non-string callables
		self::init(false);
		if (!array_key_exists($libraryPrefix, self::$libraries)) {
			return false;
		}
		if (is_null($callable)) {
			unset(self::$libraries[$libaryPrefix]);
			return array($libraryPrefix => $callable);
		}
		if (array_key_exists($callable, self::$libraries[$libaryPrefix])) {
			unset(self::$libraries[$libaryPrefix][$callable]);
			return array($libraryPrefix => $callable);
		}
		return false;
	}

	/**
	 * Registers one or more new prefix/path pairs. Prefixes may be the empty
	 * string to match any class. Paths may be empty to match the path.
	 *
	 */
	public static function addAutoloader($prefix, $path, $preposition = self::POSITION_AFTER)
	{
		self::init(false);
		$path = self::translatePath($path);
		if (array_key_exists($prefix, self::$autoloaders)) {
			if ($preposition) {
				self::$autoloaders[$prefix] = array_merge(
					array($path),
					self::$autoloaders[$prefix]
				);
			} else {
				self::$autoloaders[$prefix][] = $path;
			}
			return;
		}
		foreach(self::$autoloaders as $existingPrefix => $existingPaths){
			 if (strpos($existingPrefix, $prefix) === 0) {
				$newMap = array();
				foreach(self::$autoloaders as $currentPrefix => $currentSpec) {
					if ($currentPrefix == $existingPrefix) {
						$newMap[] = $path;
						$newMap[] = $currentSpec;
					} else {
						$newMap[] = $currentSpec;
					}
				}
				self::$autoloaders = $newMap;
				return;
			}
		}
		self::$autoloaders[$prefix] = array($path);
	}

	/**
	 * Unregisters an autoloader. It can remove only one matching path, or 
	 * all that are registered. Libraries should be removed with removeLibrary
	 *
	 */
	public static function removeAutoloader($prefix, $path = null)
	{
		self::init(false);
		if (!array_key_exists($prefix, self::$autoloaders)) {
			return false;
		}
		if (is_null($path)) {
			unset(self::$autoloaders[$prefix]);
			return array($prefix => $path);
		}
		if (array_key_exists($path, self::$autoloaders[$prefix])) {
			unset(self::$autoloaders[$prefix][$path]);
			return array($prefix => $path);
		}
		return false;
	}

	public static function getPathSpec()
	{
		self::init(false);
		return self::$autoloaders;
	}

	/**
	 * Registers one or more new name/resolution pairs.
	 * @param string $name
	 * @param string $callable
	 * @param bool $preposition
	 */
	public static function addSpecialAutoloader($name, $resolutionCallable = null , $preposition = self::POSITION_AFTER)
	{
		self::init(false);
		if ('' == $resolutionCallable || is_null($resolutionCallable)) {
			$resolutionCallable = 'Saf\Legacy\Autoloader::resolveClassPath';
		}
		$instantiatedCallable = self::_instantiateCallable($resolutionCallable);
		if (array_key_exists($name, self::$special)) {
			if ($preposition) {
				self::$special[$name] = array_merge(
					array(
						$resolutionCallable => $instantiatedCallable
					),
					self::$special[$name]
				);
			} else {
				self::$special[$name][$resolutionCallable] =
				self::_instantiateCallable($resolutionCallable);
			}
			return;
		}
		self::$special[$name] = array(
			$resolutionCallable => $instantiatedCallable
		);
	}

	public static function removeSpecialAutoloader($name, $callable = false)
	{ //#TODO #3.0.0 translate non-string callables
		self::init(false);
		if (!array_key_exists($name, self::$special)) {
			return false;
		}
		if (is_null($callable)) {
			unset(self::$special[$name]);
			return array($name => $callable);
		}
		if (array_key_exists($callable, self::$special[$name])) {
			unset(self::$special[$name][$callable]);
			return array($name => $callable);
		}
		return false;
	}

	public static function getSpecialLoaders()
	{
		self::initialize(false);
		return self::$special;
	}

	/**
	 * takes a callable, and returns a Reflector that can be invoked.
	 * In the case of instantiated callables, the return is an array
	 * bundling the object [0] and method reflector [1], or a relector class (ReflectionFunction or static ReflectionMethod)
	 * @param string $callable
	 * @return array 0 object or string, 1 Reflection
	 */
	protected static function _instantiateCallable($callable)
	{
		if (is_string($callable) && strpos($callable, '::') === false) {
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
	 * Converts a classname and base file path into a full path based
	 * on Zend Framework class naming conventions. i.e. _ to /
	 *
	 */
	public static function resolveClassPath($className, $basePath = '')
	{
		if ('' == $basePath) {
			$basePath = self::$envAdapter['LIBRARY_PATH'];
		}
		$classNameComponents = explode('_', $className);
		$classPath = $basePath;
		foreach ($classNameComponents as $nameComponent) {
			$classPath .= "/{$nameComponent}";
		}
		$classPath .= '.php';
		return $classPath;
	}

	public static function resolveControllerClassName($controllerName)
	{
		$nameSuffix = 'Controller';
		$className = ucfirst($controllerName);
		return
			strpos($className, $nameSuffix) == false
				|| strrpos($className, $nameSuffix) != (strlen($className) - strlen($nameSuffix))
			? ($className .= $nameSuffix)
			: $className;
	}

    /**
	 * Converts a controller class name into a filepath
	 * @param string $className
	 */
	public static function resolveControllerPath($controllerName, $coerce = true)
	{
		$controllerPath = self::$envAdapter['APPLICATION_PATH'] . '/' . self::$envAdapter['CONTROLLER_PATH'] . '/';
		$className = $coerce ? self::resolveControllerClassName($controllerName) : $controllerName;
		if (!is_readable($controllerPath)) {
			throw new \Exception('This application does not support controllers.');
		}
		return(self::resolveClassPath($className, $controllerPath));
	}

    protected static function initOptions(array $options)
    {
        $map = [
            'libraryPath' => 'LIBRARY_PATH',
            'applicationPath' => 'APPLICATION_PATH',
            'controllerPath' => 'CONTROLLER_PATH',
            'publicPath' => 'PUBLIC_PATH',
            'installPath' => 'INSTALL_PATH',
        ];
        foreach($map as $optionKey => $adaptedConstant) {
            if (array_key_exists($optionKey, $options)) {
                self::$envAdapter[$adaptedConstant] = $options[$optionKey];
            } else {
                throw new \Exception("Legacy autoloader not configured with a {$adaptedConstant}");
            }
        }
        if (
            (array_key_exists('forceDebug', $options) && $options['forceDebug']) 
            || (array_key_exists('enableDebug', $options) && $options['enableDebug'])
        ) {
            self::$debuggingEnabled = true;
        }
    }

    public static function translatePath(string $path)
	{
		return str_replace(
			[
				'[[APPLICATION_PATH]]',
				'[[LIBRARY_PATH]]',
				'[[PUBLIC_PATH]]',
				'[[INSTALL_PATH]]',
                '[[LEGACY_SAF_PATH]]',
            ], [
				self::$envAdapter['APPLICATION_PATH'],
				self::$envAdapter['LIBRARY_PATH'],
				self::$envAdapter['PUBLIC_PATH'],
				self::$envAdapter['INSTALL_PATH'],
				realpath(dirname(dirname(dirname(__FILE__))).'/lib' ),
			], $path
		);
	}

    /**
	 * Manually checks the path (not always pwd) to see if the file to be included
	 * is there. Implemented as such because some differences seem to be present
	 * between PHP implementations.
	 * @param string filepath relative path to the file or folder in question.
	 * @return boolean
	 */
	public static function fileExistsInPath($filepath)
	{
        if (!$filepath || '' === $filepath) {
            return false;
        }
		if(strpos($filepath, '/') === 0) {
			return file_exists($filepath);
		}
		$paths = explode(\PATH_SEPARATOR, ini_get('include_path'));
		foreach($paths as $path){
			if (file_exists(realpath("{$path}/{$filepath}"))){
				return true;
			}
		}
		return false;
	}
}