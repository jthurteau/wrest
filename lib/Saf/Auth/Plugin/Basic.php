<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base Auth Plugin Class and handler for Apache Basic Auth integration

*******************************************************************************/
class Saf_Auth_Plugin_Basic { //#TODO #1.5.0 make a base class extend

	protected $_pluginName = 'Apache Basic Auth';
	protected $_config = array();

	public function __construct($config = array())
	{
		$this->_config = $config;
		if (is_array($this->_config) && array_key_exists('publicLabel', $this->_config)) {
			$this->_pluginName = $this->_config['publicLabel'];
		}
	}

	public function auth(){
		$username = $this->getProvidedUsername();
		if ($username) {
			Saf_Auth::setStatus(TRUE);
			return TRUE;
		}
		return FALSE;
	}

	public function isLoggedIn()
	{
		$username = $this->getProvidedUsername();
		return !is_null($username) && '' != trim($username);
	}

	public function logout()
	{
		return TRUE;
	}

	public function getPublicName()
	{
		return $this->_pluginName;
	}

	public function getExternalLoginUrl()
	{
		return '';
	}

	public function getExternalLogoutUrl()
	{
		return '';
	}

	public function getProvidedUsername()
	{
		return 
			array_key_exists('PHP_AUTH_USER', $_SERVER)
			? trim($_SERVER['PHP_AUTH_USER'])
			: NULL;
	}

	public function setUsername($username)
	{
		$_SERVER['PHP_AUTH_USER'] = $username;
	}

	public function getProvidedPassword()
	{
		return '';
	}

	public function getUserInfo($what = NULL){
		if (is_null($what)) {
			return array(
				Saf_Auth::PLUGIN_INFO_USERNAME => $this->getProvidedUsername(),
				Saf_Auth::PLUGIN_INFO_REALM => 'apache'
			);
		} else {
			switch($what){
				case Saf_Auth::PLUGIN_INFO_USERNAME :
					return $this->getProvidedUsername();
				case Saf_Auth::PLUGIN_INFO_REALM :
					return 'apache';
				default:
					return NULL;
			}
		}
	}

	public function fail()
	{
		if (Saf_Debug::isEnabled()) {
			Saf_Debug::out('Authentication Declined.');
		}
	}

	protected function _succeed()
	{
		return TRUE;
	}

	public function setPluginStatus($success, $errorCode)
	{
		//only some plugins will need to do this.
	}

	public function promptsForInfo()
	{
		return TRUE;
	}

	public function postLogin()
	{
		return TRUE;
	}

}