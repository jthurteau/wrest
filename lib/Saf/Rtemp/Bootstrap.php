<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base class for application bootstrap

*******************************************************************************/

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

	public function run($request = NULL)
    {
		if (!$this->_application || !method_exists($this->_application, 'run')) {
			throw new Exception('No Application Provided');
		}
    	$this->_preRun();
		//#TODO #2.0.0 handle request setup
		$response = $this->_application->run($request);
		$this->_postRun();
		//#TODO #2.0.0 handle response setup
		return $response;
    }
    
    protected function _preRun()
    {
    	//#TODO #2.0.0 testing isolation (e.g. time isolation)
    	if (array_key_exists('autoSession', $this->_config)) {
    		$this->_autoSession = $this->_config['autoSession'];
    	}
    	if ($this->_autoSession) {//#TODO #2.0.0 support manual/auto/sessionless
    		$this->_preSessionStart()->_sessionStart()->_postSessionStart();
    	} else {
    		 
    	}
    	return $this;
    }
    
    protected function _postRun()
    {
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
    	//$this->_initDebug();
    	return $this;
    }
    
    
}
