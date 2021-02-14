<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing constants and environment.
 */

namespace Saf\Environment;

use Saf\Cast;
use Saf\Brray;
// use Saf\File\Dot;
// use Saf\File\Env;

require_once(dirname(dirname(__FILE__)) . '/Cast.php');
require_once(dirname(dirname(__FILE__)) . '/Utils/Brray.php');
// require_once(dirname(dirname(__FILE__)) . '/File/Dot.php');
// require_once(dirname(dirname(__FILE__)) . '/File/Env.php');

//#TODO #2.0.0 update function header docs
class Define {

	public const CAST_TYPE_STRING = Cast::TYPE_STRING;

	/**
	 * Looks for a constant returns it if set, otherwise returs the provided default.
	 * @param string $constantName constant to look
	 * @param mixed $constantDefault default value to return if it isn't set
	 */
	public static function get($constantName, $constantDefault = NULL)
	{
		return defined($constantName) ? constant($constantName) : $constantDefault;
	}

	/**
	 * Looks for a constant returns it if it is in a dotfile or env file.
	 * @param string $constantName constant to look
	 * @param mixed $constantDefault default value to return if it isn't set
	 */
	public static function find($constantName, $constantDefault = NULL, $cast = Cast::TYPE_STRING)
	{
		return self::valueLoad($constantName, $constantDefault, $cast);
	}

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
	public static function load($constantName, $constantDefault = NULL, $cast = Cast::TYPE_STRING)
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
				self::load($currentConstantName, $currentDefault, $currentCast);
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
				$sourceValue = self::loadFile($sourceFileMatch);
				define($constantName, Cast::translate($sourceValue, $cast));
			} else if (!is_null($constantDefault)) {
				define($constantName, $constantDefault);
			} else {
				die($failureMessage);
			}
		}
		return TRUE;
	}

	public static function valueLoad($valueName, $valueDefault = NULL, $cast = Cast::TYPE_STRING)
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
			$sourceValue = self::loadFile($sourceFileMatch);
			return Cast::translate($sourceValue, $cast);
		} else if (!is_null($valueDefault)) {
			return $valueDefault;
		} else {
			die($failureMessage);
		}
	}

	public static function mapLoad($valueName, $memberCast = Cast::TYPE_STRING)
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
			$sourceValues = self::loadFile($sourceFileMatch);
			$castValues = array();
			foreach($sourceValues as $index=>$value) {
				$castValues[$index] = Cast::translate($value, $memberCast);
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

	protected static function loadFile($sourceConfig, $lineBreak = \PHP_EOL, $delim = ':')
	{
		if (is_array($sourceConfig)) {
			$lines = explode($lineBreak, trim(file_get_contents($sourceConfig[0])));
			if ('' != trim($sourceConfig[1])) {
				$matchLine = NULL;
				foreach($lines as $line) {
					$upperMatch = strtoupper(trim($sourceConfig[1]) . $delim);
					$upperLine = strtoupper($line);
					if (strpos($upperLine,$upperMatch) === 0) {
						$matchLine = trim(substr($line, strlen($upperMatch)));
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
						$values[$index] = trim(substr($line, $indexEnd + strlen($delim)));
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
				file_exists(\APPLICATION_PATH . "/{$file}")				
				? APPLICATION_PATH
				: ($ifExists ? '.' : array(\APPLICATION_PATH, '.'))
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

	public static function envRead($filepath)
	{
		$return = array();
		if (file_exists($filepath) && is_readable($filepath)) {
			$lines = explode(\PHP_EOL,file_get_contents($filepath));
			foreach($lines as $line) {
				$line = trim($line);
				$split = strpos($line,'=');
				if ($split) {
					$var = substr($line,0,$split);
					$val = substr($line,$split + 1);
					$return[$var] = $val;
				}
			}
		}
		return $return;
	}

}