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
	protected $_config = NULL;
	protected $_bootstrap = NULL;
	protected $_bootstrapConfig = array();
	protected $_router = NULL;
	protected $_current = NULL;
	protected $_routerClass = 'Saf_Router';
	protected $_resources = array();

	/**
	 * @param string $applicationName
	 * @param string $configEnvironment
	 * @param bool $autoStart
	 * @return Saf_Application
	 * @throws Exception
	 */
	public static function load($applicationName = 'Application', $configEnvironment = NULL, $autoStart = FALSE)
	{
		if (!Kickstart::isValidNameToken($applicationName)) {
			throw new Exception('Invalid application name specified.');
		}
		Kickstart::go();
		if (is_null($applicationName)) {
			$applicationName = 'Application';
		}
		$applicationFile = APPLICATION_PATH . "/{$applicationName}.php";
		if (!file_exists($applicationFile)) {
			throw new Exception('Unable to find the application.');
		}
		if (!is_readable($applicationFile)) {
			throw new Exception('Unable to access the application.');
		}
		require_once($applicationFile);
		$application = new $applicationName($configEnvironment, APPLICATION_CONFIG, $autoStart);
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
				!is_null($configFilePath) ? $configFilePath : APPLICATION_CONFIG,
				!is_null($configEnvironment) ? $configEnvironment : APPLICATION_ENV
			);
		} catch (Saf_Config_Exception_InvalidEnv $e) {
			Saf_Debug::out("Requested configutation section \"{$configEnvironment}\" not found, trying default...");
			$this->_config = Saf_Config::load($configFilePath, 'default');
		}
		$this->_applyConfig();
		if(
		 	'install' == APPLICATION_STATUS
		 	&& !array_key_exists('install', $_REQUEST)
		) {
			//#TODO how to access install
			$e = new Exception('This application is Install Mode and currently unavailable.');
			Saf_Status::set(Saf_Status::STATUS_503_UNAVAILABLE);
			Kickstart::exceptionDisplay($e);
		}
		if('down' == APPLICATION_STATUS) {
			$e = new Exception('This application is in Maintenance Mode and currently unavailable.');
			Saf_Status::set(Saf_Status::STATUS_503_UNAVAILABLE);
			Kickstart::exceptionDisplay($e);
		}
		if ('online' != APPLICATION_STATUS) {
			$e = new Exception('This application is an unrecognized mode: ' . APPLICATION_STATUS . ' and currently unavailable.');
			Saf_Status::set(Saf_Status::STATUS_503_UNAVAILABLE);
			Kickstart::exceptionDisplay($e);			
		}
		if ($autoStart) {
			$this->start();
		}
	}
	
	protected function _applyConfig()
	{
		//#TODO #2.0.0 loop through the tags instead
		$autoLoad = $this->_config->getOptional('autoLoad', FALSE);
		$debugMode = $this->_config->getOptional('debug:mode', Saf_Debug::DEBUG_MODE_OFF);
		$errorMode = $this->_config->getOptional('error:mode', Saf_Debug::ERROR_MODE_DEFAULT);
		Saf_Debug::init($debugMode, $errorMode, FALSE);
		if ($autoLoad) {
			$autoLoadTakeover =
			array_key_exists('takeover', $autoLoad)
			&& Saf_Filter_Truthy::filter($autoLoad['takeover']);
			Kickstart::initializeAutoloader($autoLoad);
			if (array_key_exists('loader', $autoLoad)) {
				$loaders = Saf_Config::autoGroup($autoLoad['loader']);
				foreach($loaders as $loader) {
					$loaderParts = explode(':', Saf_Filter_ConfigString($loader),2);
					!array_key_exists(1, $loaderParts)
					? Kickstart::addAutoloader($loaderParts[0])
					: Kickstart::addAutoloader($loaderParts[0],$loaderParts[1]);
				}
			}
			if (array_key_exists('library', $autoLoad)) {
				$libraries = Saf_Config::autoGroup($autoLoad['library']);
				foreach($libraries as $library) {
					$libParts = explode(':', Saf_Filter_ConfigString($library),2);
					!array_key_exists(1, $libParts)
					? Kickstart::addLibrary($libParts[0])
					: Kickstart::addLibrary(array($libParts[0], $libParts[1]));
				}
			}
			if (array_key_exists('special', $autoLoad)) {
				$specialLoaders = Saf_Config::autoGroup($autoLoad['special']);
				foreach($specialLoaders as $special) {
					$specialParts = explode(':', Saf_Filter_ConfigString($special),2);
					!array_key_exists(1, $specialParts)
					? Kickstart::addLibrary(array($specialParts[0]))
					: Kickstart::addLibrary(array($specialParts[0], $specialParts[1]));
				}
			}
		}
		$this->_bootstrapConfig = $this->_config->getOptional('bootstrap', NULL);
		if ($this->_config->has('resources')) {
			$resources = Saf_Array::coerce($this->_config->get('resources:+'), Saf_Array::MODE_TRUNCATE);
			foreach($resources as $pluginName => $pluginConfig) {
				$this->provision($pluginName, $pluginConfig);
			}
		}
	}

	public function start($bootstrapType = NULL, $request = NULL)
	{
		return $this->bootstrap($bootstrapType)->run($request);
		//#NOTE ^ this is bootstrap->run, not application->run
	}
	
	public function bootstrap($type = NULL)
	{
		if (!is_null($type) && !Kickstart::isValidNameToken($type)) {
			throw new Exception('Invalid bootstrap specified.');
		}
		if (is_null($type) && !is_null($this->_bootstrap)) {
			return $this->_bootstrap;
		} else if (!is_null($this->_bootstrap)) {
			$bootstrapClass = get_class($this->_bootstrap);
			$ucfType = ucfirst($type);
			if ("Saf_Bootstrap_{$ucfType}" == $bootstrapClass) {
				return $this->_bootstrap;
			}
		} else {
			$type = (
				defined('APPLICATION_PROTOCOL')
				? (
					APPLICATION_PROTOCOL != 'commandline'
					? 'Http'
					: 'Commandline'
				) : 'Http'
			);
		}
		$ucfType = ucfirst($type);
		try {
			$bootstrapClass = "Saf_Bootstrap_{$type}";
			if (
				!Kickstart::isAutoloading() 
				&& !class_exists($bootstrapClass)
			) {
				Kickstart::autoload($bootstrapClass);
			}
			$this->_bootstrap = new $bootstrapClass($this, $this->_bootstrapConfig);
		} catch (Exception $e) {
			if (!class_exists($bootstrapClass, FALSE)){
			//!in_array($bootstrapClass, get_declared_classes())) { //also seems to fail
			//#TODO #RAINYDAY for some reason if spl_autoload throws an exception for a class, 
			//PHPseems to refuse try again, or even load the class manually...
				if (Kickstart::isAutoloading()) {
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

	protected function _preRun()
	{
		if (is_null($this->_router)) {
			$this->_router = new $this->_routerClass();
		}
		if (is_null($this->_model)) {
			$this->_model = array();
		}
	}
	
	abstract public function run(&$request = NULL, &$response = NULL);
	
	public function shareRoute($targetApplicaiton)
	{
		$targetApplicaiton->setRoute($this->_router, $this->_current);
	}
	
	public function setRoute($router, $currentRoute)
	{
		$this->_router = $router;
		$this->_current = $currentRoute;
	}

	public function provision($pluginName, $pluginConfig)
	{
		$this->_resources[$pluginName] = $pluginConfig; //#TODO #1.0.0 implement
	}

}
