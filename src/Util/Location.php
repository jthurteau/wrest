<?php
/**
 * Utility helper class for managing breadcrumb navigation
 */

declare(strict_types=1);

namespace Saf\Util;
//baseUrl
use Saf\Hash;

class Location{

	protected static $location = '';
	protected static $crumbs = ['Home' => './'];
	
	public static function set($location)
	{
		self::$location = $location;
	}
	
	public static function setCrumbs($crumbs = [])
	{
		if (is_null($crumbs)) {
			$crumbs = [];
		} else if (is_object($crumbs) && method_exists($crumbs, 'toArray')) {
			$crumbs = $crumbs->toArray();
		}
		self::$crumbs = $crumbs;
	}
	
	public static function setCrumbsFromConfig($config = [])
	{
		$baseUrl = '';//Saf_Registry::get('baseUrl');
		if (is_null($config)) {
			$config = [];
		} else if (is_object($config) && method_exists($config, 'toArray')) {
			$config = $config->toArray();
		}
		$crumbs = array();
		if (count($config) == 1) {
			reset($config);
			$sample = current($config);
			if (Hash::isNumericArray($sample)) {
				$config = $sample;
			}
		} else if (key_exists('url', $config) && key_exists('label', $config)) {
			$config = [['url' => $config['url'], 'label' => $config['label']]];
		}
		foreach($config as $fallBackLabel => $linkConfig) {
			if (is_object($linkConfig) && method_exists($linkConfig, 'toArray')) {
				$linkConfig = $linkConfig->toArray();
			}
			
			if (is_array($linkConfig)) {
				$label = array_key_exists('label', $linkConfig) ? $linkConfig['label'] : $fallBackLabel;
				if (array_key_exists('url', $linkConfig)) {
					$url = str_replace('[[baseUrl]]', $baseUrl, $linkConfig['url']);
					
					$crumbs[$label] = ['url' => $url];
				} else {
					$crumbs[$label] = ['status' => 'current'];
				}
			} else {
				$crumbs[$linkConfig] = ['status' => 'current'];
			}
		}
		self::setCrumbs($crumbs);
	}
	
	public static function pushCrumb($label, $crumb = ['status' => 'current'])
	{
		if (!$crumb) {
			$crumb == array('status' => 'current');
		}
		self::$crumbs[$label] = $crumb;
	}
	
	public static function removeCrumb($label)
	{
		if (key_exists($label, self::$crumbs)) {
			unset(self::$crumbs[$label]);
		}
	}
	
	public static function updateCrumb($label, $crumb = ['status' => 'current'], $newLabel = '')
	{
		if (key_exists($label, self::$crumbs)) {
			if ($newLabel == '' ) {
				self::$crumbs[$label] = $crumb;
			} else {
				$newCrumbs = array();
				foreach(self::$crumbs as $oldLabel => $oldCrumb) {
					if ($oldLabel == $label) {
						$newCrumbs[$newLabel] = $crumb;
					} else {
						$newCrumbs[$oldLabel] = $oldCrumb;
					}
				}
				self::$crumbs = $newCrumbs;
			}
		} else {
			self::pushCrumb($newLabel !== '' ? $newLabel : $label ,$crumb);
		}
	}
	
	public static function append($location)
	{
		self::$location .= $location;
	}
	
	public static function get()
	{
		return self::$location;
	}
	
	public static function getCrumbs()
	{
		return self::$crumbs;
	}
}