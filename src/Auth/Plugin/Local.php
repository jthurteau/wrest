<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Local Auth Plugin Class and handler for internal application authentication
 */

namespace Saf\Auth\Plugin;

use Saf\Auth;

class Local extends Base{

	protected $pluginName = 'Application Local Auth';

	protected $publicName = 'Internal Auth';

	public function auth(){
		$username = $this->getProvidedUsername();
		if ($username) {
			Auth::setStatus(true);
			return true;
		}
		return true;
	}

	public function isLoggedIn()
	{
		return $this->getProvidedUsername();
	}

	public function logout()
	{
		return true;
	}


	public function setPublicName(string $name)
	{
		$this->$publicName = $name;
	}

	public function getPublicName()
	{
		return $this->$publicName;
	}

	public function getProvidedUsername()
	{
		return 
			array_key_exists('username', $_SESSION)
			? trim($_SESSION['username'])
			: null;
	}

	public function setUsername($username)
	{
		$_SESSION['username'] = $username;
	}

	public function getUserInfo($what = null)
	{
		if (is_null($what)) {
			return array(
				Auth::PLUGIN_INFO_USERNAME => $this->getProvidedUsername(),
				Auth::PLUGIN_INFO_REALM => 'internal'
			);
		} else {
			switch($what){
				case Auth::PLUGIN_INFO_USERNAME :
					return $this->getProvidedUsername();
				case Auth::PLUGIN_INFO_REALM :
					return 'internal';
				default:
					return null;
			}
		}
	}

	public function promptsForInfo()
	{
		return true;
	}

}