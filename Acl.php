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
	
	public function __construct($config = array())
	{
		if (!is_array($config)) {
			$config = array('username' => $config);
		}
	}
	
	public function who($module, $controller = 'index', $action = 'index' , $stack = array())
	{
		if (
			'default' == $module
			&& (
				('login' == $controller)
				|| ('index' == $controller && 'login' == $action)
			)		
		) {
			return self::ACL_WHO_ANYONE;
		}
		if (
				'default' == $module
				&& (
						('logout' == $controller)
						|| ('index' == $controller && 'logout' == $action)
				)
		) {
			return self::ACL_WHO_OTHERUSER;
		}
		return self::ACL_WHO_UNKNOWN;
	}
	
	public static function init($config = array())
	{
		
	}
}