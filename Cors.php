<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for managing HTTP status headers
@see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html for information on these statuses

*******************************************************************************/

class Saf_Cors{

	const HEADER_ORIGIN = 'origin';
	const HEADER_HEADER = 'headers';
	const HEADER_XHEADER = 'xheaders';
	const HEADER_METHOD = 'methods';
	const HEADER_MAXAGE = 'maxage';
	const HEADER_CRED = 'credentials';

	protected static $_origin = '';
	protected static $_methods = '';
	protected static $_headers = '';
	protected static $_xheaders = '';
	protected static $_maxage = '';
	protected static $_credentials = '';


	public static function setOrigin($string)
	{
		self::$_origin = $string;
	}

	public static function setMaxAge($string)
	{
		self::$_maxage = $string;
	}

	public static function setMethods($mixed)
	{
		if (!is_array($mixed)) {
			$mixed = array($mixed);
		}
		self::$_methods = implode(', ', $mixed);
	}

	public static function setHeaders($mixed)
	{
		if (!is_array($mixed)) {
			$mixed = array($mixed);
		}
		self::$_headers = implode(', ', $mixed);
	}
	public static function setExposeHeaders($mixed)
	{
		if (!is_array($mixed)) {
			$mixed = array($mixed);
		}
		self::$_xheaders = implode(', ', $mixed);
	}

	public static function allowCredentials()
	{
		self::$_credentials = 'true';
	}

	public static function out($string = NULL)
	{
		foreach(self::_headers($string) as $field => $header) {
			if ('commandline' == APPLICATION_PROTOCOL) {
				print("Cors: {$string}\r\n");
			} else {
				header("{$field} {$header}");
			}
		}
	}

	/**
	 * returns headers
	 * @param string $string which header, defaults to all
	 */
	protected static function _headers($string = NULL)
	{
		$return = array();
		if(!is_null($string)) {
			$headers = self::$_all;
		} else {
			$headers = array($string);
		}
		foreach($headers as $header) {
			switch($header){
				case HEADER_ORIGIN:
					if (!is_null(self::$_origin)) {
						$return['Access-Control-Allow-Origin'] = self::$_origin;
					}
					break;
				case HEADER_HEADER:
					if (!is_null(self::$_headers)) {
						$return['Access-Control-Allow-Headers'] = self::$_headers;
					}
					break;
				case HEADER_XHEADER:
					if (!is_null(self::$_xheaders)) {
						$return['Access-Control-Expose-Headers'] = self::$_xheaders;
					}
					break;
				case HEADER_METHOD:
					if (!is_null(self::$_methods)) {
						$return['Access-Control-Allow-Methods'] = self::$_methods;
					}
					break;
				case HEADER_MAXAGE:
					if (!is_null(self::$_maxage)) {
						$return['Access-Control-Max-Age'] = self::$_maxage;
					}
					break;
				case HEADER_CRED:
					if (self::$_credentials === 'true') {
						$return['Access-Control-Allow-Credentials'] = self::$_credentials;
					}
					break;
			}
		}
		return $return;
	}
}

