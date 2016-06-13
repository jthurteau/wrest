<?php  //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility helper class for managing breadcrumb navigation

*******************************************************************************/

class Saf_Layout_Location{

	protected static $_location = '';
	protected static $_crumbs = array('Home' => './');
	
	public static function set($location)
	{
		self::$_location = $location;
	}
	
	public static function setCrumbs($crumbs = array())
	{
		if (is_null($crumbs)) {
			$crumbs = array();
		} else if (is_object($crumbs) && method_exists($crumbs, 'toArray')) {
			$crumbs = $crumbs->toArray();
		}
		self::$_crumbs = $crumbs;
	}
	
	public static function setCrumbsFromConfig($config = array())
	{
		$baseUrl = Zend_Registry::get('baseUrl');
		if (is_null($config)) {
			$config = array();
		} else if (is_object($config) && method_exists($config, 'toArray')) {
			$config = $config->toArray();
		}
		$crumbs = array();
		if (count($config) == 1) {
			reset($config);
			$sample = current($config);
			if (Saf_Array::isNumericArray($sample)) {
				$config = $sample;
			}
		} else if (array_key_exists('url', $config) && array_key_exists('label', $config)) {
			$config = array(array('url' => $config['url'], 'label' => $config['label']));
		}
		foreach($config as $fallBackLabel => $linkConfig) {
			if (is_object($linkConfig) && method_exists($linkConfig, 'toArray')) {
				$linkConfig = $linkConfig->toArray();
			}
			
			if (is_array($linkConfig)) {
				$label = array_key_exists('label', $linkConfig) ? $linkConfig['label'] : $fallBackLabel;
				if (array_key_exists('url', $linkConfig)) {
					$url = str_replace('[[baseUrl]]', $baseUrl, $linkConfig['url']);
					
					$crumbs[$label] = array('url' => $url);
				} else {
					$crumbs[$label] = array('status' => 'current');
				}
			} else {
				$crumbs[$linkConfig] = array('status' => 'current');
			}
		}
		self::setCrumbs($crumbs);
	}
	
	public static function pushCrumb($label, $crumb = array('status' => 'current'))
	{
		if (!$crumb) {
			$crumb == array('status' => 'current');
		}
		self::$_crumbs[$label] = $crumb;
	}
	
	public static function removeCrumb($label)
	{
		if (array_key_exists($label, self::$_crumbs)) {
			unset(self::$_crumbs[$label]);
		}
	}
	
	public static function updateCrumb($label, $crumb = array('status' => 'current'), $newLabel = '')
	{
		if (array_key_exists($label, self::$_crumbs)) {
			if ($newLabel == '' ) {
				self::$_crumbs[$label] = $crumb;
			} else {
				$newCrumbs = array();
				foreach(self::$_crumbs as $oldLabel => $oldCrumb) {
					if ($oldLabel == $label) {
						$newCrumbs[$newLabel] = $crumb;
					} else {
						$newCrumbs[$oldLabel] = $oldCrumb;
					}
				}
				self::$_crumbs = $newCrumbs;
			}
		} else {
			self::pushCrumb($newLabel !== '' ? $newLabel : $label ,$crumb);
		}
	}
	
	public static function append($location)
	{
		self::$_location .= $location;
	}
	
	public static function get()
	{
		return self::$_location;
	}
	
	public static function getCrumbs()
	{
		return self::$_crumbs;
	}
}