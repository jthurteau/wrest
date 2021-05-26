<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Key based Auth Plugin Class and handler
 */

namespace Saf\Auth\Plugin;

use Saf\Auth;
use Saf\Keys;

class Key extends Base {

	protected $pluginName = 'Application Key Auth';
    protected $usernamePrefix = 'key:';
    protected $keyField = Keys::DEFAULT_KEY_FIELD;
    protected $persistKeys = false;

    public function __constructor($config)
    {
        if (key_exists('keyField', $config)) {
            Keys::setKeyField($config['keyField']);
        }
        if (key_exists('persistKeys', $config)) {
            $this->persistKeys = $config['persistKeys'];
        }
        parent::__constructor($config);
    }

	public function auth($setStatus = true)
	{
        $key = Keys::detect();
		$username = 
            Keys::validKey($key) 
            ? ($this->usernamePrefix . Keys::keyName($key)) 
            : null;
		if ($username) {
            Keys::storeKey($key, $this->persistKeys);
			if ($setStatus) {
                Auth::setStatus(true);
            }
			return true;
		}
		return false;
	}

	public function logout()
	{
        if (key_exists('keys', $_SESSION)) {
            unset($_SESSION['keys']);
        }
		return true;
	}

	public function getPublicName()
	{
		return $this->$pluginName;
	}

	public function getProvidedUsername()
	{
		return 
			key_exists('username', $_SESSION)
			? ($this->usernamePrefix . trim($_SESSION['username']))
			: null;
	}

	public function setUsername($username)
	{
		$_SESSION['username'] = $username;
	}

	// public function getProvidedPassword()
	// {
	// 	return '';
	// }

	public function getUserInfo($what = null){
		if (is_null($what)) {
			return array(
				Auth::PLUGIN_INFO_USERNAME => $this->getProvidedUsername(),
				Auth::PLUGIN_INFO_REALM => 'key'
			);
		} else {
			switch($what){
				case Auth::PLUGIN_INFO_USERNAME :
					return $this->getProvidedUsername();
				case Auth::PLUGIN_INFO_REALM :
					return 'key';
				default:
					return null;
			}
		}
	}
}