<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for authentication

*******************************************************************************/
class Saf_Auth{

	protected static $_loadedPlugins = array();
	protected static $_defaultPlugins = array();
	protected static $_defaultPluginName = '';
	protected static $_initialized = false;
	protected static $_classMap = array();
	protected static $_autocreate = false;
	protected static $_authenticated = false;
	protected static $_credentialMissmatch = false;
	protected static $_activePlugin = null;
	protected static $_userObject = null;
	protected static $_errorMessages = array();
	
	const PLUGIN_INFO_USERNAME = 'username';
	const PLUGIN_INFO_REALM = 'realm';
	const PLUGIN_INFO_FIRSTNAME = 'firstName';
	const PLUGIN_INFO_LASTNAME = 'lastName';
	const PLUGIN_INFO_FULLNAME = 'fullName';
	const PLUGIN_INFO_EMAIL = 'email';
	
	const MODE_SIMULATED = 1;

	public static function init($config = array())
	{
		if (self::$_initialized) {
			return;
		}
		$plugins = 
			array_key_exists('plugin', $config)
			? (is_array($config['plugin']) ? $config['plugin'] : array($config['plugin'])) 
			: array();
		self::$_autocreate = 
			array_key_exists('autocreateUsers', $config)
			? $config['autocreateUsers']
			: FALSE;
		$firstPass = TRUE;
		foreach($plugins as $pluginConfig) {
			if (is_array($pluginConfig) && array_key_exists('name', $pluginConfig)) {
				$pluginName = $pluginConfig['name'];
				$pluginConfig = array_key_exists('config', $pluginConfig) ? $pluginConfig : array();
			} else {
				$pluginName = trim($pluginConfig);
				$pluginConfig = array();
			}
			if ($firstPass) {
				self::$_defaultPlugins[] = $pluginName;
				self::$_defaultPluginName = $pluginName;
			} else if (array_key_exists('default', $pluginConfig) && $pluginConfig['default']) {
				self::$_defaultPlugins[] = $pluginName;
			}
			if (!in_array($pluginName, self::$_loadedPlugins)) {
				self::$_loadedPlugins[] = $pluginName;
				$className = 'Saf_Auth_Plugin_' . $pluginName;
				$classPath = LIBRARY_PATH . '/'
					. str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
				if (!file_exists($classPath)) {
					$className = $pluginName;
					Saf_Kickstart::autoload($className);
				} else {
					Saf_Kickstart::autoload($className);
				}
				
				self::$_classMap[$pluginName] = $className;
			}
			$firstPass = FALSE;
		}
		self::$_initialized = TRUE;
	}

	public static function autodetect($mode = NULL)
	{
		if (array_key_exists('simulated_login_lock', $_SESSION)) {
			$mode = self::MODE_SIMULATED;
			Saf_Kickstart::defineLoad('APPLICATION_SIMULATED_USER', '');
		}
		if (self::_login(
			$mode == self::MODE_SIMULATED
				&& APPLICATION_SIMULATED_USER
			? APPLICATION_SIMULATED_USER
			: NULL
		)){
			if ($mode == self::MODE_SIMULATED) {
				self::$_activePlugin = new Saf_Auth_Plugin_Local();
				$_SESSION['simulated_login_lock'] = TRUE;
				self::$_activePlugin->auth();
			}	
			return TRUE;
		}
		self::init();
		$plugins = (
				!array_key_exists('loginRealm', $_GET)
				|| !in_array(trim($_GET['loginRealm']),self::$_loadedPlugins)
				? self::$_defaultPlugins
				: array(trim($_GET['loginRealm']))
		);
		foreach($plugins as $pluginName){
			try {
				$pluginClass = self::$_classMap[$pluginName];
				$plugin = new $pluginClass();
				self::$_activePlugin = $plugin;
				if($plugin->auth() && self::$_authenticated) { //#TODO #2.0.0 maybe too over zealous (artifact of old methods)
					return self::_login(self::$_userObject, TRUE);
				} else {
					self::$_activePlugin = NULL;
				}
			} catch (Exception $e) {
				self::$_activePlugin = NULL;
				if (Saf_Debug::isEnabled()) {
					self::$_errorMessages[] =
					"Exception in auth plugin {$pluginName} : " . $e->getMessage();
				}
			}
		}
		if (count(self::$_errorMessages) > 0) {
			count(self::$_errorMessages) == 1 //#TODO #1.1.0 see how this behaves once we patch in shib
			? Saf_Layout::setMessage(
					'loginError', self::$_errorMessages[0]
			) : Saf_Layout::setMessage(
					'loginError', 'Multiple errors: <ul><li>'
					. implode('</li><li>', self::$_errorMessages)
					. '</li></ul>'
			);
			if (count($plugins) > 0
					&& $plugins[0] == 'Local'
					&& self::$_credentialMissmatch
			) {
				Saf_Layout::setMessage(
						'passwordResetPrompt',
						'<a href="?cmd=resetPasswordRequest">Forgotten/Lost Password</a>?'
				);
			}
		}
		//$usersObject = new users();
		//Rd_Registry::set('root:userInterface',$usersObject->initUser('', ''));
		//Account_Rd::init();
		return FALSE;
	}
/*
	public static function loginAs($userObject)
	{
		$u = Rd_Registry::get('root:userInterface');
		if ($u->getDefaultRole() < Account_Rd::LEVEL_ADMIN) {
			throw new Exception('ERROR: Attempting to switch user when not an admin.');
		}
		return self::_login($userObject);
	}
*/
	protected static function _login($username = NULL, $logInDb = FALSE){
		if (!is_null($username)){
			$_SESSION['username'] = $username;
		} else if (
			array_key_exists('username', $_SESSION)
			&& '' != $_SESSION['username']
		) {
			$username = $_SESSION['username'];
		}  else {
			return FALSE;
		}
		//#TODO #1.1.0
		//#NOTE anything else we want to do at login time. i.e. auto create groups?
		return TRUE;
	}

	public static function isLoggedIn()
	{
		self::init();
		return self::isExternallyLoggedIn()
			|| self::isInternallyLoggedIn();
	}

	public static function isExternallyLoggedIn()
	{
		self::init();
		foreach(self::$_loadedPlugins as $pluginName){ //#TODO this should be stored as objects...
			$pluginClass = self::$_classMap[$pluginName];
			$plugin = new $pluginClass();
			if($plugin->isLoggedIn()){
				return true;
			}
		}
		return false;
	}

	public static function isInternallyLoggedIn()
	{
		self::init();
		return array_key_exists('username', $_SESSION) && $_SESSION['username'];
	}

	public static function logout($realm = '*')
	{
		unset($_SESSION['simulated_login_lock']);
		self::logoutExternally();
		self::logoutLocally();
	}

	public static function logoutExternally($realm = '*')
	{
		if('*' != $realm) {
			if (in_array(trim($realm),self::$_loadedPlugins)) {
				$realms = array($realm);
			}
			else {
				$realms = array();
			}
		} else {
			$realms = self::$_loadedPlugins;
		}
		foreach($realms as $pluginName){
			$pluginClass = self::$_classMap[$pluginName];
			$plugin = new $pluginClass();
			$plugin->logout();
		}
	}

	private static function _getPlugin($pluginName = NULL)
	{
		if (is_null($pluginName) || '' == $pluginName) {
			$pluginName = self::$_defaultPlugins[0];
		}
		$pluginClass =
		array_key_exists($pluginName, self::$_classMap)
		? self::$_classMap[$pluginName]
		: NULL;
		if ($pluginClass) {
			$plugin = new $pluginClass();
			return $plugin;
		} else {
			throw new Exception('No such Plugin: ' . htmlentities($pluginName));
		}
	}

	public static function getPluginName($pluginName = NULL)
	{
		try {
			return self::_getPlugin($pluginName)->getPublicName();
		} catch (Exception $e) {
			$safeName = htmlentities($pluginName);
			throw new Exception("ERROR: Attempted to get property \"publicName\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
		}
	}

	public static function getExternalLoginUrl($pluginName = NULL)
	{
		try {
			return self::_getPlugin($pluginName)->getExternalLoginUrl();
		} catch (Exception $e) {
			$safeName = htmlentities($pluginName);
			throw new Exception("ERROR: Attempted to get property \"externalLoginUrl\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
		}
	}

	public static function getExternalLogoutUrl($pluginName = NULL)
	{
		try {
			return self::_getPlugin($pluginName)->getExternalLogoutUrl();
		} catch (Exception $e) {
			$safeName = htmlentities($pluginName);
			throw new Exception("ERROR: Attempted to get property \"externalLogoutUrl\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
		}
	}

	public static function logoutLocally()
	{
		if (array_key_exists('username', $_SESSION) ) {
			unset($_SESSION['username']);
		}
		Saf_Registry::cleanSession();
	}

	public static function pluginIsLoaded($name)
	{
		self::init();
		return in_array($name, self::$_loadedPlugins);
	}

	public static function getDefaultPlugin()
	{
		self::init();
		return self::$_defaultPlugins[0];
	}

	public static function getDefaultPluginName()
	{
		return self::$_defaultPluginName;
	}

	public static function getLoadedPlugins()
	{
		self::init();
		return self::$_loadedPlugins;
	}

	public static function willAutocreateUsers()
	{
		return self::$_autocreate;
	}

	public static function setStatus($success, $userObject = NULL, $errorCode = '')
	{
		self::$_authenticated = $success;
		self::$_userObject = $userObject;
		self::$_activePlugin->setPluginStatus($success, $errorCode);
		if ('' != $errorCode) {
			throw new Exception("Login Error, error code: {$errorCode}");
/*			$message = Rd_CodeLookup::tryMessage('loginFailure', $errorCode);
			self::$_errorMessages[] = (
					'' != trim($message)
					? $message
					: "Code Lookup Error: No data for status code for \"loginFailure:{$code}\"."
					);
			if(intval($errorCode) == 1 || intval($errorCode) == 3) {
				self::$_credentialMissmatch = TRUE;
			}
*/
		}
	}

	public static function createUser($user, $userInfo){
		if(!self::willAutocreateUsers()) {
			self::setStatus(false, NULL, '009');
			return false;
		}
		if(!array_key_exists('username', $userInfo)) {
			throw new Exception('Must specify username to create user.');
		}
		$username = $userInfo['username'];
		$firstName = (
				array_key_exists('firstName', $userInfo)
				? $userInfo['firstName']
				: ''
		);
		$lastName = (
				array_key_exists('lastName', $userInfo)
				? $userInfo['lastName']
				: ''
		);
		$email = (
				array_key_exists('email', $userInfo)
				? $userInfo['email']
				: ''
		);
		return $user->createUser($username, $firstName, $lastName, $email, Account_Rd::LEVEL_STUDENT);
	}

	public static function getPluginUserInfo($what = NULL)
	{
		return
		self::$_activePlugin
		? self::$_activePlugin->getUserInfo($what)
		: NULL;
	}

	public static function getPluginProvidedUsername()
	{
		return
		self::$_activePlugin
		? self::$_activePlugin->getProvidedUsername()
		: '';
	}

	public static function pluginPromptsForInfo($pluginName = NULL)
	{
		try {
			return self::_getPlugin($pluginName)->promptsForInfo();
		} catch (Exception $e) {
			$safeName = htmlentities($pluginName);
			throw new Exception("ERROR: Attempted to check property \"promptsForInfo\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
		}
	}

	public static function failPlugin()
	{
		if (self::$_activePlugin) {
			self::$_activePlugin->fail();
		}
	}
}