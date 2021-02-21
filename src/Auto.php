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
	public static function loadInternalClass(string $class, bool $required = true)
	{
		$file = self::classPathLookup($class);
		if (file_exists($file) && is_readable($file)) {
			$required ? (require_once($file)) : (include_once($file));
			return true; //#TODO #2.0.0 only because require triggers fatal, return class_exists?
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

	public static function meditate($meditationData, $depth = 0)
	{ #TODO this is also in debug, so consolidate/improve
		$tab = str_repeat('  ', $depth + 1);
		$style = $depth ? ' style="padding-left:2rem;margin-top: -1.2rem;"' : '';
		$inline = false;
		ob_start();
		if (is_array($meditationData)) {
			foreach($meditationData as $key => $value) {
				$key = is_int($key) ? $key : "'{$key}'";
				$value = self::meditate($value, $depth + 1);
				print("\n{$tab}{$key}:{$value}");
			}
		} elseif (is_a($meditationData, 'Exception')) {
			//$font = 'font-family:\'Helvetica Neue\',Helvetica,Roboto,Arial,sans-serif;';
			$font = 'font-family:UniversLight;'; #TODO handle this with themeing styles instead;
			$isMeditation = get_class($meditationData) == 'Saf\Agent\Meditation';
			$exceptionClass = $isMeditation ? 'meditation' : 'exception';
			$code = $meditationData->getCode();
			$idData = $isMeditation ? "data-id=\"{$code}\" " : '';
			print("{$tab}<div {$idData}class=\"{$exceptionClass}\" style=\"white-space:normal;{$font}\">");
			print("{$tab}<div>" . $meditationData->getMessage() .'</div>');
			print("{$tab}<div>" . $meditationData->getFile() . ' : ' . $meditationData->getLine() . '</div>');
			$trace = $meditationData->getTraceAsString();
			print("\n{$tab}<pre class=\"trace\">{$trace}</pre>");
			if ($meditationData->getPrevious()) {
				print(self::meditate($meditationData->getPrevious(), $depth + 1));
			}
			print('</div>');
		} elseif (is_object($meditationData)) {
			$class = get_class($meditationData);
			print("\n{$tab}{$class}<span class=\"inner-data\" style=\"display:none;\">");
			print_r($meditationData);
			print('</span>');
		} else {
			$inline = true;
			$style = $depth ? ' style="display:inline;"' : '' ;
			print_r($meditationData);
		}
		$blockCap = !$inline ? str_repeat('  ', $depth) : '';
		$output = ob_get_contents();
		ob_end_clean();
		return gettype($meditationData) ."[<pre class=\"data\"{$style}>{$output}</pre>{$blockCap}]";
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