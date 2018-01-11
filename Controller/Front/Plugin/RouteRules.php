<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Router Plugin to enforce various routing rules.

*******************************************************************************/

class Saf_Controller_Front_Plugin_RouteRules extends Zend_Controller_Plugin_Abstract
{

	protected static $_redactedKeys = array(
		'password', 'loggedout', 'forwardCode', 'forwardUrl'
	);
	
	public function routeShutdown(Zend_Controller_Request_Abstract $request)
	{
		try {
			$this->_statusRules(
				$request->getModuleName(),
				$request->getControllerName(),
				$request->getActionName(),
				$request->getParam('resourceStack')
			);
			$this->_aclRules(
				$request->getModuleName(),
				$request->getControllerName(),
				$request->getActionName(),
				$request->getParam('resourceStack'),
				$request->getQuery()
			);
			$this->_workflowRules(
				$request->getModuleName(),
				$request->getControllerName(),
				$request->getActionName(),
				$request->getParam('resourceStack')
			);
		} catch (Saf_Controller_Front_Plugin_RouteRules_Exception $e) {
			Saf_Debug::out('Enforcing Routing Rule: ' . $e->getMessage());
			$request->setModuleName($e->getModuleName());
			$request->setControllerName($e->getControllerName());
			$request->setActionName($e->getActionName());
			$request->setParam('resourceStack', $e->getResourceStack());
		}
	}

	protected function _statusRules($module, $controller, $action, $stack)
	{
		if ('install' == APPLICATION_STATUS && 'install' != $controller) {
			throw new Saf_Controller_Front_Plugin_RouteRules_Exception(
				'Forcing application route to install mode.',
				0,NULL,
				array(
					'',
					'install'
				)
			);
		}
		if ('down' == APPLICATION_STATUS) {
			throw new Saf_Controller_Front_Plugin_RouteRules_Exception(
				'Forcing application route to maintenance mode.',
				0,NULL,
				array(
					'',
					'error',
					'down'
				)
			);
		}
		$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$e = $bootstrap->getStartupException();
		$applicationAcl = Saf_Acl::getInstance();
		if ($e && !$applicationAcl->allowStartupException($module, $controller, $action, $stack)) {
			throw new Saf_Exception_Startup('An error occured during startup.', 0, $e);
		}
	}

	protected function _aclRules($module, $controller, $action, $stack, $get = array())
	{
		$applicationAcl = Saf_Acl::getInstance();
		$url = (
			'default' != $module
			? "{$module}/"
			: ''
		) . (
			'index' != $controller || 'index' != $action || count($stack) //#TODO #2.0.0 handle module/[[index/index/]resourcestack
			? "{$controller}/"
			: ''
		) . (				
			(
				('index' != $action || count($stack))
				&& '' != $action
			) ? "{$action}/"
			: ''
		) . (				
			count($stack)
			? (implode('/',$stack) . '/')
			: ''
		);
		$getStack = array();
		foreach($get as $getKey => $getValue){
			if (!in_array($getKey, self::$_redactedKeys)) {
				$getStack[] = urldecode($getKey) . '=' . urlencode($getValue);
			}
		}
		$get =
			$getStack
			? ('?' . implode('&', $getStack))
			: '';
//Saf_Debug::outdata((array($url,$module,$controller,$action,$stack));
		$forward = Saf_UrlRewrite::encodeForward($url . $get);
		$redirectUrl = 'login/' . ($forward ? "?{$forward}" : '') ;
		$whoCan = $applicationAcl->who($module, $controller, $action, $stack);
		switch ($whoCan) {
			case Saf_Acl::ACL_WHO_ANYUSER:
			case Saf_Acl::ACL_WHO_USER:
				if (!Saf_Auth::isLoggedIn()) {
					throw new Saf_Exception_Redirect($redirectUrl);
				}
				break;
			case Saf_Acl::ACL_WHO_SOMEUSER:
				if (!Saf_Auth::isLoggedIn()) {
					throw new Saf_Exception_Redirect($redirectUrl);
				} else {
					throw new Saf_Exception_NotAllowed('Insufficient permissions for operation.');
				}
				break;
			case Saf_Acl::ACL_WHO_ANYONE:
				break;
			case Saf_Acl::ACL_WHO_OTHERUSER:
				if (!$username) {
					throw new Saf_Exception_NotAllowed('Insufficient permissions for operation.');
				}
				//#TODO #1.3.0 verify this works preoprly
				break;
			case Saf_Acl::ACL_WHO_NOONE:
				throw new Saf_Exception_NotAllowed('Operation Not Allowed.');
			default:
				throw new Saf_Exception_NotImplemented('Operation Not Supported.');
				
		}
	}

	protected function _workflowRules($module, $controller, $action, $stack)
	{

	}

}

