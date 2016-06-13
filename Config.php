<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for managing configuration data

*******************************************************************************/

require_once(LIBRARY_PATH . '/Saf/Registry.php');
require_once(LIBRARY_PATH . '/Saf/Config/Exception/InvalidEnv.php');

class Saf_Config {

	protected static $_current = array();
	protected static $_cache = array(); //#TODO #2.0.0 implement caching/multiconfig handling
	protected static $_registeredLoaders = array();
	
	protected $_configuration = NULL;

	protected static $_unableToIdentifyConfigType = 'Unable to determine file type of configuration.';
	protected static $_unrecognizedMessage = 'Unrecognized configuration file type.';
	protected static $_notFoundExceptionMessage = 'Unable to find the configuration File.';
	protected static $_unavailableExceptionMessage = 'Requested configuration option is not available.';
	protected static $_corruptedExceptionMessage = 'The configuration file is corrupted.';
	protected static $_duplicateSectionExceptionMessage = 'More than one section matched the requested load.';
	protected static $_missingSectionExceptionMessage = 'No section matched the requested load.';
	protected static $_missingExtentionExceptionMessage = 'No section matched the prerequesite for the requested load.';
	protected static $_circularExtentionExceptionMessage = 'Circular reference detected in prerequesite for the requested load.';

	const LOAD_MERGE = 1;
	const LOAD_OVERLAY = 2;
	const LOAD_UNDERLAY = 3;
	const LOAD_COPY_MERGE = 4;
	const LOAD_COPY_OVERLAY = 5;
	const LOAD_COPY_UNDERLAY = 6;
	const LOAD_REPLACE = 7;
	const LOAD_QUARANTINED = 8;

	protected $_loadedSources = array();
	
	protected function __construct(&$configuration = NULL, $sourceStack = NULL)
	{
		$this->_configuration = 
			is_object($configuration) && is_a($configuration, 'Saf_Config')
			? $configuration->peek()
			: $configuration; //#TODO additional attempts at Array
		if (!is_null($sourceStack)) {
			$this->_loadedSources = $sourceStack;
		}
	}

	private function __clone()
	{//#TODO
	}

	private function __wakeup()
	{//#TODO
	}

	public static function load($filePath = '', $section = '', $merge = self::LOAD_MERGE)
	{
		if ('' == trim($filePath)) {
			$filePath = APPLICATION_CONFIG;
		}
		$fileTypeStart = strrpos($filePath, '.'); //#TODO #2.0.0 support URL style loading
		if (
			$fileTypeStart === 0
			|| $fileTypeStart === FALSE
			|| ($fileTypeStart == strlen($filePath) - 1)
		) {
			$debugData = (
				Saf_Debug::isEnabled()
					? " Loading file: {$filePath}."
					: ''
				);
			throw new Exception(self::$_unableToIdentifyConfigType . $debugData);
		}
		$fileType = ucFirst(strtolower(substr($filePath, $fileTypeStart + 1)));
		try{
			$configLoaderClass = self::_prepareLoader($fileType);
			$loaderConfig = new $configLoaderClass(
				$merge != self::LOAD_QUARANTINED
				? Saf_Config::$_current
				: array()
			);
		} catch (Exception $e) {
			$debugData = (
			Saf_Debug::isEnabled()
				? " Loading file: {$filePath}."
				: ''
			);
			throw new Exception(self::$_unrecognizedMessage . $debugData, NULL, $e);
		}
		return $loaderConfig->open($filePath, $section, $merge);
	}

	public function open($filePath, $section = '', $merge = self::LOAD_MERGE) //#TODO #2.0.0 refine this pattern so it is less confusing?
	{
		throw new Exception("Can't load configs with the base Saf_Config Class.");
	}
	
	public static function parse($configSource, $section='', $existingConfigs = array())
	{
		throw new Exception("Can't parse configs with the base Saf_Config Class.");
	}

	public static function assertFile($filePath)
	{
		if(!file_exists($filePath)){
			$debugData = (
				Saf_Debug::isEnabled()
				? " Loading file: {$filePath}."
				: ''
			);
			throw new Exception(self::$_notFoundExceptionMessage . $debugData);
		}
	}
	
	protected static function _generateRequirements($section, $map, &$return = array(), $visited = array())
	{
		$current = $section;
		if(in_array($section, $visited)) {
			$debugData = (
				Saf_Debug::isEnabled()
				? " Section name: {$section}."
				: ''
			);
			throw new Exception(self::$_circularExtentionExceptionMessage . $debugData);		}
		if(array_key_exists($section, $map)){
			if ('' == $map[$section]) {
				return $return;
			} else {
				$return[] = $map[$section];
				return self::_generateRequirements($map[$section], $map, $return, $visited);
			}
		} else {
			$debugData = (
				Saf_Debug::isEnabled()
				? " Section name: {$section}."
				: ''
			);
			throw new Saf_Config_Exception_InvalidEnv(self::$_missingExtentionExceptionMessage . $debugData);
		}
	}

	public static function merge($configArrayA, $configArrayB)
	{
		$newConfig = array_replace_recursive($configArrayA, $configArrayB);
		return $newConfig;
	}

	public function has($name)
	{
		try{
			$this->get($name);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function getOptional($name, $default = NULL, $cast = NULL){
		try{
			$value = $this->get($name);
		} catch (Exception $e) {
			$value = $default;
		}
		return !is_null($cast) ? $value : Saf_Kickstart::cast($value, $cast);
	}
	
	public function peek()
	{
		return $this->_configuration;
	}
	
	public static function getFrom($name, $source, $cast = NULL)
	{
		$nameComponents =
			is_array($name)
			? $name
			: explode(':', $name);
		$value = self::_get($nameComponents, $source);
		return !is_null($cast) ? $value : Saf_Kickstart::cast($value, $cast);
	}
	
	public static function getFromOptional($name, $source, $default = NULL, $cast = NULL)
	{
		try{
			$value = self::getFrom($name, $source, $cast);
		} catch (Exception $e) {
			$value = $default;
		}
		return !is_null($cast) ? $value : Saf_Kickstart::cast($value, $cast);
	}

	public function get($name, $cast = NULL)
	{
		$nameComponents =
			is_array($name) 
			? $name 
			: explode(':', $name);
		$value = self::_get($nameComponents, $this->_configuration);
		return !is_null($cast) ? $value : Saf_Kickstart::cast($value, $cast);
	}

	protected static function _get($name, $currentPosition)
	{
		foreach ($name as $index=>$subName) {
			if(
				$currentPosition 
				&& array_key_exists($subName, $currentPosition)
			) {
				if($index == count($name) - 1){
					if (is_array($currentPosition[$subName])) {
						$copy =array();
						foreach($currentPosition[$subName] as $copyIndex=>$copyValue) {
							if (!is_numeric($copyIndex)) {
								$copy[$copyIndex] = $copyValue;
							}
						}
						return 
							count($copy) > 0
							? $copy
							: (
								array_key_exists(0, $currentPosition[$subName])
								? $currentPosition[$subName][0]
								: NULL
							);
					} else {
						return $currentPosition[$subName];
					}
				} else if (array_key_exists($index + 1, $name) && '+' == $name[$index + 1]) {
					return $currentPosition[$subName];
				} else {
					$currentPosition = $currentPosition[$subName];
				}
			} else {
				$debugData = Saf_Debug::isEnabled()
					? ' Requested option named: ' . implode(':',$name)
					: '';
				throw new Exception(self::$_unavailableExceptionMessage . $debugData);
			}
		}
	}
	
	public static function valueMap($data, $cast = 'auto'){
		switch($cast)
		{
			case 'boolean' :
			case 'bool' :
				return (boolean) $data;
				break;
			case 'truthy' :
				return Saf_Filter_Truthy::filter($data);
			case 'string' :
				return (string) $data;
			case 'int' :
			case 'integer' :
				return (int) $data;
			case 'float' :
			case 'double' :
			case 'real' :
				return (float) $data;
			default:
				return is_array($data) || is_object($data)
				? self::arrayMap($data)
				: $data;
		}
	}
	
	public static function arrayMap($data, $flatten = FALSE)
	{
		$return = array();
		$increment = 1;
		if(is_array($data)) { 
			foreach ($data as $key=>$value) {
				if(array_key_exists($key, $return)) {
					$return[$key . ($increment++)] = self::valueMap($value);
				} else{
					$return[$key] = self::valueMap($value);
				}
			}
		} else if (is_object($data) && method_exists($data, 'toArray')) {
			$return = self::arrayMap($data->toArray());
		} else if (is_object($data) && method_exists($data, '__toArray')) {
			$return = self::arrayMap($data->__toArray());
		} else if (is_object($data) && in_array('Traversable', class_implements($data))) {
			if(0 ==count($data)) {
				$return = (string)$data;
			} else {
				foreach ($data as $key=>$value) {
					if(array_key_exists($key, $return)) {
						$return[$key . ($increment++)] = self::valueMap($value);
					} else{
						$return[$key] = self::valueMap($value);
					}
				}
			}
		} else {
			$return[] = $data;
		}
		if ($flatten && is_array($return)) {
			$flattenedReturn = array();
			foreach($return as $value) {
				if(is_array($value)) {
					foreach($value as $subKey=>$subValue) {
						$flattenedReturn[$subKey] = $subValue;
					}
				} else {
					$flattenedReturn[] = $value;
				}
			}
			$return = $flattenedReturn;
		}
		return $return;
	}
	
	public static function autoGroup($array) {
		if (!is_array($array) || !array_key_exists(0, $array)) {
			return array($array);
		}
		return $array;
	}
	
	public static function registerLoader($fileType, $className)
	{
		self::$_registeredLoaders[ucFirst(strtolower($fileType))] = $className;
	}
	
	protected static function _prepareLoader($fileType)
	{
		$class = (
			array_key_exists($fileType, self::$_registeredLoaders)
			? self::$_registeredLoaders[$fileType]
			: "Saf_Config_{$fileType}"
		);
		if (!class_exists($class)) {
			$nativePath = LIBRARY_PATH . "/Saf/Config/{$fileType}.php";
			if (!array_key_exists($fileType, self::$_registeredLoaders) && file_exists($nativePath)) {
				require_once($nativePath);
			} else {
				throw new Exception("Unable to find Config Loader for {$fileType}");
			}
		}
		return $class;
	}
	
}