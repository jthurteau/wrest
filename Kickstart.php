<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for starting up an application and preparing the framework.
Also provides autoloading.

*******************************************************************************/

require_once(LIBRARY_PATH . '/Saf/Filter/Truthy.php');
require_once(LIBRARY_PATH . '/Saf/Status.php');
require_once(LIBRARY_PATH . '/Saf/Debug.php');
require_once(LIBRARY_PATH . '/Saf/Layout.php');

//#TODO #1.0.0 update function header docs
class Saf_Kickstart {

	/**
	 * instructs an inserter to add the new item to the start of the list
	 * @var bool
	 */
	const POSITION_BEFORE = TRUE;

	/**
	 * instructs an inserter to add the new item to the end of the list
	 * @var unknown_type
	 */
	const POSITION_AFTER = FALSE;

	const CAST_STRING = 0;
	const CAST_BOOL = 1;
	const CAST_INT = 2;
	const CAST_FLOAT = 3;
	const CAST_COMMA_ARRAY = 4;
	const CAST_SPACE_ARRAY = 5;
	const CAST_NEWLINE_ARRAY = 6;
	const CAST_CSV = 7;
	const CAST_TSV = 8;
	const CAST_JSON = 9;
	const CAST_XML_MAP = 10;
	
	const MODE_AUTODETECT = NULL;
	const MODE_NONE = 'none';
	const MODE_SAF = 'saf';
	const MODE_ZFMVC = 'zendmvc';
	const MODE_ZFNONE = 'zendbare';
	const MODE_LF = 'laravel'; //#TODO #2.0.0 support Laravel
	const MODE_NF = 'nette'; //#TODO #2.0.0 support Nette
	const MODE_S2F = 'symphony2'; //#TODO #2.0.0 support Sympony2

	const REGEX_VAR =
		'/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';
	const REGEX_CLASS =
		'/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s{]/';
	const REGEX_PARENT_CLASS =
		'/class\s+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s+extends\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+[\s{]/';

	/**
	 * indicates the mode that the environment as been initialized in
	 * @var string
	 */
	protected static $_kicked = NULL;

	/**
	 * indicates that the internal lists have been normalized
	 */
	protected static $_initialized = FALSE;

	/**
	 * indicates that the internal autoloader is in charge
	 * @var bool
	 */
	protected static $_autoloadingInstalled = FALSE;

	protected static $_controllerPath = 'Controller';

	protected static $_modelPath = 'Model';

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
			'Saf_Kickstart::resolveControllerPath' => 'Saf_Kickstart::resolveControllerPath'
		)
	);

	/**
	 * list of library autoloaders and the callable they use for resolution
	 * @var array
	 */
	protected static $_libraries = array(
		'Saf' => array(
			'Saf_Kickstart::resolveClassPath' => 'Saf_Kickstart::resolveClassPath'
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

	/**
	 * Accepts a constant name and optional default value. Will attempt to
	 * see if the constant is already defined. If not it will see if there
	 * is a local dot file in the pwd or path that specifies this value.
	 * If neither of these are true it will use the provided default or
	 * die with a failure message in the event there is no value source.
	 * @param string $constantName constant to look for and set
	 * @param mixed $constantDefault default value to use if none are available
	 * @param int $cast class constant to coerce any loaded values into, provided defaults not coerced, defaults to string
	 * @return true or execution is halted
	 */
	public static function defineLoad($constantName, $constantDefault = NULL, $cast = self::CAST_STRING)
	{
		if (is_array($constantName)){
			foreach($constantName as $currentConstantIndex => $currentConstantValue) {
				$currentConstantName =
					is_array($currentConstantValue)
					? $currentConstantIndex
					: $currentConstantValue;
				$currentDefault =
					is_array($currentConstantValue) && array_key_exists(0, $currentConstantValue)
						? $currentConstantValue[0]
						: $constantDefault;
				$currentCast =
					is_array($currentConstantValue) && array_key_exists(1, $currentConstantValue)
						? $currentConstantValue[1]
						: $cast;
				self::defineLoad($currentConstantName, $currentDefault, $currentCast);
			}
			return TRUE;
		}
		$constantName = self::filterConstantName($constantName);
		$sourceFileMatch = self::dotFileMatch($constantName);
		$sourceFilename =
			is_array($sourceFileMatch)
			? $sourceFileMatch[0]
			: $sourceFileMatch;
		$lowerConstantName = strtolower($constantName);
		$failureMessage = 'This application is not fully configured. '
			. "Please set a value for {$constantName}. "
			. (
				$sourceFilename
				? "There is a {$sourceFilename} file that may specify this configuration option, but it is not readable."
				: "This value can be specified locally in a .{$lowerConstantName} file."
			);
		if (!defined($constantName)) {
			if ($sourceFileMatch) {
				$sourceValue = self::load($sourceFileMatch);
				define($constantName, self::cast($sourceValue, $cast));
			} else if (!is_null($constantDefault)) {
				define($constantName, $constantDefault);
			} else {
				die($failureMessage);
			}
		}
		return TRUE;
	}

	public static function valueLoad($valueName, $valueDefault = NULL, $cast = self::CAST_STRING)
	{
		if (is_array($valueName)){
			$return = array();
			foreach($valueName as $currentValueIndex => $currentValue) {
				$currentValueName =
					is_array($currentValue)
						? $currentValueIndex
						: $currentValue;
				$currentDefault =
					is_array($currentValue) && array_key_exists(0, $currentValue)
						? $currentValue[0]
						: $valueDefault;
				$currentCast =
					is_array($currentValue) && array_key_exists(1, $currentValue)
						? $currentValue[1]
						: $cast;
				$return[$valueName] = self::valueLoad($currentValueName, $currentDefault, $currentCast);
			}
			return $return;
		}
		$valueName = self::filterConstantName($valueName);
		$sourceFileMatch = self::dotFileMatch($valueName);
		$sourceFilename =
			is_array($sourceFileMatch)
				? $sourceFileMatch[0]
				: $sourceFileMatch;
		$lowerValueName = strtolower($valueName);
		$failureMessage = 'This application is not fully configured. '
			. "Please set a value for {$valueName}. "
			. (
				$sourceFilename
				? "There is a {$sourceFilename} file that may specify this configuration option, but it is not readable."
				: "This value can be specified locally in a .{$lowerValueName} file."
			);
		if ($sourceFileMatch) {
			$sourceValue = self::load($sourceFileMatch);
			return self::cast($sourceValue, $cast);
		} else if (!is_null($valueDefault)) {
			return $valueDefault;
		} else {
			die($failureMessage);
		}
	}

	public static function mapLoad($valueName, $memberCast = self::CAST_STRING)
	{
		$valueName = self::filterConstantName($valueName);
		$sourceFileMatch = self::dotFileMatch($valueName, TRUE);
		$sourceFilename =
			is_array($sourceFileMatch)
				? $sourceFileMatch[0]
				: $sourceFileMatch;
		$lowerValueName = strtolower($valueName);
		$failureMessage = 'This application is not fully configured. '
			. "Please set a map of values for {$valueName}. "
			. (
				$sourceFilename
				? "There is a {$sourceFilename} file that may specify this configuration option, but it is not readable."
				: "This value can be specified locally in a .{$lowerValueName} file."
			);
		if ($sourceFileMatch) {
			$sourceValues = self::load($sourceFileMatch);
			$castValues = array();
			foreach($sourceValues as $index=>$value) {
				$castValues[$index] = self::cast($value, $memberCast);
			}
			return $castValues;
		} else if (!is_null($valueDefault)) {
			return $valueDefault;
		} else {
			die($failureMessage);
		}
	}
	
	public static function filterConstantName($name)
	{
		$filteredName = preg_replace('/[^A-Za-z0-9_\x7f-\xff]/', '', $name);
		return strtoupper($filteredName);
	}

	protected static function load($sourceConfig, $lineBreak = PHP_EOL, $delim = ':')
	{
		if (is_array($sourceConfig)) {
			$lines = explode($lineBreak, trim(file_get_contents($sourceConfig[0])));
			if ('' != trim($sourceConfig[1])) {
				$matchLine = NULL;
				foreach($lines as $line) {
					$upperMatch = strtoupper(trim($sourceConfig[1]) . $delim);
					$upperLine = strtoupper($line);
					if (strpos($upperLine,$upperMatch) === 0) {
						$matchLine = substr($line, strlen($upperMatch));
						break;
					}
				}
				return $matchLine;
			} else {
				$values = array();
				foreach($lines as $line) {
					$index = NULL;
					$indexEnd = strpos($line, $delim);
					if ($indexEnd !== FALSE) {
						$index = substr($line, 0, $indexEnd);
						$values[$index] = substr($line, $indexEnd + strlen($delim));
					} else {
						$values[] = $line;
					}
				}
				return $values;
			}
		} else {
			return (trim(file_get_contents($sourceConfig)));
		}
	}

	protected static function _filterDotFileName($constantName)
	{
		$lowerConstantName = strtolower($constantName);
		$safeConstantName = preg_replace('/[^a-z0-9_.]/', '', $lowerConstantName);
		$prefSource = self::_prefFileSource('.' . $safeConstantName);
		return
			strlen($safeConstantName) > 1
			? "{$prefSource}/.{$safeConstantName}"
			: FALSE;
	}
	
	protected static function _filterDoubleDotFileName($constantName)
	{
		$lowerConstantName = strtolower($constantName);
		$safeConstantName = preg_replace('/[^a-z0-9_.]/', '', $lowerConstantName);
		$prefSource = self::_prefFileSource('..' . $safeConstantName, FALSE);
		if (strlen($safeConstantName) <= 1) {
			return FALSE;
		}
		if (is_array($prefSource)) {
			$result = array();
			foreach($prefSource as $prefSourceOption) {
				$result[] = "{$prefSourceOption}/..{$safeConstantName}";
			}
			return $result;
		} else {
			return "{$prefSource}/..{$safeConstantName}";
		}
	}

	protected static function _prefFileSource($file, $ifExists = TRUE)
	{
		return (
			defined('APPLICATION_PATH')
			? (
				file_exists(APPLICATION_PATH . "/{$file}")				
				? APPLICATION_PATH
				: ($ifExists ? '.' : array(APPLICATION_PATH, '.'))
			) : '.'
		);
	}

	public static function dotFileMatch($constantName, $allowMulti = FALSE)
	{
		$sourceFilename = self::_filterDotFileName($constantName);
		if(is_readable($sourceFilename)){
			return $sourceFilename;
		} else {
			return self::_doubleDotFileScan($constantName, $allowMulti);
		}
	}

	protected static function _doubleDotFileScan($constantName, $allowMulti = FALSE)
	{
		$sourceFilename = self::_filterDoubleDotFileName($constantName);
		if (!is_array($sourceFilename)) {
			$sourceFilename = array($sourceFilename);
		}
		$scans = array();
		foreach($sourceFilename as $currentSourceFilename) {
			$components = explode('_', $currentSourceFilename);
			foreach($components as $componentIndex => $componentName) {
				$fullMatch = implode('_', array_slice($components, 0, $componentIndex + 1));
				if ($fullMatch != $currentSourceFilename) {
					$scans[] = array(
						$fullMatch,
						implode('_', array_slice($components, $componentIndex + 1))
					);
				} else if ($allowMulti && is_readable($fullMatch)) {
					return array($fullMatch, '');
				}
			}
		}
		foreach($scans as $scanParts) {
			if (is_readable($scanParts[0])) {
				$lines = explode(PHP_EOL, trim(file_get_contents($scanParts[0])));
				foreach($lines as $line) {
					$upperMatch = strtoupper($scanParts[1]);
					$upperLine = strtoupper($line);
					if (strpos($upperLine, $upperMatch) === 0) {
						return $scanParts;
					}
				}
			}
		}
		return NULL;
	}

	public static function dotFileExists($constantName)
	{
		$sourceFilename = self::_filterDotFileName($constantName);
		return is_readable($sourceFilename);
	}

	/**
	 * Inserts a new filepath into the path if is not already present. It may
	 * be relative to a specific other path or the whole list. If the specified
	 * other path is not present, the new path always goes to the end. The
	 * specified is also translated into a realpath() for matching purposes. If the
	 * new path was already present it's old locaiton is removed. This function
	 * will only add valid, exiecutable paths. Returns true if the path is added.
	 * @param string $filepath to add
	 * @param string $preposition optional specification of after (default) or before
	 * @param string $place for relative placement with preposition.
	 * @return boolean
	 */	
	public static function addInPath($filepath, $preposition = self::POSITION_AFTER, $place = '*')
	{
		$realNewPath = realpath($filepath);
		if(!$realNewPath){
			return FALSE;
		}
		$oldPaths = explode(PATH_SEPARATOR, ini_get('include_path'));
		$newPaths = array();
		$placed = FALSE;
		$realPlace = realpath($place);
		foreach ($oldPaths as $path) {
			$currentRealPath = realpath($path);
			$currentMatchesNew = (
				($currentRealPath && ($currentRealPath == $realNewPath))
				|| $path == $filepath
			);
			$currentMatchesPlace = (
				($currentRealPath && ($currentRealPath == $realPlace))
				|| $path == $place
			);
			print_r(array($filepath,$path,$currentMatchesNew,$currentMatchesPlace));
			print("\n");
			if (self::POSITION_BEFORE == $preposition && !$placed) {
				if ('*' == $place || $currentMatchesPlace) {
					$newPaths[] = $realNewPath;
					if (!$currentMatchesNew) {
						$newPaths[] = $path;
					}
					$placed = TRUE;
				}
			} else if (!$placed) {
				if ('*' == $place || $currentMatchesPlace) {
					if (!$currentMatchesNew) {
						$newPaths[] = $path;
					}
					$newPaths[] = $realNewPath;
					$placed = TRUE;
				}
			} else if (!$currentMatchesNew) {
				$newPaths[] = $path;
			}

/*			if(strpos($filepath, PATH_SEPARATOR) !== 0
				? file_exists(realpath($path . $filepath))
				: file_exists(realpath($path . PATH_SEPARATOR . $filepath))
			){
				return TRUE;
			}*/
		}
		if (!$placed) {
			$newPaths[] = $realNewPath;
		}
		ini_set('include_path', implode(PATH_SEPARATOR, $newPaths));
		return TRUE;
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
		if(strpos($filepath, '/') === 0) {
			return file_exists($filepath);
		}
		$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
		foreach($paths as $path){
			if (file_exists(realpath("{$path}/{$filepath}"))){
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * @param string $filepath
	 * @param int $order
	 */
	public static function addIfNotInPath($filePath, $order = self::POSITION_AFTER)
	{
		$includePaths = explode(PATH_SEPARATOR, get_include_path());
		$inPath = FALSE;
		foreach($includePaths as $includePath) {
			if (realpath($includePath) == realpath($filePath)) {
				$inPath = TRUE;
				break;
			}
		}
		if (!$inPath) {
			if ($order === self::POSITION_AFTER) {
				$includePaths[] = $filePath;
			} else {
				$includePaths = array_unshift($includePaths, $filePath);
			}
			set_include_path(implode(PATH_SEPARATOR, $includePaths));
		}
	}

	public static function translatePath($path)
	{
		return str_replace(
			array(
				'[[APPLICATION_PATH]]',
				'[[LIBRARY_PATH]]',
				'[[PUBLIC_PATH]]',
				'[[INSTALL_PATH]]'
			), array(
				APPLICATION_PATH,
				LIBRARY_PATH,
				PUBLIC_PATH,
				INSTALL_PATH
			), $path
		);
	}

	/**
	 * Converts a classname and base file path into a full path based
	 * on Zend Framework class naming conventions. i.e. _ to /
	 *
	 */
	public static function resolveClassPath($className, $basePath = '')
	{
		if ('' == $basePath) {
			$basePath = LIBRARY_PATH;
		}
		$classNameComponents = explode('_', $className);
		$classPath = $basePath;
		foreach ($classNameComponents as $nameComponent) {
			$classPath .= "/{$nameComponent}";
		}
		$classPath .= '.php'; //#TODO #2.0.0 handle other file formats
		return $classPath;
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
	 * Converts a controller class name into a filepath
	 * @param string $className
	 */
	public static function resolveControllerPath($controllerName, $coerce = TRUE)
	{
		$controllerPath = APPLICATION_PATH . '/' . self::$_controllerPath . '/';
		$className = $coerce ? self::resolveControllerClassName($controllerName) : $controllerName;
		if (!is_readable($controllerPath)) {
			throw new Exception('This application does not support controllers.');
		}
		return(self::resolveClassPath($className, $controllerPath));
	}

	/**
	 * casts a value into a different type
	 * @param mixed $value
	 * @param int $cast matching one of the CAST_ class constants
	 * @return mixed
	 */
	public static function cast($value, $cast)
	{
		switch ($cast) {
			case self::CAST_BOOL :
				return Saf_Filter_Truthy::filter($value);
			default: //#TODO #2.0.0 support the other cast features
				return $value;
		}
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
		//#TODO #3.0.0 allow for uninstall
		if ($takeover && !self::$_autoloadingInstalled) {
			spl_autoload_register('Saf_Kickstart::autoload');
			self::$_autoloadingInstalled = TRUE;
		}
		if (self::$_initialized) {
			return;
		}
		foreach(self::$_libraries as $libraryPrefix => $callableList) {
			foreach($callableList as $callableIndex => $callable) {
				if (
					!is_a($callable,'ReflectionFunctionAbstract', FALSE)
					&& (
						!is_array($callable)
						|| !array_key_exists(0,$callable)
						|| !array_Key_exists(1,$callable)
						|| !is_object($callable[0])
						|| !is_a($callable[1],'ReflectionFunctionAbstract', FALSE)
					)
				) {
					self::$_libraries[$libraryPrefix][$callableIndex] = self::_instantiateCallable($callable);
				}
			}
		}
		foreach(self::$_specialAutoloaders as $loaderName => $callableList) {
			foreach($callableList as $callableIndex => $callable) {
				if (
					!is_a($callable,'ReflectionFunctionAbstract', FALSE)
					&& (
						!is_array($callable)
						|| !array_key_exists(0,$callable)
						|| !array_Key_exists(1,$callable)
						|| !is_object($callable[0])
						|| !is_a($callable[1],'ReflectionFunctionAbstract', FALSE)
					)
				) {
					self::$_specialAutoloaders[$loaderName][$callableIndex] = self::_instantiateCallable($callable);
				}
			}
		}
		foreach(self::$_autoloaders as $prefix => $paths) {
			foreach($paths as $pathIndex => $path) {
				self::$_autoloaders[$prefix][$pathIndex] = self::translatePath($path);
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
			$zendAutoloader = Zend_Loader_Autoloader::getInstance();
			$currentSetting = $zendAutoloader->suppressNotFoundWarnings();
			$zendAutoloader->suppressNotFoundWarnings(TRUE);
			$zendResult = Zend_Loader_Autoloader::autoload($className);
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
								&& self::fileExistsInPath($classFile)
							) {
								require_once($classFile);
								if (!class_exists($className, FALSE)) {
									throw new Exception('Failed to special autoload class'
										. (
											class_exists('Saf_Debug', FALSE) && Saf_Debug::isEnabled()
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
							class_exists('Saf_Debug', FALSE) && Saf_Debug::isEnabled()
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
						&& self::fileExistsInPath($classFile)
					) {
						require_once($classFile);
						if (!class_exists($className, FALSE)) {
							throw new Exception('Failed to autoload class'
								. (
									class_exists('Saf_Debug',FALSE) && Saf_Debug::isEnabled()
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
					$classFile = self::resolveClassPath($className, $path);
					if (
						$classFile
						&& self::fileExistsInPath($classFile)
					) {
						require_once($classFile);
						if (!class_exists($className, FALSE)) {
							throw new Exception('Failed to autoload class' 
								. (
									class_exists('Saf_Debug', FALSE) && Saf_Debug::isEnabled()
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
			throw new Exception('Failed resolving file to autoload class' 
				. (
					class_exists('Saf_Debug', FALSE) && Saf_Debug::isEnabled()
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
			$resolutionCallable = 'Saf_Kickstart::resolveClassPath';
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
						$newLibMap[$libraryPrefix] = array($resolutionCallable => instantiatedCallable);
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
		$path = self::translatePath($path);
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
			$resolutionCallable = 'Saf_Kickstart::resolveClassPath';
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
			return new ReflectionFunction($callable);
		}
		if (!is_array($callable)) {
			$callable = explode('::', $callable);
			return new ReflectionMethod($callable[0],$callable[1]);
		} else {
			return array($callable[0], new ReflectionMethod($callable[0],$callable[1]));
		}
	}
	
	/**
	 * loads resource configuration data
	 */
	public static function getConfigResource($resource)
	{//#TODO #2.0.0 this is muddled could be a Zend_Config or Saf_Config... also seems out of place now
		$resources = Zend_Registry::get('config')->get('resources', APPLICATION_ENV);
		if ($resources) {
			$resource = $resources->$resource;
			if ($resource || is_array($resource)) {
				return
					is_array($resource)
					? $resource
					: $resource->toArray();
			}
		}
		return NULL;
	}

	/**
	 * loads configuration data
	 */
	public static function getConfigItem($item)
	{
		$item = Zend_Registry::get('config')->get($item, APPLICATION_ENV);
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
	 * Starts the kickstart process, preparing the environment for a framework matching $mode
	 * @param string $mode Any of the class's MODE_ constants
	 * @return string the mode chosen (useful in the case of default MODE_AUTODETECT)
	 */
	public static function go($mode = self::MODE_AUTODETECT)
	{
		if (!self::$_kicked) {
			defined('APPLICATION_START_TIME') || define('APPLICATION_START_TIME', microtime(TRUE));
			Saf_Kickstart::defineLoad('PUBLIC_PATH', realpath('.'));
		 	Saf_Kickstart::defineLoad('INSTALL_PATH', realpath('..'));
		 	Saf_Kickstart::defineLoad('LIBRARY_PATH', realpath(__DIR__));
		 	Saf_Kickstart::defineLoad('APPLICATION_PATH', realpath(INSTALL_PATH . '/application'));
		 	Saf_Kickstart::defineLoad('APPLICATION_ENV', 'production');
			Saf_Kickstart::defineLoad('APPLICATION_ID', '');
		 	if (
		 		'' == APPLICATION_PATH 
		 		|| '' == realpath(APPLICATION_PATH . '/configs')
		 		|| !is_readable(APPLICATION_PATH)
		 	) {
	 			header('HTTP/1.0 500 Internal Server Error');
	 			die('Unable to find the application core.');	 		
		 	}
		 	if ($mode == self::MODE_AUTODETECT) {
				$mode = self::_goAutoMode();
		 	}
			switch($mode) {
		 		case self::MODE_ZFMVC:
		 			$configFile = 'zend_application';
		 			break;
		 		case self::MODE_SAF:
					if (defined('APPLICATION_ID') && APPLICATION_ID){
						$applicationFilePart = strtolower(APPLICATION_ID); //#TODO #1.0.0 filter file names safely
						$configFile = "saf_application.{$applicationFilePart}";
						if (file_exists(APPLICATION_PATH . "/configs/{$configFile}.xml")) {
							break;
						}
					}
					$configFile = 'saf_application';
		 			break;
		 		default:
		 			$configFile = 'application';
		 			break;
		 	}
			Saf_Kickstart::defineLoad('APPLICATION_CONFIG', APPLICATION_PATH . "/configs/{$configFile}.xml");
			Saf_Kickstart::defineLoad('APPLICATION_STATUS', 'online');
			Saf_Kickstart::defineLoad('APPLICATION_BASE_ERROR_MESSAGE', 'Please inform your technical support staff.');
			Saf_Kickstart::defineLoad('APPLICATION_DEBUG_NOTIFICATION', 'Debug information available.');
		 	self::_goPreRoute();
		 	Saf_Kickstart::defineLoad('APPLICATION_FORCE_DEBUG', FALSE, Saf_Kickstart::CAST_BOOL);
		 	if (APPLICATION_FORCE_DEBUG) {
		 		Saf_Debug::init(Saf_Debug::DEBUG_MODE_FORCE, NULL , FALSE);
		 	}	
			if ($mode == self::MODE_ZFMVC || $mode == self::MODE_ZFNONE) {
				self::_goZend();
			}
			if ($mode == self::MODE_SAF) {
				self::_goSaf();
			}
			self::_goPreBoot();
			self::$_kicked = $mode;
		}
 		return self::$_kicked;
	}

	protected static function _goAutoMode()
	{
		$applicationClassFile =
			APPLICATION_PATH . '/' . APPLICATION_ID . '.php';
		$bootstrapClassFile =
			APPLICATION_PATH . '/Bootstrap.php';
		if (file_exists($applicationClassFile)) {
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
				|| self::fileExistsInPath('Zend/Application.php')
			? self::MODE_ZFNONE
			: self::MODE_NONE;
	}

	/**
	 * steps to prep the route
	 */
	protected static function _goPreRoute()
	{
		defined('ROUTER_NAME') || define('ROUTER_NAME', 'index');
		$routerIndexInPhpSelf = strpos($_SERVER['PHP_SELF'], strtolower(ROUTER_NAME) . '.php');
		$routerPathLength = (
			$routerIndexInPhpSelf !== FALSE
			? $routerIndexInPhpSelf
			: PHP_MAXPATHLEN
		);
		defined('ROUTER_PATH') || define('ROUTER_PATH', NULL);
		$defaultRouterlessUrl = substr($_SERVER['PHP_SELF'], 0, $routerPathLength);
		Saf_Kickstart::defineLoad('APPLICATION_BASE_URL',
			ROUTER_NAME != ''
			? $defaultRouterlessUrl
			: './'
		);
		Saf_Kickstart::defineLoad('APPLICATION_HOST', (
			array_key_exists('HTTP_HOST', $_SERVER) && $_SERVER['HTTP_HOST']
			? $_SERVER['HTTP_HOST']
			: 'commandline'
		));
		Saf_Kickstart::defineLoad('STANDARD_PORT', '80');
		Saf_Kickstart::defineLoad('SSL_PORT', '443');
		Saf_Kickstart::defineLoad('APPLICATION_SSL', //#TODO #2.0.0 this detection needs work
			array_key_exists('HTTPS', $_SERVER)
				&& $_SERVER['HTTPS']
				&& $_SERVER['HTTPS'] != 'off'
			, self::CAST_BOOL
		);
		Saf_Kickstart::defineLoad('APPLICATION_PORT', (
				array_key_exists('SERVER_PORT', $_SERVER) && $_SERVER['SERVER_PORT']
				? $_SERVER['SERVER_PORT']
				: 'null'
		));
		Saf_Kickstart::defineLoad('APPLICATION_SUGGESTED_PORT',
			(APPLICATION_SSL && APPLICATION_PORT != SSL_PORT)
				|| (!APPLICATION_SSL && APPLICATION_PORT == STANDARD_PORT)
			? ''
			: APPLICATION_PORT
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
		Saf_Kickstart::defineLoad('DEFAULT_RESPONSE_FORMAT', (
			'commandline' == APPLICATION_PROTOCOL
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
			Saf_Debug::out('Unable to connect LibXML to integrated debugging. libxml_use_internal_errors() not supported.', 'NOTICE');
		}
		if (defined('APPLICATION_TZ')) {
			date_default_timezone_set(APPLICATION_TZ);
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
		require_once(LIBRARY_PATH . '/Saf/Application.php');
	}
	
	/**
	 * steps to take when preparing for a Zend Framework application
	 */
	protected static function _goZend()
	{
		Saf_Kickstart::defineLoad('ZEND_PATH', '');
		if (ZEND_PATH != '') {
			self::addIfNotInPath(ZEND_PATH);
		}
		if (
			!file_exists(ZEND_PATH . '/Zend/Application.php')
			&& !file_exists(LIBRARY_PATH . '/Zend/Application.php')
			&& !self::fileExistsInPath('Zend/Application.php')
		) {
			header('HTTP/1.0 500 Internal Server Error');
			die('Unable to find Zend Framework.');
		}
		if (
			!is_readable('Zend/Application.php')
			&& !is_readable(ZEND_PATH . '/Zend/Application.php')
			&& !is_readable(LIBRARY_PATH . '/Zend/Application.php')
		) {
			header('HTTP/1.0 500 Internal Server Error');
			die('Unable to access Zend Framework.');
		}
		if (
			file_exists(LIBRARY_PATH . '/Zend/Application.php')
			&& is_readable(LIBRARY_PATH . '/Zend/Application.php')
			&& !self::fileExistsInPath('Zend/Application.php')
		) {
			self::addIfNotInPath(LIBRARY_PATH);
		}
		require_once('Zend/Application.php');
		self::$_controllerPath = 'controllers';
	}
	
	/**
	 * @return string the mode the application was started in
	 */
	public static function getMode()
	{
		return self::$_kicked;
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
		$rootUrl = defined('APPLICATION_BASE_URL') ? APPLICATION_BASE_URL : '';
		$title = 'Configuration Error';
		if (is_null(self::$_exceptionView)) {
			self::$_exceptionView = APPLICATION_PATH . '/views/scripts/error/error.php';
		}
		include(self::$_exceptionView);
		if (class_exists('Saf_Debug', FALSE)) {
			Saf_Debug::dieSafe();
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
