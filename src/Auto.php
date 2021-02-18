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
}