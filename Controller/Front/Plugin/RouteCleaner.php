<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Router Plugin to cleanup module/controller/action names due to use of Multiviews

*******************************************************************************/

class Saf_Controller_Front_Plugin_RouteCleaner extends Zend_Controller_Plugin_Abstract
{
	public function routeShutdown(Zend_Controller_Request_Abstract $request)
	{
		$request->setModuleName(str_replace('.php', '', $request->getModuleName()));
		$request->setControllerName(str_replace('.php', '', $request->getControllerName()));
		$request->setActionName(str_replace('.php', '', $request->getActionName()));
	}
}

