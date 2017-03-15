<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility functions for loading configuration from XML files

*******************************************************************************/

require_once(LIBRARY_PATH . '/Saf/Config.php');
require_once(LIBRARY_PATH . '/Saf/Exception/NotConfigured.php');

class Saf_Config_Xml extends Saf_Config{
	
	public function open($filePath, $section = '', $merge = self::LOAD_MERGE) 
	{
		self::assertFile($filePath);
		$this->_loadedSources[] = array($filePath, $section, $merge);
		$newXml = simplexml_load_file($filePath);
		if (!$newXml) {
			Saf_Debug::outData(array('failedXMLConfigLoad' =>libxml_get_errors())); //#TODO #1.1.0 utlilitize this see Ems_Api->parseResponse
			//if(!Saf_Debug::isEnabled()){ //#TODO #1.1.0 not for production...
				//Saf_Debug::flushBuffer(TRUE);
			//}
		}
		$newConfiguration = self::parse($newXml, $section);
		if(!$newConfiguration) {
			$debugData = (
				Saf_Debug::isEnabled()
				? " Loading file: {$filePath}."
				: ''
			);
			throw new Exception(self::$_corruptedExceptionMessage . $debugData);
		}
		if ($merge == self::LOAD_QUARANTINED) {
			return new Saf_Config($newConfiguration);
		} else if (is_null($this->_configuration) || $merge == self::LOAD_REPLACE) {
			$this->_configuration = $newConfiguration;
		} else {
			$this->_configuration =
				self::merge(
					$this->_configuration,
					$newConfiguration
				);
		}
		return $this;
	}
 

	public static function parse($xmlObject, $section='', $existingConfigs = array())
	{ //#TODO #2.0.0 do we need to pass existing for some reason? i.e. reflective/nesting issues?
		$newConfig = array();
		$extensionMap = array();
		$sourceMap = array();
		if (!is_object($xmlObject)) {
			throw new Exception(self::$_corruptedExceptionMessage);
		}
		$rootName = $xmlObject->getName();
		if ('' == $section || $section == $rootName) {
			return self::_xmlToArray($xmlObject);
		} else {
			$childNodes = $xmlObject->children();
			foreach($childNodes as $child) {
				$childName = $child->getName();
				if ($childName == $section) {
					if (array_key_exists($childName, $sourceMap)) {
						$debugData = (
							Saf_Debug::isEnabled()
							? " Section name: {$childName}."
							: ''
						);
						throw new Exception(self::$_duplicateSectionExceptionMessage . $debugData);
					}
				}
				$attributes = $child->attributes();
				$extends = '';
				$sourceMap[$childName] = array();
				foreach($attributes as $attributeName => $attributeNode) {
					$attributeValue = (string)$attributeNode;
					if ('src' == $attributeName) {
						$sourceMap[$childName][] = $attributeValue;
						//#TODO #2.0.0 check and throw a _warning_ if the file does not exist, even if not needed for this source
					}
					if ('extends' == $attributeName) {
						$extends = $attributeValue;
					}
				}
				$extensionMap[$childName] = $extends;
				$sourceMap[$childName][] = $child;
			}
			if (!array_key_exists($childName, $sourceMap)) {
				$debugData = (
					Saf_Debug::isEnabled()
					? " Section name: {$section}."
					: ''
				);
				throw new Exception(self::$_missingSectionExceptionMessage . $debugData);
			}
			$extending = self::_generateRequirements($section, $extensionMap);
			foreach($sourceMap as $targetSection => $sourceList) {
				if (
					$targetSection == $section 
					|| in_array($targetSection, $extending)
				) {
					foreach($sourceList as $currentIndex => $currentSource) {
						if (is_string($currentSource)) {
							//#TODO #2.0.0 load external XML
						}
						$sourceMap[$targetSection][$currentIndex] = self::_xmlToArray($currentSource, $sourceMap, $targetSection);
					}
					$sourceMap[$targetSection] = 
						count($sourceMap[$targetSection]) > 1
						? self::merge($sourceMap[$targetSection][0],$sourceMap[$targetSection][1])
						: $sourceMap[$targetSection][0];
				}
			}
			$return =
				array_key_exists($section, $sourceMap) && !is_null($sourceMap[$section])
				? $sourceMap[$section]
				: array();
			$inherit = array_shift($extending);
			while($inherit) {
				$return = self::merge($sourceMap[$inherit], $return);
				$inherit = array_shift($extending);
			}
			return $return;
		}
		//#TODO #2.0.0 iterate through $source to populate $newConfig
	}

	protected static function _xmlToArray($xml, &$sourceMap = array(), $current = '')
	{//#NOTE DO NOT EDIT $sourceMap!
		$return = NULL;
		$extensionMap = array();
		foreach($xml->children() as $child) {
			$generatedValue = NULL;
			$childName = $child->getName();
			$extends = '';
			$attributes = $child->attributes();
			foreach($attributes as $attributeName => $attributeNode) {
				$attributeValue = (string)$attributeNode;
				if ('src' == $attributeName) {
					if (!array_key_exists($childName, $return)) {
						$return[$childName] = array($attributeValue);
					} else {
						$return[$childName][] = $attributeValue;
					}
					//#TODO #2.0.0 check and throw a _warning_ if the file does not exist, even if not needed for this source
				}
				if ('extends' == $attributeName) {
					$extends = $attributeValue;
				}
				if (
					$childName == 'const' 
						&& !$child->count() 
						&& 'name' == $attributeName
						&& defined($attributeValue)
				) {
					 $generatedValue = constant($attributeValue);
				} else if (
					$childName == 'const'
					&& !$child->count()
					&& 'name' == $attributeName
					&& !defined($attributeValue)
				) {
					throw new Saf_Exception_NotConfigured(self::$_missingConstantExceptionMessage . ": {$attributeValue}");
				}
			}
			if($extends){
				$extensionMap[$childName] = $extends;
			}
			$childCurrent = "$current:$childName";

			if ($generatedValue) {
				$return = 
					is_null($return)
					? $generatedValue
					: (
						is_array($return)
						? array_merge($return, array($generatedValue))
						: array_merge(array($return), array($generatedValue))
					);
			} else {
				$childValue = (
					!$child->count()
					? (string)$child
					: self::_xmlToArray($child, $sourceMap, $childCurrent)
				);
				if (is_null($return)) {
					$return = array();
				}
				if (!array_key_exists($childName, $return)) {
					$return[$childName] = $childValue;
				} else if (is_array($return[$childName])) {
					$oldChildren = $return[$childName];
					if (!is_array($oldChildren)) {
						$return[$childName] = array($oldChildren);
					}
					$return[$childName][] = $childValue;
				} else {
					$return[$childName] = array($return[$childName],$childValue);
				}
			}
		}
		if (is_array($return)) {
			foreach($return as $childName => $childValue) {
				//$extending = self::_generateRequirements($childName, $extensionMap);
				if (array_key_exists($childName, $extensionMap)) {
					$currentValue = $return[$childName];
					$mergeValue = 
						self::getFromOptional(
							$extensionMap[$childName],
							$return, 
							self::getFromOptional(
								$extensionMap[$childName],
								$sourceMap
							)
						);
					if (is_null($mergeValue)) {
						throw new Exception('Unable to find value to extend');
					}
					if (is_null($currentValue) || (!is_array($currentValue) && '' == trim($currentValue))) {
						$return[$childName] = $mergeValue;
					} else if (is_null($mergeValue) || (!is_array($mergeValue) && '' == trim($mergeValue))) {
						$return[$childName] = $currentValue;
					} else {
						$return[$childName] = self::merge($mergeValue,$currentValue);
					}
					
				}
			}
		}
		return $return;
	}	
}