<?php //#SCOPE_OS_PUBLIC
namespace Saf\Environment;
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for managing paths.

*******************************************************************************/

//#TODO #1.0.0 update function header docs
class Path {

	const REGEX_VAR =
		'/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';
	const REGEX_CLASS =
		'/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\s{]/';
	const REGEX_PARENT_CLASS =
		'/class\s+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s+extends\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+[\s{]/';


    /**
	 * instructs an inserter to add the new item to the start of the list
	 * @var bool
	 */
	const POSITION_BEFORE = TRUE;

	/**
	 * instructs an inserter to add the new item to the end of the list
	 * @var bool
	 */
    const POSITION_AFTER = FALSE;
    
    protected static $_controllerPath = 'Controller';

    protected static $_modelPath = 'Model';
    
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
		$oldPaths = explode(\PATH_SEPARATOR, ini_get('include_path'));
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
		ini_set('include_path', implode(\PATH_SEPARATOR, $newPaths));
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
		$paths = explode(\PATH_SEPARATOR, ini_get('include_path'));
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
		$includePaths = explode(\PATH_SEPARATOR, get_include_path());
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
			set_include_path(implode(\PATH_SEPARATOR, $includePaths));
		}
	}

	public static function translatePath($path)
	{
		return str_replace(
			array(
				'[[APPLICATION_PATH]]',
				// '[[LIBRARY_PATH]]',
				'[[PUBLIC_PATH]]',
				'[[INSTALL_PATH]]'
			), array(
				\APPLICATION_PATH,
				// \LIBRARY_PATH,
				\PUBLIC_PATH,
				\INSTALL_PATH
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
			$basePath = \INSTALL_PATH;
		}
		$classNameComponents = explode('_', $className);
		$classPath = $basePath;
		foreach ($classNameComponents as $nameComponent) {
			$classPath .= "/{$nameComponent}";
		}
		$classPath .= '.php'; //#TODO #2.0.0 handle other file formats
		return $classPath;
    }
    
    /**
	 * Converts a controller class name into a filepath
	 * @param string $className
	 */
	public static function resolveControllerPath($controllerName, $coerce = TRUE)
	{
		$controllerPath = \APPLICATION_PATH . '/' . self::$_controllerPath . '/';
		$className = $coerce ? self::resolveControllerClassName($controllerName) : $controllerName;
		if (!is_readable($controllerPath)) {
			throw new Exception('This application does not support controllers.');
		}
		return(self::resolveClassPath($className, $controllerPath));
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

}