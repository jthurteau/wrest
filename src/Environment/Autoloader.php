<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Autoloader for modern SAF instances
 */

namespace Saf\Environment;

require_once(__DIR__ . '/Path.php');
require_once(__DIR__ . '/Parser.php');

class Autoloader 
{

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

	/**
	 * indicates that this autoloader is registered
	 */
	protected static $registered = false;

	/**
	 * list of class matches and resolvers for autoloading
	 * @var array
	 */
	protected static $autoloaders = [];

	/**
	 * memory of successful and failed class matches
	 * @var array
	 */	
	protected static $memory = [];

    /**
     * returns true if this autoloader has been registered with the spl
     */
	public static function isRegistered()
	{
		return self::$registered;
	}

	/**
	 * Makes sure this autoloader is registered and optionally sets internal loaders.
     * @param array $loaders optional loader configs to pre-populate
	 */
	public static function init(array $loaders = [])
	{
		self::insert();
		foreach($loaders as $loader) {
			if (is_array($loader) && count($loader) >= 2) {
				self::register(...$loader);
			}
		}
	}

	/**
	 * Registers this autoloader with the spl
	 *
	 */
	protected static function insert()
	{
		if (!self::$registered) {
			//spl_autoload_register('\Saf\Environment\Autoloader::autoload');
			spl_autoload_register(function(string $class){
				self::autoload($class);
			});
			self::$registered = true;
		}
	}

	/**
	 * Registers one or more new match/resolver pairs. Matches may be the empty
	 * string to match any class name. Paths may be empty to match the current path.
	 *
	 */
	public static function register(string $prefix, $lookup, $preposition = self::POSITION_AFTER)
	{
		self::insert();
		if (!key_exists($prefix, self::$autoloaders)) {
			if ($preposition) {
				self::$autoloaders = array_merge(
					[$prefix => [$lookup]],
					self::$autoloaders
				);
			} else {
			   self::$autoloaders[$prefix] = [$lookup];
			}
		} else {
			if ($preposition) {
				array_unshift(self::$autoloaders[$prefix], $lookup);
			} else {
				self::$autoloaders[$prefix][] = $lookup;
			}
		}
	}

	/**
	 * Autoload an internally registerd class
	 * @param string $className
	 *
	 */
	public static function autoload(string $className)
	{
		if (class_exists($className, false)) {
			return;
		}
		self::handle($className, self::match($className));
	}

	/**
	 * Uses the internally registered loaders to find possible files for $className
	 * @param string $className
	 * @param bool $parseScan only match files that match introspection
	 */
	public static function match(string $className, $fromMemory = true)
	{
		if ($fromMemory && key_exists($className, self::$memory)) {
			return self::$memory[$className];
		}
		$oldMemory = key_exists($className, self::$memory) ? self::$memory[$className] : [];
		$matches = [];
		//self::$memory[$className] = [];
		foreach (self::$autoloaders as $prefix => $lookupList) {
			if ('' == $prefix || strpos($className, $prefix) === 0) { #TODO support regex
				foreach($lookupList as $lookup) {
					#TODO bring back the feature from the legacy autoloader to support callable names via reflector
					$match =
						is_callable($lookup)
						? $lookup($className)
						: self::resolveClassPath($className, $lookup);
					if (is_null($match)) {
						self::stub($className, 'matchless', '', "Autoloader yeilded no path for {$className}");
					} else {
						$matches[] = $match;
					}
				}
			}
		}
		foreach($oldMemory as $oldFile) {
			if (!in_array($oldFile, $matches)) {
				self::stub($className, 'missing', $file, 'Previously matched file no longer present.');
			}
		}
		return $matches;
	}

	/**
	 * Tries $classFiles for successful matches to declare $className
	 * @param string $className
	 */
	protected static function handle(string $className, array $classFiles, bool $preflight = false)
	{
		foreach($classFiles as $file) {
			$issue = null;
			$found = false;
			try{
				if (trim($file) != '' && Path::fileExistsInPath($file)) {
					$found = true;
					$preflight || require_once($file);
				} elseif(trim($file) != '') {
					$issue = "Autoloader path yeilded no file for {$className}";
				} else {
					$issue = "Unable to access {$file} file for {$className}";
				}
			} catch (\ParseError $e) {
				$issue = (
					'Parse Exception in ' . $e->getFile() 
					. ' on line ' . $e->getLine() . ': ' 
					. $e->getMessage() . ' '
				);
			} catch (\Error | \Exception $e) {
				$issue = (
					get_class($e). ' in ' . $e->getFile() 
					. ' on line ' . $e->getLine() . ': ' 
					. $e->getMessage() . ' '
				);
			}
			if ($issue) {
				$classStub = $found ? 'invalid' : 'absent';
				self::stub($className, $classStub, $file, $issue);
			} elseif (class_exists($className, false)) {
				self::remember($className, $file);
				return true;
			} else {
				self::stub($className, 'mismatch', $file, 'File does not contain declaration, empirical');
			}
		}
		return false;
	}

	/**
	 * Tries $classFiles for the first successful match to declare $className
	 * @param string $className
	 */
	protected static function scan(string $className, array $classFiles)
	{
		foreach($classFiles as $file) {
			if (self::handle($className, $classFiles, true)) {
				if (Parser::fileContainsClassDeclaration($className, $file)) {
					//self::remember($className, $file)
					return $file;
				} else {
					self::stub($className, 'mismatch', $file, 'File does not contain declaration, infered');
				}
			}
		}
	}

	public static function test(string $className, $file = null)
	{
		$return = [
			'exists' => false,
			'resolver' => null,
			'tried' => [],
		];
		if ($className != '') { #TODO validate className
			if (is_null($file)) {
				$match = self::scan($className, self::match($className));
			} else {
				$scanned = [$file];
				$match =
					self::scan($className, $scanned)
					? $file
					: null;
				$return['tried'] = $scanned;
			}
			foreach(self::$memory as $token => $memoryfile){
				$stubMatch =
					substr($token, -(strlen($className) + 1)) === ":{$className}";
				if ($token == $className || $stubMatch) {
					$fileMatch = 
						(is_string($memoryfile) && $memoryfile == $file)
						|| (is_array($memoryfile) && key_exists($file, $memoryfile));
					if ($fileMatch) {
						$return['tried'][$token] = $memoryfile;
					} else {
						key_exists('skipped', $return) || ($return['skipped'] = []);
						$return['skipped'][$token] = $memoryfile;
					}
				}
			}
			$return['exists'] = class_exists($className, false);
			$return['resolver'] = $match;
		}
		return $return;
	}

	/**
	 * Converts a classname and base file path into a full path based
	 * on naming conventions. i.e. "_" or "\" to "/"
	 * @param string $className
	 * @param string $classPath
	 */
	public static function resolveClassPath(string $className, string $classPath)
	{
		$nameSpaceMode = strpos($className, '\\') !== false;
		$classNameComponents = $nameSpaceMode ? explode('\\', $className) : explode('_', $className);
		foreach ($classNameComponents as $nameComponent) {
			$classPath .= "/{$nameComponent}";
		}
		$classPath .= '.php';
		return $classPath;
	}

	protected static function remember($className, $file)
	{
		if (!key_exists($className, self::$memory)) {
			self::$memory[$className] = [$file];
		} elseif (!in_array($file, self::$memory[$className])) {
			self::$memory[$className][] = $file;
		}
	}

	protected static function stub($className, $stub, $file, $issue)
	{
		if (
			key_exists($className, self::$memory)
		) {
			$fileIndex = null; #TODO this should be a Hash:: method
			foreach(self::$memory[$className] as $memoryIndex => $memoryFile) {
				if ($file === $memoryFile) {
					$fileIndex == $memoryIndex;
					break;
				}
			}
			if (!is_null($fileIndex)) {
				unset(self::$memory[$fileIndex]);
			}
		}
		$stubName = "{$stub}:{$className}";
		if (!key_exists($stubName, self::$memory)) {
			self::$memory[$stubName] = [$file => $issue];
		} else {
			self::$memory[$stubName][$file] = $issue;
		}
	}

	public static function getMemory()
	{
		return self::$memory;
	}
}