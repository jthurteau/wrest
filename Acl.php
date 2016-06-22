<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility and Base class for access control

*******************************************************************************/
class Saf_Acl{
	
	const ACL_WHO_ANYONE = 7;
	const ACL_WHO_ANYUSER = 6;
	const ACL_WHO_SOMEUSER = 4;
	const ACL_WHO_USER = 2;
	const ACL_WHO_OTHERUSER = 1;
	const ACL_WHO_NOONE = 0;
	const ACL_WHO_UNKNOWN = -1;
	const ACL_OPERATION_VIEW = 1;
	const ACL_OPERATION_CREATE = 2;
	const ACL_OPERATION_MODIFY = 3;
	const ACL_OPERATION_DELETE = 4;

	protected $_username = NULL;
	protected $_whiteList = array();
	protected $_blackList = array();
	protected $_authenticationEnabled = TRUE;

	protected static $_instance = NULL;

	protected function __construct($config = array())
	{
		if (!is_array($config)) {
			$config = array('username' => $config);
		}
		if(array_key_exists('username', $config)) {
			$this->_username = $config['username'];
		}
		if(array_key_exists('whiteList', $config)) {
			$this->_whiteList = $config['whiteList'];
		}
		if(array_key_exists('blackList', $config)) {
			$this->_blackList = $config['blackList'];
		}
	}

	public static function getInstance()
	{
		return Saf_Acl::$_instance;
	}

	public function who($module, $controller = 'index', $action = 'index' , $stack = array())
	{
		if ($this->isInList($this->_whiteList, $module, $controller, $action, $stack)) {
			$stackPart = implode('/', $stack);
			if ($stackPart) {
				$stackPart = "/{$stackPart}";
			}
			Saf_Debug::out("White-listed access for {$module}/{$controller}/{$action}{$stackPart}");
			return self::ACL_WHO_ANYONE;
		}
		if ($this->isInList($this->_blackList, $module, $controller, $action, $stack)) {
			$stackPart = implode('/', $stack);
			if ($stackPart) {
				$stackPart = "/{$stackPart}";
			}
			Saf_Debug::out("Black-listed access for {$module}/{$controller}/{$action}{$stackPart}");
			return self::ACL_WHO_NOONE;
		}
		if (
			$this->_authenticationEnabled
			&& 'default' == $module
			&& (
				('login' == $controller)
				|| ('index' == $controller && 'login' == $action)
			)		
		) {
			return self::ACL_WHO_ANYONE;
		}
		if (
			$this->_authenticationEnabled
			&& 'default' == $module
			&& (
				('logout' == $controller)
				|| ('index' == $controller && 'logout' == $action)
			)
		) {
			return self::ACL_WHO_ANYUSER;
		}
		return self::ACL_WHO_UNKNOWN;
	}
	
	public static function allowStartupException($module, $controller = 'index', $action = 'index' , $stack = array())
	{
		return FALSE;
	}

	public static function init($acl)
	{
		self::$_instance = $acl;
	}

	public function getUsername()
	{
		return $this->_username;
	}

	public static function isInList($list, $module, $controller = 'index', $action = 'index', $stack = array())
	{
		if (!is_array($list) && !is_null($list)) {
			$list = array($list);
		}
		if (count($list) == 0) {
			return FALSE;
		}
		foreach($list as $acl) {
			if (self::matches($acl, $module, $controller, $action, $stack)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	public static function matches($acl, $module, $controller = 'index', $action = 'index', $stack = array()) {
		if (!is_array($acl)){
			$acl = explode('/', $acl);
		}
		if (is_null($stack)) {
			$stack = array();
		}
		$request = array_merge(array($module,$controller,$action), $stack);
		foreach($request as $partIndex=>$part) {
			if (array_key_exists($partIndex, $acl)){
				if (is_array($acl[$partIndex]) && !in_array($part, $acl[$partIndex])) {
					return FALSE;
				} else if ($part != $acl[$partIndex]) {
					return FALSE;
				}
			} else {
				return TRUE;
			}
		}
		return TRUE;
	}
}