<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Local_View_Helper_Javascript extends Zend_View_Helper_Abstract
{

	public function javascript(){
		return $this;
	}
	
	public function actionScripts(){
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$controller = $request->getControllerName();
		$action = $request->getActionName();
		$searchPath = PUBLIC_PATH . '/javascript/';
		$baseUrl = Zend_Registry::get('baseUrl');
		$controllerScriptPath = $searchPath . strtolower($controller) . '.js';
		$controllerScriptUrl = $baseUrl . 'javascript/' . strtolower($controller) . '.js';
		$actionScriptPath = $searchPath . strtolower($controller) . '/' . strtolower($action) . '.js';
		$actionScriptUrl = $baseUrl . 'javascript/' . strtolower($controller) . '/' . strtolower($action) . '.js';
		$return = '';
		if (file_exists($controllerScriptPath)) {
			$return .= $this->tag($controllerScriptUrl);
		}
		if (file_exists($actionScriptPath)) {
			$return .= $this->tag($actionScriptUrl);
		}
		return($return);
	}
	
	public function tag($url) 
	{
		return "<script src=\"{$url}\" type=\"text/javascript\"></script>\n";
	}

}