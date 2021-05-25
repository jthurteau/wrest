<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for authentication
 */

namespace Saf;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
use Saf\Hash;
use Saf\Utils\Filter\Truthy;
use Saf\Auto;
use Saf\Auth\Plugin\Local;
use Saf\Layout;
use Saf\Audit;
use Saf\Session;
use Saf\Environment\Define;

class Auth
{
    public const PLUGIN_INFO_USERNAME = 'username';
    public const PLUGIN_INFO_REALM = 'realm';
    public const PLUGIN_INFO_FIRSTNAME = 'firstName';
    public const PLUGIN_INFO_LASTNAME = 'lastName';
    public const PLUGIN_INFO_FULLNAME = 'fullName';
    public const PLUGIN_INFO_EMAIL = 'email';

    protected const STATUS_CANNOT_CREATE_USER = '009';

    protected static $loadedPlugins = [];
    protected static $defaultPlugins = [];
    protected static $defaultPluginName = '';
    protected static $initialized = false;
    protected static $classMap = [];
    protected static $autocreate = false;
    protected static $allowGuest = false;
    protected static $authenticated = false;
    protected static $credentialMissmatch = false;
    protected static $activePlugin = null;
    protected static $userObject = null;
    protected static $errorMessages = [];
    protected static $loadedConfig = null;
    protected static $supportsInternal = false;
    protected static $serviceKeys = [];
    protected static $postLoginHooks = [];
    
    const USER_AUTODETECT = NULL;

    const MODE_SIMULATED = 1;

    public function __invoke(ContainerInterface $container, string $name, callable $callback) : Object
    {
		$containerConfig = Container::getOptional($container, 'config', []);
        $authConfig = Hash::extractIfArray('auth', $containerConfig, []);
        self::init($authConfig);
        // $created = $callback();
        // // $created-> ...
        // return $created;
        return $callback();
    }

    public static function init($config = [])
    {
        if (self::$initialized) {
            return;
        }
        self::$loadedConfig = $config;
        if (
            key_exists('supportsInternal', $config)
            && Truthy::filter($config['supportsInternal'])
        ) {
            self::$activePlugin = new Local();
            self::$supportsInternal = true;
        }

        $plugins =
            key_exists('plugin', $config)
            ? (
                Hash::isNumericArray($config['plugin'])
                ? $config['plugin']
                : [$config['plugin']]
            ) : [];
        self::$autocreate = 
            key_exists('autocreateUsers', $config)
            ? $config['autocreateUsers']
            : false;
        self::$allowGuest = 
            key_exists('allowGuest', $config)
            ? $config['allowGuest']
            : false;
        $firstPass = true;
        foreach($plugins as $pluginConfig) {
            if (
                is_array($pluginConfig)
                && key_exists('name', $pluginConfig)
            ) {
                $pluginName = $pluginConfig['name'];
                $pluginConfig = $pluginConfig;
            } else {
                $pluginName = trim($pluginConfig);
                $pluginConfig = [];
            }
            if ($firstPass) {
                self::$defaultPlugins[] = $pluginName;
                self::$defaultPluginName = $pluginName;
            } else if (key_exists('default', $pluginConfig) && $pluginConfig['default']) {
                self::$defaultPlugins[] = $pluginName;
            }
            if (!in_array($pluginName, self::$loadedPlugins)) {
                self::$loadedPlugins[] = $pluginName;
                $className = '\\Saf\\Auth\\Plugin\\' . $pluginName;
                $internalPluginPath = __DIR__ . '/Plugin/' . Auto::classNameToPath($className) . '.php';
                if (!file_exists($internalPluginPath)) {
                    $className = $pluginName;
                }
                if (!class_exists($className)) {
                    throw new \Exception("Failed to load configured plugin {$pluginName}");
                }
                if ($pluginConfig) {
                    self::$classMap[$pluginName] = array($className => $pluginConfig);
                } else {
                    self::$classMap[$pluginName] = $className;
                }
            }
            $firstPass = false;
        }
        $hooks = key_exists('postProcess', $config)
            ? (
                Hash::isNumericArray($config['postProcess'])
                ? $config['postProcess']
                : array($config['postProcess'])
            ) : array();
        foreach($hooks as $hook) {
            self::$postLoginHooks[$hook] = 'Hook\\' . $hook;
        }
        self::$initialized = true;
    }

    public static function autodetect($mode = null)
    {
        $originalActivePlugin = self::$activePlugin;
        if (self::$supportsInternal && self::$activePlugin) {
            $simulatedLockOn =
                isset($_SESSION)
                && key_exists('simulated_login_lock', $_SESSION);
            $currentSimulatedUser =
                key_exists('simulated_user', $_SESSION)
                ? $_SESSION['simulated_user']
                : '';
            if ($simulatedLockOn) {
                $mode = self::MODE_SIMULATED;
                Define::load( #TODO update this
                    '\\Saf\\AUTH_SIMULATED_USER',
                    $currentSimulatedUser
                );
            }
            $userToLogin =
                $mode == self::MODE_SIMULATED
                    && \Saf\AUTH_SIMULATED_USER
                ? \Saf\AUTH_SIMULATED_USER
                : self::USER_AUTODETECT;
            if (
                self::login($userToLogin) && self::$activePlugin->auth()
            ){
                if (
                    self::$authenticated
                    && $mode == self::MODE_SIMULATED
                ) {
                    $_SESSION['simulated_login_lock'] = true;
                    $_SESSION['simulated_user'] = \Saf\AUTH_SIMULATED_USER;
                }
                return self::$authenticated;
            }
        }
        self::init();
        $plugins = (
            !key_exists('loginRealm', $_GET)
            || !in_array(trim($_GET['loginRealm']),self::$loadedPlugins)
            ? self::$defaultPlugins
            : array(trim($_GET['loginRealm']))
        );
        foreach($plugins as $pluginName){
            try {
                $plugin = self::getPlugin($pluginName);
                self::$activePlugin = $plugin;
                if($plugin->auth()) {
                    return self::_login($plugin->getProvidedUsername(), TRUE);
                } else {
                    self::$activePlugin = NULL;
                }
            } catch (\Exception $e) {
                self::$activePlugin = NULL;
                if (Debug::isEnabled()) {
                    self::$errorMessages[] =
                    "Exception in auth plugin {$pluginName} : " . $e->getMessage();
                }
            }
        }
        if (count(self::$errorMessages) > 0) {
            count(self::$errorMessages) == 1
            ? Layout::setMessage(
                    'loginError', self::$errorMessages[0]
            ) : Layout::setMessage(
                    'loginError', 'Multiple errors: <ul><li>'
                    . implode('</li><li>', self::$errorMessages)
                    . '</li></ul>'
            );
            if (count($plugins) > 0
                    && $plugins[0] == 'Local'
                    && self::$credentialMissmatch
            ) {
                Layout::setMessage( #TODO update this
                        'passwordResetPrompt',
                        '<a href="?cmd=resetPasswordRequest">Forgotten/Lost Password</a>?'
                );
            }
        }
        if (is_null(self::$activePlugin)) {
            self::$activePlugin = $originalActivePlugin;
        }
        return false;
    }

    public static function authenticate(ServerRequestInterface $request) : string
    {
        return '';
    }

    protected static function _login($username = self::USER_AUTODETECT, $logInDb = false)
    {
        $wasLoggedIn = self::isInternallyLoggedIn();
        if (
            $username !== self::USER_AUTODETECT
            && '' != trim($username)
        ){
            $_SESSION['username'] = $username;
        } else if (
            array_key_exists('username', $_SESSION)
            && '' != $_SESSION['username']
        ) {
            $username = $_SESSION['username'];
        }  else {
            return false;
        }
        if (self::$activePlugin) {
            self::$activePlugin->postLogin();
        }
        //#TODO #1.5.0 log if requested
        if (!$wasLoggedIn && self::isInternallyLoggedIn()) {
            foreach(self::$postLoginHooks as $hookName) {
                try {
                    $hookName::trigger(array('username' => $username));
                } catch (\Exception $e) {
                    Audit::add('problem', $e->getMessage());
                }
            }
        }
        return true;
    }

    public static function isLoggedIn()
    {
        self::init();
        return
            self::isExternallyLoggedIn()
            || (
                self::$supportsInternal
                && self::isInternallyLoggedIn()
            );
    }

    public static function isExternallyLoggedIn()
    {
        self::init();
        foreach(self::$loadedPlugins as $pluginName){
            $plugin = self::getPlugin($pluginName);
            if($plugin->isLoggedIn()){
                return true;
            }
        }
        return false;
    }

    public static function isInternallyLoggedIn()
    {
        self::init();
        return key_exists('username', $_SESSION) && $_SESSION['username'];
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
                $realms = [$realm];
            }
            else {
                $realms = [];
            }
        } else {
            $realms = self::$_loadedPlugins;
        }
        foreach($realms as $pluginName){
            $plugin = self::getPlugin($pluginName);
            $plugin->logout();
        }
    }

    private static function getPlugin($pluginName = null)
    {
        if (is_null($pluginName) || '' == $pluginName) {
            $pluginName = self::$_defaultPlugins[0];
        }
        $pluginClass =
            array_key_exists($pluginName, self::$_classMap)
            ? self::$_classMap[$pluginName]
            : null;
        if ($pluginClass) {
            if (is_object($pluginClass)) {
                return $pluginClass;
            } else if (is_array($pluginClass)) {
                reset($pluginClass);
                $pluginClassName = key($pluginClass);
                $pluginConfig = current($pluginClass);
                $plugin = new $pluginClassName($pluginConfig);
            } else {
                $plugin = new $pluginClass();
            }
            return $plugin;
        } else {
            $safeName = htmlentities($pluginName);
            throw new \Exception("No such Plugin: {$safeName}");
        }
    }

    public static function getPluginName($pluginName = null)
    {
        try {
            return self::getPlugin($pluginName)->getPublicName();
        } catch (\Exception $e) {
            $safeName = htmlentities($pluginName);
            throw new \Exception("ERROR: Attempted to get property \"publicName\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
        }
    }

    public static function getExternalLoginUrl($pluginName = null)
    {
        try {
            return self::getPlugin($pluginName)->getExternalLoginUrl();
        } catch (\Exception $e) {
            $safeName = htmlentities($pluginName);
            throw new \Exception("ERROR: Attempted to get property \"externalLoginUrl\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
        }
    }

    public static function getExternalLogoutUrl($pluginName = null)
    {
        try {
            return self::_getPlugin($pluginName)->getExternalLogoutUrl();
        } catch (\Exception $e) {
            $safeName = htmlentities($pluginName);
            throw new \Exception("ERROR: Attempted to get property \"externalLogoutUrl\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
        }
    }

    public static function logoutLocally()
    {
        if (key_exists('username', $_SESSION) ) {
            unset($_SESSION['username']);
        }
        Session::clean();
    }

    public static function pluginIsLoaded($name)
    {
        self::init();
        return in_array($name, self::$loadedPlugins);
    }

    public static function getDefaultPlugin()
    {
        self::init();
        return self::$defaultPlugins[0];
    }

    public static function getDefaultPluginName()
    {
        return self::$defaultPluginName;
    }

    public static function getLoadedPlugins()
    {
        self::init();
        return self::$loadedPlugins;
    }

    public static function willAutocreateUsers()
    {
        return self::$autocreate;
    }

    public static function setStatus($success, $userObject = null, $errorCode = '')
    {
        self::$_authenticated = $success;
        self::$_userObject = $userObject;
        self::$_activePlugin->setPluginStatus($success, $errorCode);
        if ('' != $errorCode) {
            throw new Exception("Login Error, error code: {$errorCode}");
        }
    }

    public static function createUser($user, $userInfo)
    {
        if(!self::willAutocreateUsers()) {
            self::setStatus(false, null, self::STATUS_CANNOT_CREATE_USER);
            return false;
        }
        if(!key_exists('username', $userInfo)) {
            throw new Exception('Must specify username to create user.');
        }
        $username = $userInfo['username'];
        $firstName = key_exists('firstName', $userInfo) ? $userInfo['firstName'] : '';
        $lastName = key_exists('lastName', $userInfo) ? $userInfo['lastName'] : '';
        $email = key_exists('email', $userInfo) ? $userInfo['email'] : '';
        return $user->createUser($username, $firstName, $lastName, $email);
        #TODO handle default attributes
    }

    public static function getPluginUserInfo($what = null)
    {
        return
			self::$activePlugin
			? self::$activePlugin->getUserInfo($what)
			: null;
    }

    public static function getPluginProvidedUsername()
    {
        return
            self::$activePlugin
            ? self::$activePlugin->getProvidedUsername()
            : '';
    }

    public static function setUsername($username)
    {
        if (self::$activePlugin) {
            self::$activePlugin->setUsername($username);
        }
    }

    public static function pluginPromptsForInfo($pluginName = NULL)
    {
        try {
            return self::getPlugin($pluginName)->promptsForInfo();
        } catch (\Exception $e) {
            $safeName = htmlentities($pluginName);
            throw new \Exception("ERROR: Attempted to check property \"promptsForInfo\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
        }
    }

    public static function failPlugin()
    {
        if (self::$activePlugin) {
            self::$activePlugin->fail();
        }
    }

    public static function getConfig()
    {
        return self::$loadedConfig;
    }

    public static function setServiceKeys($keyArray)
    {
        if (!is_array($keyArray)) {
            $keyArray = array($keyArray);
        }
        self::$serviceKeys = $keyArray;
    }

    public static function validKey($keyValue, $name = null)
    {
        $keyValue = trim((string)$keyValue);
        if (!is_null($name)) {
            return key_exists($name,self::$_serviceKeys)
                && trim(self::$serviceKeys[$name]) === $keyValue;
        }
        foreach(self::$serviceKeys as $key) {
            if ($keyValue === trim($key)) {
                return true;
            }
        }
        return false;
    }

    public static function allowGuest()
    {
        return self::$allowGuest;
    }
}