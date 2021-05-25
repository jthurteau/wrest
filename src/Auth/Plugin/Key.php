<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Local Auth Plugin Class and handler for key based authentication
 */

namespace Saf\Auth\Plugin;

use Saf\Auth;

class Local extends Base{

	protected $pluginName = 'Application Key Auth';

	public static function auth()
	{
		$username = self::getProvidedUsername();
		if ($username) {
			Saf_Auth::setStatus(true);
			return true;
		}
		return FALSE;
	}

	public static function isLoggedIn()
	{
		return self::getProvidedUsername();
	}

	public static function logout()
	{
		return TRUE;
	}

	public static function getPublicName()
	{
		return 'Internal Auth';
	}

	public static function getExternalLoginUrl()
	{
		return '';
	}

	public static function getExternalLogoutUrl()
	{
		return '';
	}

	public static function getProvidedUsername()
	{
		return 
			array_key_exists('username', $_SESSION)
			? trim($_SESSION['username'])
			: NULL;
	}

	public function setUsername($username)
	{
		$_SESSION['username'] = $username;
	}

	public static function getProvidedPassword()
	{
		return '';
	}

	public static function getUserInfo($what = NULL){
		if (is_null($what)) {
			return array(
				Saf_Auth::PLUGIN_INFO_USERNAME => self::getProvidedUsername(),
				Saf_Auth::PLUGIN_INFO_REALM => 'internal'
			);
		} else {
			switch($what){
				case Saf_Auth::PLUGIN_INFO_USERNAME :
					return self::getProvidedUsername();
				case Saf_Auth::PLUGIN_INFO_REALM :
					return 'apache';
				default:
					return NULL;
			}
		}
	}

	public static function fail()
	{
		if (Saf_Debug::isEnabled()) {
			Saf_Debug::out('Authentication Declined.');
		}
	}

	protected static function _succeed()
	{
		parent::_succeed();
	}

	public static function setPluginStatus($success, $errorCode)
	{
		//only some plugins will need to do this.
	}

	public static function promptsForInfo()
	{
		return TRUE;
	}

	public function postLogin()
	{
		return TRUE;
	}

}