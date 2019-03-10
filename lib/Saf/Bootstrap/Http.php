<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Bootstrap handler for HTTP applications

*******************************************************************************/
require_once(LIBRARY_PATH . '/Saf/Bootstrap.php');
require_once(LIBRARY_PATH . '/Saf/Array.php');

class Saf_Bootstrap_Http extends Saf_Bootstrap
{
	
	public function __construct($application, $config = array())
	{
		$this->_config = 
			array_key_exists('http', $config)
			? $config['http']
			: (
				array_key_exists('default', $config)
				? $config['default']
				: array()		
			);
		parent::__construct($application, $config);
	}

	public function run(&$request = NULL)
	{
		//$this->_correctMagicQuotes();
		if (is_null($request)) {
			$request = array(
				'uri' => $_SERVER['REQUEST_URI'],
				'method' => $_SERVER['REQUEST_METHOD'],
				'format' => self::_detectFormat(),
				'get' => $_GET,
				'post' => $_POST,
				'session' => isset($_SESSION) ? $_SESSION : NULL,
				'files' => $_FILES,
				'internal' => array(),
				'lang' => self::_detectLang()				
			);
		} else {
			
		}
		return parent::run($request);
	}
	
	protected function _detectLang()
	{
		return 'en';
	}
	
	protected function _detectFormat()
	{
		return 'html';
	}
	
	protected function _initUrl()
	{
		$port = (
				(!APPLICATION_SSL && $_SERVER['SERVER_PORT'] == STANDARD_PORT)
				|| (APPLICATION_SSL && $_SERVER['SERVER_PORT'] == SSL_PORT)
				? ''
				: ':' . $_SERVER['SERVER_PORT']
		);
		$url = APPLICATION_PROTOCOL . '://' . $_SERVER['SERVER_NAME'] . $port . APPLICATION_BASE_URL;
		Saf_Registry::set('siteUrl', $url);
		Saf_Registry::set('baseUrl', APPLICATION_BASE_URL);
	}

	
	/*	protected function _initRequest()
	 {
	$zcf = Zend_Controller_Front::getInstance();
	$request = new Zend_Controller_Request_Http();
	$zcf->setRequest($request);
	}
	*/
	
	protected function _initTheRest()
	{
		$this->_initDebug();
	}
 
	/*protected function _safeInitView(Zend_Controller_Request_Abstract $request)
	 {
	$module= $request->getModuleName();
	$dirs	= $this->getFrontController()->getControllerDirectory();
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
	/*		$this->_safeInitView($request); //#TODO #2.0.0 include logic for other response formats
	
	*/
	/*protected function _safeInitView(Zend_Controller_Request_Abstract $request)
	 {
	$module= $request->getModuleName();
	$dirs	= $this->getFrontController()->getControllerDirectory();
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
	/*		$this->_safeInitView($request); //#TODO #2.0.0 include logic for other response formats
	
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
