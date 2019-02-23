<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Router Plugin to detect the request type and remember it for later

*******************************************************************************/

class Saf_Controller_Front_Plugin_RestDetection extends Zend_Controller_Plugin_Abstract
{
	const ACCEPT_FORMAT = 0;
	
	protected static $_acceptTables = array(
		0 => array(
			0 => 'text/html+javascript+css',
			'text/html' => 'text/html+javascript+css',
			'text/javascript' => 'json',
			'application/json' => 'json',
			'application/xml' => 'xml'
		)	
	);
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		Zend_Registry::set('requestIsAjax', $request->isXmlHttpRequest());
		$format = $this->parseAccept($request->getHeader('accept'));
		Zend_Registry::set('responseFormat', $format);
	}
	
	public function parseAccept($string, $table = self::ACCEPT_FORMAT)
	{
		$default = self::$_acceptTables[$table][0];
		if ('' == trim($string)) {
			Saf_Debug::out("No accept format specified, using default ({$default}).", 'NOTICE');
			return $default;
		}
		$array = explode(',', $string);
		foreach ($array as $option) {
			$format = 
				strpos($option, ';') !== FALSE
				? substr($option, 0, strpos($option, ';'))
				: $option;
			if (array_key_exists($format, self::$_acceptTables[$table])) {
				$notice =
					"Accept format ({$format}), using "
					. self::$_acceptTables[$table][$format]
					. '.';
				Saf_Debug::out($notice, 'NOTICE');
				return self::$_acceptTables[$table][$format];
			}
		}
		Saf_Debug::out("Unrecognized accept format ({$format}), using default ({$default}).", 'NOTICE');
		return $default;
	}

}

