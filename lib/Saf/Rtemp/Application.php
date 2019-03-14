<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base class for application loading

*******************************************************************************/
require_once(LIBRARY_PATH . '/Saf/Kickstart.php');
use Saf\Kickstart as Kickstart;
require_once(LIBRARY_PATH . '/Saf/Config.php');
require_once(LIBRARY_PATH . '/Saf/Debug.php');
require_once(LIBRARY_PATH . '/Saf/Status.php');
require_once(LIBRARY_PATH . '/Saf/Array.php');
require_once(LIBRARY_PATH . '/Saf/Filter/Truthy.php');
require_once(LIBRARY_PATH . '/Saf/Filter/ConfigString.php');
require_once(LIBRARY_PATH . '/Saf/Application/Mvc.php');

abstract class Saf_Application
{

    protected $_autoLoad = FALSE;
    protected $_debugMode = NULL;
    protected $_errorMode = NULL;
    protected $_config = NULL;
    protected $_bootstrap = NULL;
    protected $_route = NULL;
    protected $_bootstrapConfig = array();
    protected $_debugHandled = FALSE;

    public static function load($applicationName = 'Application', $configEnvironment = NULL, $configFilePath = NULL, $autoStart = FALSE)
    {
    	Kickstart::go();
    	if (is_null($applicationName)) {
    		$applicationName = 'Application';
    	}
    	$ext = '.php';
    	$applicationClass = ''; //#TODO #2.0.0 move into kickstart and normalize names/paths
    	$applicationFile =
    		strpos($applicationName, '/') === 0
    		? "{$applicationName}{$ext}"
    		: APPLICATION_PATH . "/{$applicationName}{$ext}";
    	if (!Kickstart::fileExistsInPath($applicationFile)) {
    		throw new Exception('Unable to find application.');
    	}
    	require_once($applicationFile);
    	$application = new $applicationName($configEnvironment, $configFilePath, $autoStart);
    	if (!$application || !method_exists($application, 'start')) {
    		throw new Exception('Unable to load application.');
    	}
    	return $application;
    }
    
    public function __construct($configEnvironment = NULL, $configFilePath = NULL, $autoStart = FALSE)
    {
    	Kickstart::go();
    	try {
        	$this->_config = Saf_Config::load(
        		!is_null($configFilePath) ? $configFilePath : APPLICATION_CONF,
        		!is_null($configEnvironment) ? $configEnvironment : APPLICATION_ENV
        	);
        } catch (Saf_Config_Exception_InvalidEnv $e) {
        	Saf_Debug::out("Requested configutation section \"{$configEnvironment}\" not found, trying default...");
        	$this->_config = Saf_Config::load($configFilePath, 'default');
        }
        $this->_autoLoad = $this->_config->getOptional('autoLoad', FALSE);
        $this->_debugMode = $this->_config->getOptional('debug:mode', Saf_Debug::DEBUG_MODE_OFF);
        $this->_errorMode = $this->_config->getOptional('error:mode', Saf_Debug::ERROR_MODE_INTERNAL);
        Saf_Debug::init($this->_debugMode, $this->_errorMode, FALSE);
        Kickstart::initializeAutoloader($this->_autoLoad);
        //#TODO #2.0.0 bootstrap config
    //#TODO #2.0.0 init plugins
    // loggingf
    // db
    // etc.
        if ($this->_config->has('plugins')) {
        	foreach($this->_config->get('plugins:+') as $pluginName => $pluginConfig) {
        		
        	}
            print_r(array('plugins',gettype($this->_config->get('plugins:+')), $this->_config->get('plugins:+')));
            print_r(array('plugins2',gettype($this->_config->get('plugins2:+')), $this->_config->get('plugins2')));
        }
        /*
         foreach() {
         
         }
         * 
         if(
         		'install' == APPLICATION_STATUS
         		&& !array_key_exists('install', $_REQUEST)
         ) {
        $e = new Exception('This application is Install Mode and currently unavailable.');
        Saf_Status::set(Saf_Status::STATUS_503_UNAVAILABLE);
        Kickstart::exceptionDisplay($e);
        }
        
        if('down' == APPLICATION_STATUS) {
        $e = new Exception('This application is in Maintenance Mode and currently unavailable.');
        Saf_Status::set(Saf_Status::STATUS_503_UNAVAILABLE);
        Kickstart::exceptionDisplay($e);
        }
        */
        if ($autoStart) {
        	$this->start($autoStart);
        }
    }

    public function bootstrap($type = NULL)
    {
        if ($type === TRUE || $type === FALSE) {
        	$type = NULL; //#TODO #2.0.0 allow valid class names and bootstrapObjects
        }
    	if (is_null($type) && !is_null($this->_bootstrap)) {
        	return $this->_bootstrap;
    	} else if (!is_null($this->_bootstrap)) {
        	$bootstrapClass = get_class($this->_bootstrap);
        	if ("Saf_Bootstrap_{$type}" == $bootstrapClass) {
        		return $this->_bootstrap;
        	}
        }
    	if (is_null($type)) {
        	$type = (
        		defined('APPLICATION_PROTOCOL')
        		? (
        			APPLICATION_PROTOCOL != 'commandline'
        			? 'Http'
        			: 'Commandline'
        		) : 'Http'
        	);
        }
    	try {
            $bootstrapClass = "Saf_Bootstrap_{$type}";
            if (!$this->_autoLoad && !class_exists($bootstrapClass)) {
                Kickstart::autoload($bootstrapClass);
            }
            $this->_bootstrap = new $bootstrapClass($this, $this->_bootstrapConfig);
        } catch (Exception $e) {
            if (!class_exists($bootstrapClass, FALSE)){
            //!in_array($bootstrapClass, get_declared_classes())) { //also seems to fail
            //#TODO #RAINYDAY for some reason if spl_autoload throws an exception for a class, 
            //PHPseems to refuse try again, or even load the class manually...
                if ($this->_autoLoad) {
                    throw new Exception('Unable to load the requested Bootstrap'
                        . (Saf_Debug::isEnabled() ? " ({$bootstrapClass}) " : '') 
                        . '. Autoloading is enabled, but unable to find the bootstrap.', 0, $e);
                } else {
                    throw new Exception('Unable to load the requested Bootstrap'
                        . (Saf_Debug::isEnabled() ? " ({$bootstrapClass}) " : '') 
                        . '. Manually require this class, or enable autoloading.', 0, $e);
                }
            } else {
                throw($e);
            }
        }
        return $this->_bootstrap;
    }

	public function start($bootstrapType = NULL, $request = NULL)
    {
    	return $this->bootstrap($bootstrapType)->run($request); 
    }
    
    abstract public function run($request = NULL);
    
    public function setRoute($route)
    {
    	$this->_route = $route;
    }

}
