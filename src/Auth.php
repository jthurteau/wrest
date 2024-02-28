<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for authentication
 */

namespace Saf;

use Saf\Auth\Plugin\Local;
use Psr\Http\Message\ServerRequestInterface; #TODO currently still uses bare access
use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
use Saf\Utils\Filter\Truthy;
use Saf\Auto;
use Saf\Hash;
use Saf\Session;
use Saf\Util\Ground;
use Saf\Keys; //#TODO improve this integration (maybe switch to direct Plugin\Key dependency)
use Saf\Layout; //#TODO clean up this integration
use Saf\Audit; //#TODO clean up this integration
#TODO split out plugin functionality

class Auth
{
    public const PLUGIN_INFO_USERNAME = 'username';
    public const PLUGIN_INFO_USERID = 'userid';
    public const PLUGIN_INFO_REALM = 'realm';
    public const PLUGIN_INFO_FIRSTNAME = 'firstName';
    public const PLUGIN_INFO_LASTNAME = 'lastName';
    public const PLUGIN_INFO_PREFNAME = 'preferredName';
    public const PLUGIN_INFO_FULLNAME = 'fullName';
    public const PLUGIN_INFO_EMAIL = 'email';

    public const REALM_FIELD = 'loginRealm';

    protected const STATUS_CANNOT_CREATE_USER = '009';

    protected static $loadedPlugins = [];
    protected static $defaultPlugins = [];
    protected static $defaultPluginName = '';
    protected static $initialized = false;
    protected static $classMap = [];
    protected static $autocreate = false;
    protected static $allowGuest = false;
    protected static $autoKey = true;
    protected static $authenticated = false;
    protected static $credentialMissmatch = false;
    protected static $activePlugin = null;
    protected static $userObject = null;
    protected static $errorMessages = [];
    protected static $loadedConfig = null;
    protected static $supportsInternal = false;
    protected static $postLoginHooks = [];
    protected static $simKeys = [];
    
    public const USER_AUTODETECT = null;
    public const MODE_SIMULATED = 1;
    public const SIMULATED_AUTH_CONSTANT = '\\Saf\\AUTH_SIMULATED_USER';
    public const SIMULATED_AUTH_LOCK_KEY = 'simulated_login_lock';
    public const SIMULATED_AUTH_USER_KEY = 'simulated_user';
    public const SIMULATED_AUTH_KEY_PARAM = 'simulated_login_key';
    //const MODE_SESSIONLESS = 2; //#TODO
    //const MODE_KEYONLY = 4; //#TODO

    public function __invoke(ContainerInterface $container, string $name, callable $callback) : Object
    {
		$containerConfig = Container::getOptional($container, 'config', []);
        $containerConfig =& Ground::ground($containerConfig);
        $authConfig = Hash::extractIfArray('auth', $containerConfig, []);
        self::init($authConfig);
        $keys = Hash::extractIfArray('keys', $containerConfig, []);
        if ($keys) {
            Keys::setServiceKeys($keys);
        }
        self::autodetect();
        if (
            self::$autoKey
            && (
                in_array('Key', self::$loadedPlugins)
                || in_array('\\Saf\\Auth\\Plugin\\Key', self::$loadedPlugins)
            )
            && (
                self::$activePlugin != self::getPlugin('Key')
            )
        ) {
            self::getPlugin('Key')->auth(false);
        }
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
            if (key_exists('simulatedAuthKeys', $config)) {
                self::$simKeys = $config['simulatedAuthKeys'];
            }
        }
        $plugins =
            key_exists('plugin', $config)
            ? (
                Hash::isNumericArray($config['plugin'])
                ? $config['plugin']
                : [$config['plugin']]
            ) : [];
        self::$autocreate = Hash::extract('autocreateUsers', $config, false);
        self::$allowGuest = Hash::extract('allowGuest', $config, false);
        $firstPass = true;
        foreach($plugins as $pluginConfig) {
            if (
                is_array($pluginConfig)
                && key_exists('name', $pluginConfig)
            ) {
                $pluginName = $pluginConfig['name'];
                $pluginConfig = $pluginConfig;
            } else {
                $pluginName = $pluginConfig ? trim($pluginConfig) : $pluginConfig;
                $pluginConfig = [];
            }
            if ($firstPass) {
                self::$defaultPlugins[] = $pluginName;
                self::$defaultPluginName = $pluginName;
                $firstPass = false;
            } else if (key_exists('default', $pluginConfig) && $pluginConfig['default']) {
                self::$defaultPlugins[] = $pluginName;
            }
            if (!$pluginName) {
                //#TODO add warning to debug
            }
            $pluginName && self::registerPlugin($pluginName, $pluginConfig);
        }
        $hooks = key_exists('postProcess', $config)
            ? (
                Hash::isNumericArray($config['postProcess'])
                ? $config['postProcess']
                : [$config['postProcess']]
            ) : [];
        foreach($hooks as $hook) {
            self::$postLoginHooks[$hook] = (
                strpos($hook,'Hook\\') !== false
                ? $hook
                : 'Hook\\' . $hook
            );
        }
        self::$initialized = true;
    }

    public static function autodetect($mode = null)
    {
        if (!self::$initialized) {
            throw new \Exception('Attempting to authenticate before initialization.');
        }
        Session::on();
        $originalActivePlugin = self::$activePlugin;
        $simulatedAuthConst = self::SIMULATED_AUTH_CONSTANT;
        //throw new \Saf\Exception\Inspectable($mode,$originalActivePlugin,self::$supportsInternal,self::$activePlugin);
        if (self::$supportsInternal && self::$activePlugin) {
            $simulatedLockOn =
                isset($_SESSION)
                && key_exists(self::SIMULATED_AUTH_LOCK_KEY, $_SESSION);
            $currentSimulatedUser =
                key_exists(self::SIMULATED_AUTH_USER_KEY, $_SESSION)
                ? $_SESSION[self::SIMULATED_AUTH_USER_KEY]
                : '';
            if ($simulatedLockOn) {
                $mode = self::MODE_SIMULATED;
                defined($simulatedAuthConst) || define($simulatedAuthConst, $currentSimulatedUser);
            }
            $userToLogin =
                $mode === self::MODE_SIMULATED
                    && defined($simulatedAuthConst)
                    && constant($simulatedAuthConst)
                ? constant($simulatedAuthConst) //\Saf\AUTH_SIMULATED_USER
                : self::USER_AUTODETECT;
            // throw new \Saf\Exception\Inspectable(
            //     $userToLogin, self::$activePlugin, constant($simulatedAuthConst), self::MODE_SIMULATED, $mode
            // );
            if (
                self::login($userToLogin) && self::$activePlugin->auth()
            ){
                if (
                    self::$authenticated
                    && $mode == self::MODE_SIMULATED
                ) {
                    $_SESSION[self::SIMULATED_AUTH_LOCK_KEY] = true;
                    $_SESSION[self::SIMULATED_AUTH_USER_KEY] = constant($simulatedAuthConst);
                }
                return self::$authenticated;
            }
        }
        // self::init();
        $plugins = (
            !key_exists(self::REALM_FIELD, $_GET)
                || !in_array(trim($_GET[self::REALM_FIELD]),self::$loadedPlugins)
            ? self::$defaultPlugins
            : array(trim($_GET[self::REALM_FIELD]))
        );
        foreach($plugins as $pluginName){
            try {
                $plugin = self::getPlugin($pluginName);
                self::$activePlugin = $plugin;
                if($plugin->auth()) {
                    return self::login($plugin->getProvidedUsername());
                } else {
                    self::$activePlugin = null;
                }
            } catch (\Exception $e) {
                self::$activePlugin = null;
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

    public static function authenticate(ServerRequestInterface $request) : ?string
    {
        return self::getPluginProvidedUsername();
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
        unset($_SESSION[self::SIMULATED_AUTH_LOCK_KEY]);
        self::logoutExternally();
        self::logoutLocally();
    }

    public static function logoutExternally($realm = '*')
    {
        if('*' != $realm) {
            if (in_array(trim($realm),self::$loadedPlugins)) {
                $realms = [$realm];
            }
            else {
                $realms = [];
            }
        } else {
            $realms = self::$loadedPlugins;
        }
        foreach($realms as $pluginName){
            $plugin = self::getPlugin($pluginName);
            $plugin->logout();
        }
    }

    private static function getPlugin($pluginName = null)
    {
        if (is_null($pluginName) || '' == $pluginName) {
            $pluginName = self::$defaultPlugins[0];
        } #TODO handle prepending \Saf\Auth\Plugin in case the class is added fully qualified
        $pluginClass =
            array_key_exists($pluginName, self::$classMap)
            ? self::$classMap[$pluginName]
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
            return self::getPlugin($pluginName)->getExternalLogoutUrl();
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
        self::$authenticated = $success;
        self::$userObject = $userObject;
        self::$activePlugin->setPluginStatus($success, $errorCode);
        if ('' != $errorCode) {
            throw new \Exception("Login Error, error code: {$errorCode}");
        }
    }

    public static function createUser($user, $userInfo)
    {
        if(!self::willAutocreateUsers()) {
            self::setStatus(false, null, self::STATUS_CANNOT_CREATE_USER);
            return false;
        }
        if(!key_exists('username', $userInfo)) {
            throw new \Exception('Must specify username to create user.');
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

    public static function allowGuest()
    {
        return self::$allowGuest;
    }

    public static function allowSimulatedLogin(string $username, ServerRequestInterface $request)
    {
        $query = $request->getQueryParams();
        $simKey = 
            key_exists(self::SIMULATED_AUTH_KEY_PARAM, $query) 
            ? $query[self::SIMULATED_AUTH_KEY_PARAM] 
            : null;
        if ($simKey && in_array($simKey, self::$simKeys)) {
            foreach(self::$simKeys as $keyIndex => $key) {
                if (
                    $key === $simKey 
                    && (is_numeric($key) || $key === $username)
                ) {
                    return $username;
                }
            }
            return $username;
        }
        return false;
    }

    public static function simulatedLoginEnabled()
    {
        return count(self::$simKeys) > 0;
    }

    protected static function login($username = self::USER_AUTODETECT)
    {
        $wasLoggedIn = self::isInternallyLoggedIn();
        if (
            $username !== self::USER_AUTODETECT
            && '' != trim($username)
        ){
            $_SESSION['username'] = $username;
        } else if (
            $username === self::USER_AUTODETECT
            && key_exists('username', $_SESSION)
            && '' != $_SESSION['username']
        ) {
            $username = $_SESSION['username'];
        }  else {
            return false;
        }
        if (self::$activePlugin) {
            self::$activePlugin->postLogin();
        }
        if (!$wasLoggedIn && self::isInternallyLoggedIn()) {
            foreach(self::$postLoginHooks as $hookName) {
                try {
                    $hookName::trigger(['username' => $username]);
                } catch (\Exception $e) {
                    Audit::add('problem', $e->getMessage());
                }
            }
        }
        return true;
    }

    protected static function registerPlugin($pluginName, $pluginConfig = null){
        if (!in_array($pluginName, self::$loadedPlugins)) {
            self::$loadedPlugins[] = $pluginName;
            $className = 'Saf\\Auth\\Plugin\\' . $pluginName;
            $internalPluginPath = __DIR__ . '/Auth/Plugin/' . Auto::classNameToPath($pluginName) . '.php';
            if (!file_exists($internalPluginPath)) {
                $className = $pluginName;
            }
            if (!class_exists($className)) {
                throw new \Exception("Failed to load configured plugin {$pluginName}");
            }
            $rootClassName = Auto::rootClass($className);
            if ($pluginConfig) {
                self::$classMap[$pluginName] = array($rootClassName => $pluginConfig);
            } else {
                self::$classMap[$pluginName] = $rootClassName;
            }
        }
    }
}