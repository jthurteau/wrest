<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base class for application bootstrap

*******************************************************************************/
require_once(LIBRARY_PATH . '/Saf/Debug.php');
require_once(LIBRARY_PATH . '/Saf/Exception/Assist.php');
require_once(LIBRARY_PATH . '/Saf/Filter/Truthy.php');

abstract class Saf_Bootstrap
{
    protected $_autoSession = FALSE;
    protected $_application = NULL;
    protected $_config = NULL;
    protected static $_rootStrap = NULL;

    public function __construct($application, $config = array())
    {
        $this->_application = $application;
        $this->_config = $config;
        if (is_null(self::$_rootStrap)) {
        	self::$_rootStrap = $this;
        }      
    }

	public function run(&$request = NULL)
    {
		if (!$this->_application || !method_exists($this->_application, 'run')) {
			throw new Exception('No Application Provided');
		}
		try {
			$response = self::getBaseResponse();
	    	$this->_preApplicationRun($request, $response);
			$this->_application->run($request, $response);
			$this->_postApplicationRun($request, $response);
			return $response; //#NOTE <^this will not return if it is the rootStrap.
		} catch (Saf_Exception_Assist $e) {
			//#TODO #1.0.0 handle assist
			print_r(array('assist', $e));
			die();
			$subApplicaiton = Saf_Application::load($e->getApplicationId(), APPLICATION_ENV);
			$this->_application->shareRoute($subApplication);
			$subApplication->start(NULL, $e->getRequest());
		}
    }
    
    protected function _preApplicationRun(&$request = NULL, &$response = NULL)
    {
    	//#TODO #2.0.0 testing isolation and profiling (e.g. time isolation)
    	if (!isset($_SESSION)) {
	    	if (array_key_exists('autoSession', $this->_config)) {
	    		$this->_autoSession = Saf_Filter_Truthy::filter($this->_config['autoSession']);
	    	}
	    	if ($this->_autoSession) {
	    		$this->_preSessionStart()->_sessionStart()->_postSessionStart();
	    	}
    	}
    	return $this;
    }
    
    protected function _postApplicationRun(&$request = NULL, &$response = NULL)
    {
    	//#TODO #2.0.0 testing isolation and profiling (e.g. time isolation)
    	if ($this == self::$_rootStrap) {
    		Saf_Debug::dieSafe();
    	}
    }
    
    public function preSessionStart()
    {
    	return $this;
    }
    
    public function sessionStart()
    {
    	session_start();
    	return $this;
    }
    
    public function postSessionStart()
    {
    	Saf_Debug::sessionReadyListner();
    	return $this;
    }

	/**
	 * @param array $configuration
	 * return array
	 */
	public function getBaseResponse($configuration = NULL)
	{
		return array();
	}
}
