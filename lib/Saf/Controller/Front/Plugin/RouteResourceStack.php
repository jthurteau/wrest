<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Router Plugin to add a stack of values after the matched action

*******************************************************************************/

class Saf_Controller_Front_Plugin_RouteResourceStack extends Zend_Controller_Plugin_Abstract
{
	
	public function routeShutdown(Zend_Controller_Request_Abstract $request)
	{
		$stack = explode('/' , $request->getPathInfo());
		$newStack = array();
		$preRouter = array();
		if ('' == $stack[count($stack) - 1]) {
			array_pop($stack);
		}
		if (count($stack) && '' == $stack[0]) {
			array_shift($stack);
		}
		$pathParts = explode('/', ROUTER_PATH);
		if ('' == $pathParts[count($pathParts) - 1]) {
			array_pop($pathParts);
		}
		if (count($pathParts) && '' == $pathParts[0]) {
			array_shift($pathParts);
		}
		if ($pathParts) {
			if (array_key_exists(0, $pathParts)) {
				$request->setModuleName($pathParts[0]);
			}
			if (array_key_exists(1, $pathParts)) {
				$request->setControllerName($pathParts[1]);
			}
			if (array_key_exists(2, $pathParts)) {
				$request->setActionName($pathParts[2]);
			}
			if (array_key_exists(3, $pathParts)) {
				$newStack = array_merge(array_splice($pathParts, 3) , $stack);
			}
		} else {
			$routerFound = FALSE;
			$moduleFound = 'default' == $request->getModuleName();
			$controllerFound = $moduleFound && 'index' == $request->getControllerName();
			$actionFound = $controllerFound && 'index' == $request->getActionName();
			$router = ROUTER_NAME;
			$controllerReflector = NULL;
			foreach ($stack as $part) {
//Saf_Debug::outData(array($part));
				$routerFound = $routerFound || TRUE; //#TODO #2.0.0 is this still needed for non Zend Routing?
				if(!$moduleFound && $request->getModuleName() == $part){
					$moduleFound = TRUE;
					array_shift($stack);
				} else if (!$controllerFound && $request->getControllerName() == $part){
					$controllerFound = TRUE;
					//#TODO #9.9.9 handle bug with routing with path #¯\_(ツ)_/¯
					$controllerName = ucfirst($request->getControllerName());
					$front = Zend_Controller_Front::getInstance();
					$paths = $front->getControllerDirectory();
					$controllerClass = "{$controllerName}Controller";
					foreach($paths as $path) {
						if (file_exists("{$path}/{$controllerClass}.php")) {
							include_once("{$path}/{$controllerClass}.php");
						}
					}
					$controllerReflector = new ReflectionClass($controllerClass);
					//#TODO #2.0.0 handle the case where class is non-existant (i.e. module/[index/index/]resourcestack)
					array_shift($stack);
					continue;
				} else if (!$actionFound && $request->getActionName() == $part){
					$actionFound = TRUE;
					$actionName = ucfirst($request->getActionName());
					$controllerHasAction =
						$controllerReflector
						&& $controllerReflector->hasMethod("{$actionName}Action");
					if ($controllerHasAction) {
						array_shift($stack);
					} else {
						$request->setActionName('');
					}
					continue;
				}	
				if ($routerFound && $moduleFound && $controllerFound && $actionFound){
//Saf_Debug::outData(array('stacking...', $routerFound, $moduleFound, $controllerFound, $request->getActionName(), $actionFound, $part));
					$newStack[] = array_shift($stack);
				} else {
//Saf_Debug::outData(array('prerouting...', $routerFound, $moduleFound, $controllerFound, $request->getActionName(), $actionFound, $part));
					$preRouter[] = array_shift($stack);
				}
			}
//Saf_Debug::outData(array('preparts',$pathParts,$newStack));
			if (count($stack)) {
				$newStack = array_merge($newStack, $stack);
			}
//Saf_Debug::outData(array('postparts',$newStack));
			if ($preRouter && !$newStack) {
				$newStack = $preRouter;
				$preRouter = array();
			}
		}
		if ($preRouter) {
			Saf_Debug::outData(array('preRouter' => $preRouter));
		}
		$request->setParam('resourceStack', $newStack);
		$stackString = implode('/', $newStack);
		$module = $request->getModuleName();
		$controller = $request->getControllerName();
		$action = $request->getActionName();
		Saf_Debug::out("Resolved to path: {$module} {$controller} {$action} {$stackString}", 'NOTICE');
	}
}

