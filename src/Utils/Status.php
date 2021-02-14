<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for managing HTTP status headers
@see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html for information on these statuses

*******************************************************************************/

class Saf_Status{

	const STATUS_200_OK = 200;
	const STATUS_201_CREATED = 201;
	const STATUS_202_ACCEPTED = 202;
	const STATUS_203_NONAUTHINFO = 203;
	const STATUS_204_NOCONTENT = 204;
	const STATUS_205_RESETCONTENT = 205;
	const STATUS_300_MULTIPLE = 300;
	const STATUS_301_PERMANENT = 301;
	const STATUS_302_FOUND = 302;
	const STATUS_303_OTHER = 303;
	const STATUS_304_NOTMODIFIED = 304;
	const STATUS_307_TEMPORARY = 307;
	const STATUS_400_BADREQUEST = 400;
	const STATUS_401_NOTAUTH = 401;
	const STATUS_403_FORBIDDEN = 403;
	const STATUS_404_NOTFOUND = 404;
	const STATUS_405_BADMETHOD = 405;
	const STATUS_406_NOTACCEPTABLE = 406;
	const STATUS_408_REQUESTTIMEOUT = 408;
	const STATUS_409_CONFLICT = 409;
	const STATUS_410_GONE = 410;
	const STATUS_412_PRECONDITION = 412;
	const STATUS_413_ENTITYSIZE = 413;
	const STATUS_415_BADMEDIA = 415;
	const STATUS_417_EXPECTATION = 417;
	const STATUS_500_ERROR = 500;
	const STATUS_501_NOTIMPLEMENTED = 501;
	const STATUS_502_BADGATEWAY = 502;
	const STATUS_503_UNAVAILABLE = 503;
	const STATUS_504_GATEWAYTIMEOUT = 504;

	public static function set($status){
		switch ($status){
			case 200:
			case '200':
				self::_header('200 OK');
				break;
			case 201:
			case '201':
				self::_header('201 Created');
				break;
			case 202:
			case '202':
				self::_header('202 Accepted');
				break;
			case 203:
			case '203':
				self::_header('203 Non-Authoritative Information');
				break;
			case 204:
			case '204':
				self::_header('204 No Content');
				break;
			case 205:
			case '205':
				self::_header('205 Reset Content');
				break;
			case 300:
			case '300':
				self::_header('300 Multiple Choices');
				break;
			case 301:
			case '301':
				self::_header('301 Moved Permanently');
				//don't keep using the request-uri
				break;
			case 302:
			case '302':
				self::_header('302 Found');
				//temporary, keep using the request-uri
				break;
			case 303:
			case '303':
				self::_header('303 See Other');
				//context specific, keep using the request-uri POST safe redirect option
				//#TODO #2.0.0 use this for redirect exception, with 302 as the non-default antique browser option
				break;
			case 304:
			case '304':
				self::_header('304 Not Modified');
				break;
			case 307:
			case '307':
				self::_header('307 Temporary Redirect');
				//temporary, keep using the request-uri stricter alternative to 302
				//which may incorrectly auto-redirect
				break;
			case 400:
			case '400':
				self::_header('400 Bad Request');
				break;
			case 401:
			case '401':
				self::_header('401 Unauthorized');
				break;
			case 403:
			case '403':
				self::_header('403 Forbidden');
				break;
			case 404:
			case '404':
				self::_header('404 Not Found');
				break;
			case 405:
			case '405':
				self::_header('405 Method Not Allowed');
				break;
			case 406:
			case '406':
				self::_header('406 Not Acceptable');
				//cannot formulate a response that would conform to the client's
				//expectations.
				break;
			case 408:
			case '408':
				self::_header('408 Request Timeout');
				break;
			case 409:
			case '409':
				self::_header('409 Conflict');
				break;
			case 410:
			case '410':
				self::_header('410 Gone');
				break;
			case 412:
			case '412':
				self::header('412 Precondition Failed');
				break;
			case 413:
			case '413':
				self::_header('413 Request Entity Too Large');
				break;
			case 415:
			case '415':
				self::_header('415 Unsupported Media Type');
				break;
			case 416:
			case '416':
				self::_header('416 Expectation Failed');
				break;
			case 500:
			case '500':
				self::_header('500 Internal Server Error');
				break;
			case 501:
			case '501':
				self::_header('501 Not Implemented');
				break;
			case 502:
			case '502':
				self::_header('502 Bad Gateway');
				break;
			case 503:
			case '503':
				self::_header('503 Service Unavailable');
				break;
			case 504:
			case '504':
				self::_header('504 Gateway Timeout');
				break;
			default:
				if (class_exists('Saf_Debug')) {
					Saf_Debug::out('Unrecognized HTTP Status Set Request: '. $status);
				}
				return FALSE;
		}
		return TRUE;
	}

	/**
	 * outputs a header based on the registered protocol
	 * @param string $string code plus label
	 */
	protected static function _header($string)
	{
		if ('commandline' == APPLICATION_PROTOCOL) {
			print("Status: {$string}\r\n");
		} else {
			header("{$_SERVER["SERVER_PROTOCOL"]} {$string}");
		}
	}
}

