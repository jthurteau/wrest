<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Router Plugin Exception used to pass new route.

*******************************************************************************/

class Saf_Controller_Front_Plugin_RouteRules_Exception extends Exception
{

	protected $_module = '';
	protected $_controller = 'error';
	protected $_action = 'index';	
	protected $_resourceStack = array();

	public function __construct($message='', $code = 0, $previous = NULL, $route = array())
	{
		parent::__construct();
		if (is_array($route) && count($route) > 0) {
			$this->_module = (
				array_key_exists(0, $route)
				? $route[0]
				: $this->_module
			);
			$this->_controller = (
				array_key_exists(1, $route)
				? $route[1]
				: $this->_controller
			);
			$this->_action = (
				array_key_exists(2, $route)
				? $route[2]
				: $this->_action
			);
			$this->_resourceStack = (
				array_key_exists(3, $route)
				? array_slice($route, 3)
				: array()
			);
		}
	}

	public function getModuleName()
	{
		return $this->_module;
	}

	public function getControllerName()
	{
		return $this->_controller;
	}

	public function getActionName()
	{
		return $this->_action;
	}
	public function getResourceStack()
	{
		return $this->_resourceStack;
	}
}
