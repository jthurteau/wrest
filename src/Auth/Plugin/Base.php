<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base Auth Plugin Class and handler for Apache Basic Auth integration
 */

namespace Saf\Auth\Plugin;

use Saf\Auth;
use Saf\Debug;

abstract class Base {

	protected $pluginName = 'Undefined';
	protected $config = [];

	public function __construct($config = array())
	{
		$this->config = $config;
		if (is_array($this->config) && key_exists('publicLabel', $this->config)) {
			$this->pluginName = $this->config['publicLabel'];
		}
	}

	public function getPublicName()
	{
		return $this->pluginName;
	}	
    
    public function auth()
    {
		if ($this->getProvidedUsername()) {
			Auth::setStatus(true);
			return true;
		}
		return false;
	}

	abstract public function setUsername($username);

	abstract public function getProvidedUsername();

	abstract public function getUserInfo($what = null);

	public function isLoggedIn()
	{
		$username = $this->getProvidedUsername();
		return !is_null($username) && '' != trim($username);
	}

	public function logout()
	{
		return false;
	}

    public function supportslogout()
	{
		return false;
	}

    public function supportslogin()
	{
		return false;
	}

	public function getExternalLogoutUrl()
	{
		return '';
	}

	public function getExternalLoginUrl()
	{
		return '';
	}

    public function storesPassword()
    {
        return false;
    }

	public function getProvidedPassword()
	{
		return '';
	}

	public function fail()
	{
		if (Debug::isEnabled()) {
			Debug::out('Authentication Declined.');
		}
	}

    protected function succeed()
	{
		return true;
	}

	public function setPluginStatus($success, $errorCode)
	{
		//#NOTE only some plugins will need to do this.
	}

	public function promptsForInfo()
	{
		return false;
	}

    /**
     * actions the plugin will execute after login
     */
	public function postLogin()
	{
		return true;
	}

}