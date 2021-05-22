<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base Auth Plugin Class and handler for Apache Basic Auth integration
 */

namespace Saf\Auth\Plugin;

class Basic extends Base {

	protected $pluginName = 'Apache Basic Auth';

	public function setUsername($username)
	{
		$_SERVER['PHP_AUTH_USER'] = $username;
	}

	public function getProvidedUsername()
	{
		return 
			key_exists('PHP_AUTH_USER', $_SERVER)
			? trim($_SERVER['PHP_AUTH_USER'])
			: null;
	}

	public function getUserInfo($what = null){
		if (is_null($what)) {
			return array(
				Auth::PLUGIN_INFO_USERNAME => $this->getProvidedUsername(),
				Auth::PLUGIN_INFO_REALM => 'apache'
			);
		} else {
			switch($what){
				case Auth::PLUGIN_INFO_USERNAME :
					return $this->getProvidedUsername();
				case Auth::PLUGIN_INFO_REALM :
					return 'apache';
				default:
					return null;
			}
		}
	}
}