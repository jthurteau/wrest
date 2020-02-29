<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Form //#TODO 1.1.0 move into lib
{
	
	public static function getBaseUrl()
	{
		return Zend_Registry::get('siteUrl');
	}
	
	public static function arrayToMultiOptions($array)
	{
		$result = '';
		$tagIndex = 0;
		if (!is_array($array)) {
			throw new Exception('Parameter for generating form dropdown was not an array.');
		}
		foreach($array as $index=>$option){ //#TODO #2.0.0 currently only supports one level of optGroup
			if (is_array($option)) {
				foreach($option as $subIndex=>$subOption) {
					$result .= "<option{$index}><key>{$subIndex}</key><value>{subOption}</value></option{$index}>\n";
				}
			} else {
				$result .= "<option{$tagIndex}><key>{$index}</key><value>{$option}</value></option{$tagIndex}>\n";
			}
			
			$tagIndex++;
		}
		return $result;
	}
}