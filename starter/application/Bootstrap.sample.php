<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Bootstrap extends Saf_Bootstrap_Zend //#NOTE ZF 1.12 compatible
//#NOTE kickstart autodetect mode peeks at this file with a dumb regex, 
//#NOTE so make sure the first class extends is the real one...
{
	protected $_cache = NULL;
	protected $_config = NULL;
	protected $_language = NULL;
	
	/**
	 * 
	 * @throws Exception
	 */
	public function run()
	{
		//$this->_initLanguage(); #TODO #1.5.0 no longer needed until we get to inits?
		Saf_Layout_Location::setCrumbsFromConfig(Zend_Registry::get('config')->get('defaultBreadCrumbs'));
		Saf_Layout::autoFormat();
		$this->_correctMagicQuotes();
		$dbPlugin = $this->getPluginResource('db');
		if($dbPlugin){
			$db = $dbPlugin->getDbAdapter();
			try{
				$db->getConnection();
			} catch (Exception $e){
				throw new Exception('Database Connection Failed.');
			}
		}
		$view = new Zend_View(); //#TODO #2.0.0 may need to revisit this when modules are added.
		$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
		$viewRenderer->setView($view)
			->setViewSuffix('php');
		$view->addHelperPath(APPLICATION_PATH . '/views/helpers/','Local_View_Helper_');
		Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);		
		$front = Zend_Controller_Front::getInstance();
		$front->registerPlugin(new Saf_Controller_Front_Plugin_RouteCleaner());
		$front->registerPlugin(new Saf_Controller_Front_Plugin_RouteResourceStack());
		$front->registerPlugin(new Saf_Controller_Front_Plugin_RouteRules());
		$front->registerPlugin(new Saf_Controller_Front_Plugin_RestDetection());
		try {
			parent::run();
		} catch (Saf_Exception_Redirect $e) {
			self::_handleRedirectException($e);
		}
	}
	
	protected static function _handleRedirectException($e)
	{ //#TODO #2.0.0 see if we really need this, ideally not otherwise refactor with Error_Controller
		$style = $e->isKept()
		? '301 Moved Permanently'
		: (
				TRUE //#TODO #2.0.0 figure out when 302 is needed for old agents...
				? '303 See Other'
				: '302 Found'
		);
		$siteUrl = Zend_Registry::get('siteUrl'); //#TODO #2.0.0 detect if this isn't ready yet and handle
		$subUrl = $e->getMessage();
		$url =
		strpos($subUrl, '://') !== FALSE
		? $subUrl
		: $siteUrl . $subUrl;
		$version = '0'; //#TODO #2.0.0 figure out when 303 is being used...
		if (Saf_Debug::isEnabled()) {
			Saf_Debug::dieSafe("Debug Mode Intercepting redirect to <a href=\"{$url}\">{$url}</a>");
		} else {
			if(!headers_sent()) {
				header("Location: {$url}");
				header("HTTP/1.{$version} {$style}");
			}
			Saf_Debug::dieSafe("Redirecting. Please continue at <a href=\"{$url}\">{$url}</a>");
		}
	}
	
	//#NOTE ZF seems to care about the order of these init declarations!
	protected function _initAutoload() //#TODO #2.0.0 patch this in better
	{
		require_once 'Zend/Loader/Autoloader.php';
		$loader = Zend_Loader_Autoloader::getInstance();
		$loader->setFallbackAutoloader(TRUE);
	}
	
	//#NOTE ZF seems to care about the order of these init declarations!
	protected function _initCache()
	{
		
	}
	
	//#NOTE ZF seems to care about the order of these init declarations!
	protected function _initConfig()
	{
		if (Zend_Registry::isRegistered('cache')) {
			$this->_cache = Zend_Registry::get('cache');
			$this->_config = $cache->load('configObject');
		}
		if (is_null($this->_config)) {
			$this->_config = new Zend_Config_Xml(APPLICATION_PATH . '/configs/config.xml', APPLICATION_ENV, true);

			if (is_file(APPLICATION_PATH . '/../overrides/config/config.xml')) {
				$configOverride = new Zend_Config_Xml(APPLICATION_PATH . '/../overrides/config/config.xml', APPLICATION_ENV);
				$this->_config->_merge($this->_config, $configOverride);
			}
			if ($this->_cache) {
				$this->_cache->save($config, 'configObject');
			}
		}
		Zend_Registry::set('config', $this->_config);
	}
	
	protected function _initDebug()
	{
		session_start();
		$debugConfig = 
			$this->hasOption('debug') 
			? $this->getOption('debug') 
			: array(
				'mode' => Saf_Debug::DEBUG_MODE_AUTO,
				'error' => Saf_Debug::ERROR_MODE_DEFAULT
			);
		if (is_array($debugConfig)) {
			$debugMode = 
				array_key_exists('mode', $debugConfig) 
				? $debugConfig['mode'] 
				: Saf_Debug::DEBUG_MODE_AUTO;
			$errorMode = 
				array_key_exists('error', $debugConfig) 
				? $debugConfig['error'] 
				: Saf_Debug::ERROR_MODE_DEFAULT;
		} else {
			$debugMode = $debugConfig;
			$errorMode = Saf_Debug::ERROR_MODE_DEFAULT;
		}
		Saf_Debug::init($debugMode, $errorMode, TRUE);
	}
	
	//#NOTE ZF seems to care about the order of these init declarations!
	protected function _initAuth()
	{
		$authConfig = $this->hasOption('auth') ? $this->getOption('auth') : FALSE;
		if ($authConfig) {
			Saf_Auth::init($authConfig);
			Saf_Auth::autodetect();
		}
	}

	//#NOTE ZF seems to care about the order of these init declarations!
/*	protected function _initAcl()
	{
		$aclConfig = $this->_config->get('acl', NULL);
		Saf_Acl::init($aclConfig);
	}
*/
		
	//#NOTE ZF seems to care about the order of these init declarations!
	protected function _initLanguage()
	{
		if ($this->_cache) {
			$this->_language = $this->_cache->load('languageObject');
		}		
		if(!$this->_language){
			$languageCode = $this->getOption('language');
			$languageFolder = APPLICATION_PATH . "/configs/language.{$languageCode}/";
			$languageOverrideFolder = APPLICATION_PATH . "/../overrides/language.{$languageCode}/";
			$languageConfig = null;
			if (is_dir($languageFolder)) {
				$languageConfig = $this->_loadLanguageFolder($languageFolder, $languageConfig);
			}
			if (!$languageConfig){
				throw new Exception('Found no Language Files.');
			}
			if (is_dir($languageOverrideFolder)) {
				$languageConfig = $this->_loadLanguageFolder($languageOverrideFolder, $languageConfig);
			}
			$this->_language = new Saf_Language($languageConfig);
			if ($this->_cache) {
				$this->_cache->save($language, 'languageObject');
			}
		}
		Zend_Registry::set('language', $this->_language);		
	}
	
	protected function _loadLanguageFolder($folder, $config = NULL){
		$failedLoads = array(); //#TODO #2.0.0 log this?
		$languageFiles = scandir($folder);
		foreach ($languageFiles as $file) {
			if ('.' != $file && '..' != $file
					&& !is_dir($folder . $file)
					&& strpos($file,'.') !== 0
					&& strpos($file,'.xml') !== FALSE
			) {
				try {
					$newConfig = new Zend_Config_Xml($folder . $file);
				} catch (Zend_Config_Exception $e){
					$failedLoads[] = $folder . $file;
				}
				if ($config) {
					$this->_merge($languageConfig, $newConfig);
				} else {
					$config = $newConfig;
				}
			}
		}
		return $config;
	}
	
	//#NOTE ZF seems to care about the order of these init declarations!
	protected function _initUrl()
	{
		$front = Zend_Controller_Front::getInstance();
		$port = APPLICATION_SUGGESTED_PORT ? (':' . APPLICATION_SUGGESTED_PORT) : '';
		$url = 
			(APPLICATION_SSL ? (APPLICATION_PROTOCOL . 's') : APPLICATION_PROTOCOL )
			. '://' . $_SERVER['SERVER_NAME'] . $port . APPLICATION_BASE_URL;
		Zend_Registry::set('siteUrl', $url);
		Zend_Registry::set('baseUrl', APPLICATION_BASE_URL);
		$front->setBaseUrl(APPLICATION_BASE_URL);
	}
	
	/*    protected function _initRequest()
	 {
	$zcf = Zend_Controller_Front::getInstance();
	$request = new Zend_Controller_Request_Http();
	$zcf->setRequest($request);
	$request->
	print_r($request);die;
	}
	*/
	
	/*protected function _safeInitView(Zend_Controller_Request_Abstract $request)
	{
		$module  = $request->getModuleName();
		$dirs    = $this->getFrontController()->getControllerDirectory();
		if (empty($module) || !isset($dirs[$module])) {
		$module = $this->getFrontController()->getDispatcher()->getDefaultModule();
		}
		$baseDir = dirname($dirs[$module]) . DIRECTORY_SEPARATOR . 'views';
		if (!file_exists($baseDir) || !is_dir($baseDir)) {
		throw new Zend_Controller_Exception('Missing base view directory ("' . $baseDir . '")');
		}
		$this->view = new Saf_View_Html(array('basePath' => $baseDir));
		$this->view->
	}
	*/

	protected function _correctMagicQuotes()
	{
		if(ini_get('magic_quotes_gpc')){
			foreach($_GET as $getIndex=>$getValue){
				$_GET[$getIndex] = $this->_stripSlashesHandleArrays($getValue);
			}
			foreach($_POST as $postIndex=>$postValue){
				$_POST[$postIndex] = $this->_stripSlashesHandleArrays($postValue);
			}
		}
	}
	 
	protected function _stripSlashesHandleArrays($value)
	{
		if (is_array($value)) {
			$newValue = array();
			foreach($value as $valueIndex=>$valueValue){
				$newValue[$valueIndex] = $this->_stripSlashesHandleArrays($valueValue);
			}
			return $newValue;
		} else {
			return stripslashes($value);
		}
	}
}
