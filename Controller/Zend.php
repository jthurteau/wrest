<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base class for Zend Action Controllers with patches to SAF

*******************************************************************************/

abstract class Saf_Controller_Zend extends Zend_Controller_Action {

	/**
	 * Auto extract a request param from one or more sources, substituting
	 * optional default if not present.
	 * $sources will be searched iteratively, and the first match returned.
	 * $sources can be an integer as a shortcut for array('stack' => <int>)
	 * $sources can be a string as a shortcut for array('request' => <string>)
	 * the 'request' facet is anything in the controller's "request" object.
	 * #NOTE the session is intentionally excluded as an option
	 * @param mixed $sources string, int, array indicating one or more sources
	 * @param mixed $default value to return if no match is found, defaults to NULL
	 * @param Zend_Controller_Request_Abstract $request optional alternate request object to use
	 * #TODO #1.5.0 add option for each source to be an array so more than one value in each can be searched
	 */
	protected function _extractRequestParam($sources, $default = NULL, $request = NULL)
	{
		$result = $default;
		if (is_null($request)) {
			$request = $this->getRequest();
		}
		if (!is_array($sources)) {
			$sources = 
				is_int($sources) 
				? array('stack' => $sources)
				: array('request' => $sources);
		}
		foreach($sources as $source => $index) {
			if (is_int($source)) {
				$source = is_int($index)
					? 'stack'
					:'request';
			}
			switch ((string)$source) {
				case 'stack' :
					$stack = $request->getParam('resourceStack');
					if (array_key_exists($index, $stack)) {
						return $stack[$index];
					}
					break;
				case 'get' :
					$get = $request->getQuery();
					if (array_key_exists($index, $get)) {
						return $get[$index];
					}
					break;
				case 'post' :
					$post = $request->getPost();
					if (array_key_exists($index, $post)) {
						return $post[$index];
					}
					break;
				case 'session' : //#TODO #1.1.0 deep thought on if this should be allowed 
					if (isset($_SESSION) && is_array($_SESSION) && array_key_exists($index, $_SESSION)) {
						return $_SESSION[$index];
					}
					break;
				case 'request' :
					return $request->has($index)
						? $request->getParam($index)
						: $default;
			}
		}
		return $default;
	}
	
	protected function _extractMultiIdString($idString, $separator = '_'){
		if (!is_array($idString)) {
			$idString = explode($separator, $idString);
		}
		foreach($idString as $index => $id) {
			$idString[$index] = (int)$id;
			if (!$idString[$index]) {
				unset($idString[$index]);
			}
		}
		return $idString;
	}
	
	protected  function _autoJs($path = NULL)
	{
		if (is_null($path)) {
			$path = 'todo';
// 				(
// 					$this->getModuleName() != 'default'
// 					? ($this->getModule() . '/')
// 					: ''
// 				) . (
// 					$this->getControllerName() . '/'
// 					. ''
// 				) . (
// 					$this->getActionName() . '/'
// 					. ''
// 				);
		}
		Saf_Layout::autoJs($path);
	}
	
	public function dispatch($action)
	{
		if (Zend_Registry::get('requestIsAjax')) {
			$this->_helper->layout->disableLayout();
		}
		parent::dispatch($action);
	}
	
	
	/**
	 * Returns a failure message literal instead of null if 
	 * $returnValue is null. This should be called on every
	 * controller return value to ensure no controller ever
	 * returns null.
	 * 
	 * @param $returnValue mixed
	 */
	protected function _neverReturnNull($returnValue)
	{
		return (
			$returnValue === null
			? array(
				'message' => 'Model Returned NULL.', 
				'success' => false, 
				'count'=> 0
			)
			: $returnValue
		);
	}

	/**
	 * Terminates the program and outputs JSON data.
	 *
	 * @param mixed $data
	 */
	public function json($data, $pretty = FALSE)
	{
		header('Content-Type: application/json');
		$options = (
			$pretty && defined('JSON_PRETTY_PRINT')
			//#TODO #2.0.0 remove the last condition when 5.4 is more standard
			? (JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
			: JSON_FORCE_OBJECT
		);
		print(json_encode($this->_neverReturnNull($data), $options));
		die; //#TODO #1.2.0 debug die safe also include debug in output option
		//$this->_helper->json->sendJson($this->_neverReturnNull($data)); //#DEPRECATED #1.5.0			
	}
}