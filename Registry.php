<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for storing and retrieving globally accessible state

*******************************************************************************/

class Saf_Registry {
    
	protected static $_singleton = NULL;
    protected static $_configuration = NULL;
	
	private static $_unavailableExceptionMessage = 'Requested registry value is not available.';
	private static $_setExceptionTemplate = 'Unable to edit values in the {{facet}} facet.';
	private static $_unwritableExceptionMessage = 'Requested registry value could not be edited.';

    protected static $_writableFacets = array('root','response','session','auth');
    protected static $_defaultSessionKeepers = array('debug');
    protected static $_wippedValues = array();

    const FACET_ROOT = 'root';
    const FACET_RESPONSE = 'response';
    const FACET_GET = 'get';
    const FACET_POST = 'post';
    const FACET_REQUEST = 'request';
    const FACET_SESSION = 'session';
    const FACET_AUTH = 'auth';

    protected function __construct()
    {
		self::$_configuration = array(
			self::FACET_ROOT => array(),
			'response' => array(),
			'get' => $_GET,
			'post' => $_POST,
			'request' => $_REQUEST,
			'session' => &$_SESSION,
			'auth' => array()
		);
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
    
	protected static function _init()
	{
		if (is_null(self::$_singleton)) {
            self::$_singleton = new Saf_Registry();
        }
	}
	
	/**
	 * retrive stored value
	 * @param string $name
	 * @throws Exception when not available
	 * @return mixed stored value
	 */
	public static function get($name = NULL)
	{
		self::_init();
        if (is_null($name)) {
            return self::$_singleton;
        }
		$request = 
			is_array($name)
			? $name
			: explode(':', $name);
		$facet = array_shift($request);
		if ('config' == $facet) {
			return self::_get('root:config', self::$_configuration); //#TODO #1.0.0 Saf_Config is not currently setup as a singleton...
		} else {
			try{
				if(array_key_exists($facet, self::$_configuration)){
					return self::_get($request, self::$_configuration[$facet]);
				} else {
					array_unshift($request,$facet);
					return self::_get($request, self::$_configuration['root']);
				}
			} catch (Exception $e){
				$stringName = implode(':', $request);
				throw new Exception(self::$_unavailableExceptionMessage . (Saf_Debug::isEnabled() ? "({$stringName})" : ''));
			}
		}
	}
	
	protected static function _get($name, $source)
	{
		if (0 == count($name)) {
			return $source;
		}
		$newSourceName = array_shift($name);
		if (is_array($source) 
			&& array_key_exists($newSourceName, $source)
		){
			return self::_get($name, $source[$newSourceName]);
		} else if (is_object($source) 
			&& property_exists($source, $newSourceName)
		) {
			return self::_get($name, $source->$newSourceName);
		}
		$stringName = implode(':', $name);
		throw new Exception(self::$_unavailableExceptionMessage . (Rd_Debug::isEnabled() ? "({$stringName})" : ''));
    }

    public function __get($name)
    {
        return self::_get($name, self::$_configuration[self::FACET_ROOT]);
    }

    public function __set($name, $value)
    {
        return self::_set($name, self::$_configuration[self::FACET_ROOT], $value);
    }

    /**
     * store value
     * @param string $name
     * @param mixed $value
     * @throws Exception invalid storage option
     */
	public static function set($name, $value)
	{
		self::_init();
		$request = 
			is_array($name)
			? $name
			: explode(':', $name);
		$facet = array_shift($request);
		if(
			(array_key_exists($facet, self::$_configuration) 
				&& !in_array($facet, self::$_writableFacets)
			) || (!array_key_exists($facet, self::$_configuration) 
				&& !in_array('root', self::$_writableFacets)
			)
		) {
			throw new Exception(str_replace('{{facet}}', $facet, self::$_setExceptionTemplate));
		}
		if(array_key_exists($facet, self::$_configuration)){
			return self::_set($request, self::$_configuration[$facet], $value);
		} else {
			array_unshift($request, $facet);
			return self::_set($request, self::$_configuration['root'], $value);
		}
	}
	
	protected static function _set($name, &$source, $value)
    {
		if (0 == count($name)) {
			$source = $value;
			return;
		}
		$newSourceName = array_shift($name);
		if (is_array($source)) {
			return self::_set($name, $source[$newSourceName], $value);
		} else if (is_object($source) 
			&& property_exists($source, $newSourceName)
		) {
			return self::_set($name, $source->$newSourceName, $value);
		}
		throw new Exception(self::$_unwritableExceptionMessage);
    }

    public function __isset($name)
    {
        return array_key_exists($name, self::$_configuration[self::FACET_ROOT]);
    }


    public function __unset($name)
    {
        if(array_key_exists($name, self::$_configuration[self::FACET_ROOT])) {
            if (array_key_exists($name, self::$_wipedValues)) {
                self::$_wipedValues[$name]++;
            } else {
                self::$_wipedValues[$name] = 1;
            }
            unset(self::$_configuration[self::FACET_ROOT]);
        }
    }
    
	/**
	 * clears all but select values from the session
	 * @param array $keepers values to preserve
	 */
	public static function cleanSession($keepers = NULL)
	{
		if (is_null($keepers)) {
            $keepers = self::$_defaultSessionKeepers;
        }
        if ('' == session_id()) {
			session_start();
		}
		
		$keeperValues = array();
		foreach($keepers as $keeperName){
			if (array_key_exists($keeperName, $_SESSION)) {
				$keeperValues[$keeperName] = $_SESSION[$keeperName];
			}
		}
		session_unset();
		foreach($keeperValues as $keeperName=>$keeperValue){
			$_SESSION[$keeperName] = $keeperValue;
		}
	}
    
}