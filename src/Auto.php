<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for autoloader management.
 */

namespace Saf;


class Auto {

	const REGEX_CLASS =
		'/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s{]/';
	const REGEX_PARENT_CLASS =
		'/class\s+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s+extends\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+[\s{]/';

	/**
	 * Looks up class and requires it if the file exists
	 * @param string $class
	 * @return bool success
	 */
	public static function loadInternalClass(string $class)
	{
		$file = self::classPathLookup($class);
		if (file_exists($file) && is_readable($file)) {
			require_once($file);
			return true;
		}
		return false;
	}

	/**
	 * @param string Fully qualified class name
	 * @return string path for the internal $class in question
	 */
	public static function classPathLookup(string $class)
	{
		$parts = explode('\\', $class);
		$foundationPath = dirname(__FILE__);
		if (array_shift($parts) != __NAMESPACE__) {
			return false;
		} elseif (count($parts) < 1) {
			return false;
		}
		$classPath = implode('/', $parts);
		return "{$foundationPath}/{$classPath}.php";
	}

	public static function meditate($nonException)
	{ #TODO this is also in debug, so consolidate/improve
		ob_start();
		print_r($nonException);
		$output = ob_get_contents();
		ob_end_clean();
		return gettype($nonException) ."[\n{$output}\n]";
	}


	/**
	 * scan a file for the first class definition and return the class name
	 * @param string $file path
	 * @param string $class name
	 * @returns classes match
	 */
	public static function classIs(string $file, string $class)
	{
		$pattern = self::REGEX_CLASS;
		return (self::parseClass($file, $pattern) == $class);
	}

	/**
	 * scan a file for the first class definition extending another class
	 * and return the parent class name
	 * @param string $file path
	 * @param string $class name
	 * @return parent class matches
	 */
	public static function parentClassIs(string $file, string $class)
	{
		$pattern = self::REGEX_PARENT_CLASS;
		if (strpos($class, '*') == (strlen($class) - 1)) {
			$wholeMatch = false;
			$class = substr($class, 0, strlen($class) - 1);
		} else {
			$wholeMatch = true;
		}
		return (
			$wholeMatch
			? self::parseClass($file, $pattern) == $class
			: strpos(self::parseClass($file, $pattern), $class) === 0
		);
	}

	/**
	 * 
	 * @param string $file path
	 * @param string $pattern to match
	 * @return string $class name
	 */
	protected static function parseClass(string $file, string $pattern)
	{
		if (!file_exists($file) || !is_readable($file)) {
			return null;
		}
		$file = file_get_contents($file); #TODO #2.0.0 cache these results
		$matches = null;
		preg_match($pattern, $file, $matches);
		return
			$matches && array_key_exists(1, $matches)
			? $matches[1]
			: '';
	}

	/**
	 * Inserts a new filepath into the path if is not already present. It will
	 * be added after the specified other path, the end if none is specified, and
	 * the beginning if the second param is false
	 * @param string $filepath to add
	 * @param string $after
	 * @return boolean
	 */	
	public static function insertPath(string $filepath, $after = null)
	{
		$realPath = realpath($filepath);
		if(!$realPath){
			return false;
		}
		$originalPaths = explode(\PATH_SEPARATOR, ini_get('include_path'));

		if ($after === false) {
			$newPaths = array();
			$placed = false;
		} else {
			$newPaths = array($filepath);
			$placed = true;
		}
		foreach ($originalPaths as $path) {
			$newPaths[] = $path;
			if (!$placed) {
				$currentReal = realpath($path);
				if ($currentReal == $realPath || $path == $filepath) {
					return true;
				}
				$realAfter = realpath($after);
				$currentMatch = $currentReal == $realAfter || $path == $after;
				if ($currentMatch) {
					$newPaths[] = $realPath;
					$placed = true;
				}
			}
		}
		if (!$placed) {
			$newPaths[] = $realPath;
		}
		ini_set('include_path', implode(\PATH_SEPARATOR, $newPaths));
		return true;
	}

// 	/**
// 	 * indicates that the internal autoloader is in charge
// 	 * @var bool
// 	 */
// 	protected static $_autoloadingInstalled = FALSE;

// 	/**
// 	 * list of class prefixes and paths for autoloading
// 	 * @var array
// 	 */
// 	protected static $_autoloaders = array(
// 		'' => array(
// 			'[[APPLIATION_PATH]]/models',
// 		)
// 	);

// 	/**
// 	 * list of named autoloaders and the callable they use for resolution
// 	 * @var array
// 	 */
// 	protected static $_specialAutoloaders = array(
// 		'controller' => array(
// 			'Saf\Environment\Path::resolveControllerPath' => 'Saf\Environment\Path::resolveControllerPath'
// 		)
// 	);

// 	/**
// 	 * list of library autoloaders and the callable they use for resolution
// 	 * @var array
// 	 */
// 	protected static $_libraries = array(
// 		'Saf' => array(
// 			'Saf\Environment\Path::resolveClassPath' => 'Saf\Environment\Path::resolveClassPath'
// 		)
//     );


//     public static function isAutoloading()
// 	{
// 		return self::$_autoloadingInstalled;
// 	}

// 	/**
// 	 * Turns on autoloading. Optionally, it can prep for explicit use
// 	 * of self::autoload only.
// 	 * @param bool $takeover defaults to TRUE
// 	 */
// 	public static function initializeAutoloader($takeover = TRUE)
// 	{
// 		if (!self::$_laced) {
// 			self::_lace();
// 		}
// 		//#TODO #3.0.0 allow for uninstall
// 		if ($takeover && !self::$_autoloadingInstalled) {
// 			spl_autoload_register('Saf\Kickstart::autoload');
// 			self::$_autoloadingInstalled = TRUE;
// 		}
// 		if (self::$_initialized) {
// 			return;
// 		}
// 		foreach(self::$_libraries as $libraryPrefix => $callableList) {
// 			foreach($callableList as $callableIndex => $callable) {
// 				if (
// 					!is_a($callable,'\ReflectionFunctionAbstract', FALSE)
// 					&& (
// 						!is_array($callable)
// 						|| !array_key_exists(0,$callable)
// 						|| !array_Key_exists(1,$callable)
// 						|| !is_object($callable[0])
// 						|| !is_a($callable[1],'\ReflectionFunctionAbstract', FALSE)
// 					)
// 				) {
// 					self::$_libraries[$libraryPrefix][$callableIndex] = self::_instantiateCallable($callable);
// 				}
// 			}
// 		}
// 		foreach(self::$_specialAutoloaders as $loaderName => $callableList) {
// 			foreach($callableList as $callableIndex => $callable) {
// 				if (
// 					!is_a($callable,'\ReflectionFunctionAbstract', FALSE)
// 					&& (
// 						!is_array($callable)
// 						|| !array_key_exists(0,$callable)
// 						|| !array_Key_exists(1,$callable)
// 						|| !is_object($callable[0])
// 						|| !is_a($callable[1],'\ReflectionFunctionAbstract', FALSE)
// 					)
// 				) {
// 					self::$_specialAutoloaders[$loaderName][$callableIndex] = self::_instantiateCallable($callable);
// 				}
// 			}
// 		}
// 		foreach(self::$_autoloaders as $prefix => $paths) {
// 			foreach($paths as $pathIndex => $path) {
// 				self::$_autoloaders[$prefix][$pathIndex] = Path::translatePath($path);
// 			}
// 		}
// 		self::$_initialized = TRUE;
// 	}

// 	/**
// 	 * Attempts to autoload a class by name, checking it against known class
// 	 * name prefixes for libraries (using an optional user provided path 
// 	 * resolution method), or for other files using the Zend Framework naming 
// 	 * conventions in one or more registered paths.
// 	 * Autoloading come preconfigured to look in the APPLICATION_PATH/models/
// 	 * (matching any class), and LIBRARY_PATH/Saf/ (matching prefix 'Saf_') paths.
// 	 * Libraries alway take precidence over other matching rules.
// 	 * The first existing match file will be included.
// 	 * If a $specialLoader is specified, only that loader is searched.
// 	 * Special loaders are only searched when specified.
// 	 * @param string $className name of class to be found/loaded
// 	 * @param string $specialLoader limits the search to a specific special loader
// 	 *
// 	 */
// 	public static function autoload($className, $specialLoader = '')
// 	{
// 		if (class_exists($className, FALSE)) {
// 			return TRUE;
// 		}
// 		if (!self::$_autoloadingInstalled && class_exists('Zend_Loader_Autoloader', FALSE)) {
// 			$zendAutoloader = \Zend_Loader_Autoloader::getInstance();
// 			$currentSetting = $zendAutoloader->suppressNotFoundWarnings();
// 			$zendAutoloader->suppressNotFoundWarnings(TRUE);
// 			$zendResult = \Zend_Loader_Autoloader::autoload($className);
// 			$zendAutoloader->suppressNotFoundWarnings($currentSetting);
// 			return $zendResult;
// 		}
// 		if (!self::$_initialized) {
// 			self::initializeAutoloader(FALSE);
// 		}
// 		if ($specialLoader) {
// 			if (!is_array($specialLoader)) {
// 				$specialLoader = array();
// 			}
// 			foreach($specialLoader as $loader) {
// 				if (array_key_exists($specialLoader, self::$_specialAutoloaders)) {
// 					foreach(self::$_specialAutoloaders[$specialLoader] as $callableList) {
// 						foreach ($callableList as $callableSet) {
// 							if(is_array($callableSet)) {
// 								$callableInstance = $callableSet[0];
// 								$callableReflector = $callableSet[1];
// 							} else {
// 								$callableInstance = NULL;
// 								$callableReflector = $callableSet;
// 							}
// 							$classFile = $callableReflector->invoke($callableInstance, $className);
// 							if (
// 								$classFile
// 								&& Path::fileExistsInPath($classFile)
// 							) {
// 								require_once($classFile);
// 								if (!class_exists($className, FALSE)) {
// 									throw new Exception('Failed to special autoload class'
// 										. (
// 											class_exists('Debug', FALSE) && Debug::isEnabled()
// 											? " {$className}"
// 											: ''
// 										) . '. Found class file, but no definition inside.'
// 									);
// 								}
// 								return TRUE;
// 							}
// 						}
// 					}
// 				} else {
// 					throw new Exception('Failed to special autoload class'
// 						. (
// 							class_exists('Debug', FALSE) && Debug::isEnabled()
// 							? " {$className}"
// 							: ''
// 						) . '. invalid loader specified.'
// 					);
// 				}
// 			}
// 			return FALSE;
// 		}
// 		foreach(self::$_libraries as $classPrefix => $callableList) {
// 			if (strpos($className, $classPrefix) === 0) {
// 				foreach ($callableList as $callableSet) {
// 					if(is_array($callableSet)) {
// 						$callableInstance = $callableSet[0];
// 						$callableReflector = $callableSet[1];
// 					} else {
// 						$callableInstance = NULL;
// 						$callableReflector = $callableSet;
// 					}
// // 					if (!is_object($callableReflector)) {
// // 						print_r(array($className,$classPrefix,$callableList));
// // 						throw new Exception('huh');
// // 					}
// 					$classFile = $callableReflector->invoke($callableInstance, $className);
// 					if (
// 						$classFile
// 						&& Path::fileExistsInPath($classFile)
// 					) {
// 						require_once($classFile);
// 						if (!class_exists($className, FALSE)) {
// 							throw new \Exception('Failed to autoload class'
// 								. (
// 									class_exists('Debug',FALSE) && Debug::isEnabled()
// 									? " {$className}"
// 									: ''
// 								)
// 								. '. Found a library, but no definition inside.'
// 							);
// 						}
// 						return TRUE;
// 					}
// 				}
// 			}
// 		}
// 		foreach (self::$_autoloaders as $classPrefix => $pathList) {
// 			if ('' == $classPrefix || strpos($className, $classPrefix) === 0) {
// 				foreach($pathList as $path) {
// 					$classFile = Path::resolveClassPath($className, $path);
// 					if (
// 						$classFile
// 						&& Path::fileExistsInPath($classFile)
// 					) {
// 						require_once($classFile);
// 						if (!class_exists($className, FALSE)) {
// 							throw new \Exception('Failed to autoload class' 
// 								. (
// 									class_exists('Debug', FALSE) && Debug::isEnabled()
// 									? " {$className}"
// 									: ''
// 								) 
// 								. '. Found a file, but no definition inside.'
// 							);
// 						}
// 						return TRUE;
// 					}
// 				}
// 			}
// 		}
// 		if (!class_exists($className, FALSE)) {
// 			throw new \Exception('Failed resolving file to autoload class' 
// 				. (
// 					class_exists('Debug', FALSE) && Debug::isEnabled()
// 					? " {$className}"
// 					: ''
// 				) 
// 				. '.'
// 			);
// 		}
// 		return TRUE;
// 	}

// 	/**
// 	 * Registers a new library for autoloading. Default behavior is to
// 	 * try to load from the LIBRARY_PATH using Zend Framework naming conventions.
// 	 *
// 	 */
// 	public static function addLibrary($libraryPrefix, $resolutionCallable = NULL, $preposition = self::POSITION_AFTER)
// 	{
// 		self::initializeAutoloader(FALSE);
// 		if ('' == $resolutionCallable || is_null($resolutionCallable)) {
// 			$resolutionCallable = 'Saf\Environment\Path::resolveClassPath';
// 		}
// 		$instantiatedCallable = self::_instantiateCallable($resolutionCallable);
// 		if (array_key_exists($libraryPrefix, self::$_libraries)) {
// 			if ($preposition) {
// 				self::$_libraries[$libraryPrefix] = array_merge(
// 					array(
// 						$resolutionCallable => $instantiatedCallable
// 					),
// 					self::$_libraries[$libraryPrefix]
// 				);
// 			} else {
// 				self::$_libraries[$libraryPrefix][$resolutionCallable] =
// 				self::_instantiateCallable($resolutionCallable);
// 			}
// 			return;
// 		}
// 		foreach(self::$_libraries as $existingPrefix => $existingLibrary){
// 			 if (strpos($existingPrefix, $libraryPrefix) === 0) {
// 				$newLibMap = array();
// 				foreach(self::$_libraries as $currentPrefix => $currentSpec) {
// 					if ($currentPrefix == $existingPrefix) {
// 						$newLibMap[$libraryPrefix] = array($resolutionCallable => $instantiatedCallable);
// 						$newLibMap[$currentPrefix] = $currentSpec;
// 					} else {
// 						$newLibMap[$currentPrefix] = $currentSpec;
// 					}
// 				}
// 				self::$_libraries = $newLibMap;
// 				return;
// 			}
// 		}
// 		self::$_libraries[$libraryPrefix] = array(
// 			$resolutionCallable => $instantiatedCallable
// 		);
// 	}

// 	public static function getLibrarySpec()
// 	{
// 		self::initializeAutoloader(FALSE);
// 		return self::$_libraries;
// 	}

// 	/**
// 	 * remove the library resolver specified, or all entries for that library if null
// 	 * @param string $libraryPrefix
// 	 * @param string $callable
// 	 */
// 	public static function removeLibrary($libraryPrefix, $callable = NULL)
// 	{ //#TODO #3.0.0 translate non-string callables
// 		self::initializeAutoloader(FALSE);
// 		if (!array_key_exists($libraryPrefix, self::$_libraries)) {
// 			return FALSE;
// 		}
// 		if (is_null($callable)) {
// 			unset(self::$_libraries[$libaryPrefix]);
// 			return array($libraryPrefix => $callable);
// 		}
// 		if (array_key_exists($callable, self::$_libraries[$libaryPrefix])) {
// 			unset(self::$_libraries[$libaryPrefix][$callable]);
// 			return array($libraryPrefix => $callable);
// 		}
// 		return FALSE;
// 	}

// 	/**
// 	 * Registers one or more new prefix/path pairs. Prefixes may be the empty
// 	 * string to match any class. Paths may be empty to match the path.
// 	 *
// 	 */
// 	public static function addAutoloader($prefix, $path, $preposition = self::POSITION_AFTER)
// 	{
// 		self::initializeAutoloader(FALSE);
// 		$path = Path::translatePath($path);
// 		if (array_key_exists($prefix, self::$_autoloaders)) {
// 			if ($preposition) {
// 				self::$_autoloaders[$prefix] = array_merge(
// 					array($path),
// 					self::$_autoloaders[$prefix]
// 				);
// 			} else {
// 				self::$_autoloaders[$prefix][] = $path;
// 			}
// 			return;
// 		}
// 		foreach(self::$_autoloaders as $existingPrefix => $existingPaths){
// 			 if (strpos($existingPrefix, $prefix) === 0) {
// 				$newMap = array();
// 				foreach(self::$_autoloaders as $currentPrefix => $currentSpec) {
// 					if ($currentPrefix == $existingPrefix) {
// 						$newMap[] = $path;
// 						$newMap[] = $currentSpec;
// 					} else {
// 						$newMap[] = $currentSpec;
// 					}
// 				}
// 				self::$_autoloaders = $newMap;
// 				return;
// 			}
// 		}
// 		self::$_autoloaders[$prefix] = array($path);
// 	}

// 	/**
// 	 * Unregisters an autoloader. It can remove only one matching path, or 
// 	 * all that are registered. Libraries cannot be removed once added.
// 	 *
// 	 */
// 	public static function removeAutoloader($prefix, $path = NULL)
// 	{
// 		self::initializeAutoloader(FALSE);
// 		if (!array_key_exists($prefix, self::$_autoloaders)) {
// 			return FALSE;
// 		}
// 		if (is_null($path)) {
// 			unset(self::$_autoloaders[$prefix]);
// 			return array($prefix => $path);
// 		}
// 		if (array_key_exists($path, self::$_autoloaders[$prefix])) {
// 			unset(self::$_autoloaders[$prefix][$path]);
// 			return array($prefix => $path);
// 		}
// 		return FALSE;
// 	}

// 	public static function getPathSpec()
// 	{
// 		self::initializeAutoloader(FALSE);
// 		return self::$_autoloaders;
// 	}

// 	/**
// 	 * Registers one or more new name/resolution pairs.
// 	 * @param string $name
// 	 * @param string $callable
// 	 * @param bool $preposition
// 	 */
// 	public static function addSpecialAutoloader($name, $resolutionCallable = NULL , $preposition = self::POSITION_AFTER)
// 	{
// 		self::initializeAutoloader(FALSE);
// 		if ('' == $resolutionCallable || is_null($resolutionCallable)) {
// 			$resolutionCallable = 'Saf\Environment\Path::resolveClassPath';
// 		}
// 		$instantiatedCallable = self::_instantiateCallable($resolutionCallable);
// 		if (array_key_exists($name, self::$_specialAutoloaders)) {
// 			if ($preposition) {
// 				self::$_specialAutoloaders[$name] = array_merge(
// 					array(
// 						$resolutionCallable => $instantiatedCallable
// 					),
// 					self::$_specialAutoloaders[$name]
// 				);
// 			} else {
// 				self::$_specialAutoloaders[$name][$resolutionCallable] =
// 				self::_instantiateCallable($resolutionCallable);
// 			}
// 			return;
// 		}
// 		self::$_specialAutoloaders[$name] = array(
// 			$resolutionCallable => $instantiatedCallable
// 		);
// 	}

// 	public static function removeSpecialAutoloader($name, $callable = FALSE)
// 	{ //#TODO #3.0.0 translate non-string callables
// 		self::initializeAutoloader(FALSE);
// 		if (!array_key_exists($name, self::$_specialAutoloaders)) {
// 			return FALSE;
// 		}
// 		if (is_null($callable)) {
// 			unset(self::$_specialAutoloaders[$name]);
// 			return array($name => $callable);
// 		}
// 		if (array_key_exists($callable, self::$_specialAutoloaders[$name])) {
// 			unset(self::$_specialAutoloaders[$name][$callable]);
// 			return array($name => $callable);
// 		}
// 		return FALSE;
// 	}

// 	public static function getSpecialLoaders()
// 	{
// 		self::initializeAutoloader(FALSE);
// 		return self::$_specialAutoloaders;
// 	}

// 	/**
// 	 * takes a callable, and returns a Reflector that can be invoked.
// 	 * In the case of instantiated callables, the return is an array
// 	 * bundling the object [0] and method reflector [1], or a relector class (ReflectionFunction or static ReflectionMethod)
// 	 * @param string $callable
// 	 * @return array 0 object or string, 1 Reflection
// 	 */
// 	protected static function _instantiateCallable($callable)
// 	{//#TODO #2.0.0 this is a candidate for being made public
// 		if (is_string($callable) && strpos($callable, '::') === FALSE) {
// 			return new \ReflectionFunction($callable);
// 		}
// 		if (!is_array($callable)) {
// 			$callable = explode('::', $callable);
// 			return new \ReflectionMethod($callable[0],$callable[1]);
// 		} else {
// 			return array($callable[0], new \ReflectionMethod($callable[0],$callable[1]));
// 		}
// 	}

}