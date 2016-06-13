<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Application handler for MVC style applications

*******************************************************************************/

abstract class Saf_Application_Mvc extends Saf_Application
{
	protected $_view = NULL;
	protected $_model = NULL;
	protected $_routerClass = 'Saf_Router_Restful';
	
	public function run(&$request = NULL, &$response = NULL)
	{
		$accepted = FALSE;
		while(!$accepted) {
			$requestedDestination = $this->_router->direct($request);
			if (in_array($requestedDestination, $this->_route)) {
				throw new Exception('Cyclical route detected');
			}
			array_push($this->_route, $requestedDestination);
			$this->_current++;
			$allowedDestination = $this->_acl->assert($requestedDestination);
			if ($allowedDestination != $requestedDestination) {
				array_push($this->_route, $allowedDestination);
				$this->_current++;
			}
			try {
				$accepted = $this->_router->dispatch($allowedDestination, $request, $response);
			} catch (Saf_Exception_Forward $e) {
				$this->_router->forward($request, $e);
			}			
		}
		$this->_view = Saf_View::create($response, $this->_model);
		$this->_view->render();
	}	
}