<?php
/*******************************************************************************
Saf/Client/Http.php
Created by Troy Hurteau (jthurtea@ncsu.edu)
###LICENSE###
 *******************************************************************************/
/**
 *
 * Base Client for Http Based Services
 * Note, each call creates and destroys a new curl.
 * @author jthurtea
 *
 */

class Saf_Jtemp_Client_Http{
	protected $_serviceUrl = '';
	protected $_serviceUser = '';
	protected $_servicePassword = '';
	protected $_serviceAuthenticate = false;
	protected $_lastStatus = '';
	protected $_lastError = '';
	protected $_curlConfig = array(
		CURLOPT_HEADER => true,
		CURLINFO_HEADER_OUT => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 2
	);
	protected static $_sslCodes = array(
		0 => 'ok the operation was successful',
		2 => 'unable to get issuer certificate',
		3 => 'unable to get certificate CRL',
		4 => 'unable to decrypt certificate\'s signature',
		5 => 'unable to decrypt CRL\'s signature',
		6 => 'unable to decode issuer public key',
		7 => 'certificate signature failure',
		8 => 'CRL signature failure',
		9 => 'certificate is not yet valid',
		10 => 'certificate has expired',
		11 => 'CRL is not yet valid',
		12 => 'CRL has expired',
		13 => 'format error in certificate\'s notBefore field',
		14 => 'format error in certificate\'s notAfter field',
		15 => 'format error in CRL\'s lastUpdate field',
		16 => 'format error in CRL\'s nextUpdate field',
		17 => 'out of memory',
		18 => 'self signed certificate',
		19 => 'self signed certificate in certificate chain',
		20 => 'unable to get local issuer certificate',
		21 => 'unable to verify the first certificate',
		22 => 'certificate chain too long',
		23 => 'certificate revoked',
		24 => 'invalid CA certificate',
		25 => 'path length constraint exceeded',
		26 => 'unsupported certificate purpose',
		27 => 'certificate not trusted',
		28 => 'certificate rejected',
		29 => 'subject issuer mismatch',
		30 => 'authority and subject key identifier mismatch',
		31 => 'authority and issuer serial number mismatch',
		32 => 'key usage does not include certificate signing',
		50 => 'application verification failure'
	);
	protected $_antiqueServerMode = 0;
	protected $_actionUrl = '';

	const UNENCODED_POST_DATA = -1;

	public function __construct($urlOrConfig = '')
	{
		$this->set($urlOrConfig);
	}

	public function getError()
	{
		return 'Status: ' . $this->_lastStatus . (
		'' != $this->_lastError
			? ' Error:' . $this->_lastError
			: ' No Error'
		);
	}

	public static function isUrlUnsafe($string)
	{
		return strpbrk($string, '?+,/:;?@ <?"#%{}|\\^~[]`') !== false;
	}

	public static function buildQuery($queryArray)
	{
		if(count($queryArray) > 0){
			$query = '?';
			foreach($queryArray as $key=>$value){
				$query .= ('?' == $query ? '' : '&' )
					. urlencode($key)
					. '='
					. urlencode($value);
			}
		} else {
			$query = '';
		}
		return $query;
	}

	public function set($urlOrConfig)
	{
		if (!is_array($urlOrConfig)) {
			$this->_serviceUrl = $urlOrConfig;
		} else {
			if (array_key_exists('url', $urlOrConfig)) {
				$this->_serviceUrl = $urlOrConfig['url'];
				unset($urlOrConfig['url']);
			}
			if(array_key_exists('user', $urlOrConfig)) {
				$this->_serviceUser = $urlOrConfig['user'];
				$this->_serviceAuthenticate = true;
				unset($urlOrConfig['user']);
			}
			if(array_key_exists('password', $urlOrConfig)) {
				$this->_servicePassword = $urlOrConfig['password'];
				$this->_serviceAuthenticate = true;
				unset($urlOrConfig['password']);
			}
			foreach($urlOrConfig as $key=>$value) {
				if(defined($key)){
					$this->_curlConfig[$key] = $value; //#TODO these are not yet being pulled on go()
				}
			}
		}
		return $this;
	}

	public function setAction($action)
	{
		$this->_actionUrl = $action;
		return $this;
	}

	public function go($get = array(), $post=array(), $postContentType = '')
	{
		if ('' == trim($this->_serviceUrl)) {
			throw new Exception('Must specify a url before using the Http Client.');
		}
		$curl = curl_init();
		if(is_array($get)){
			$query = $this->buildQuery($get);
		} else if ('' != $get) {
			$cleanQuery = ltrim($get,'?');
			$query = '?' . $this->urlUnsafe($cleanQuery) ? urlencode($cleanQuery) : $cleanQuery;
		} else {
			$query = '';
		}

		$fullUrl = $this->_serviceUrl . $this->_actionUrl . $query;

		$options = $this->_curlConfig;
		$options[CURLOPT_URL] = $fullUrl;

		if(is_array($post) && count($post) > 0){
			$options[CURLOPT_POSTFIELDS] = $post;
			//#TODO the path reported by this client for any sent files will be fully qualified. if the server is too stupid to handle this, a work around will be needed, possibly chdir...
		} else if (!is_array($post) && '' != trim($post)) {
			$options[CURLOPT_POST] = true;
			if ($postContentType === self::UNENCODED_POST_DATA) {
				$post = urlencode($post);
			}
			$options[CURLOPT_POSTFIELDS] = $post;
			if ('' != $postContentType) {
				$options[CURLOPT_HTTPHEADER] = array('Content-type: ' . $postContentType);
			}
		}
		if ($this->_serviceAuthenticate){
			$username = $this->_serviceUser;
			$password = $this->_servicePassword;
			$options[CURLOPT_USERPWD] = "{$username}:{$password}";
		}
		curl_setopt_array($curl, $options);
		if ($this->_antiqueServerMode) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));
		}
		try {
			$result = curl_exec($curl);
			$resultHeadEnd = strpos($result,"\r\n\r\n");
			$resultHead = (
			$resultHeadEnd !== false
				? substr($result, 0, $resultHeadEnd + 2)
				: $result
			);
			$resultBody = (
			$resultHeadEnd !== false
				? substr($result, $resultHeadEnd + 4)
				: ''
			);
			$this->_lastError = curl_error($curl);
			$resultInfo = curl_getinfo($curl);
			$this->_lastStatus = $resultInfo['http_code'];
		} catch (Exception $e){
			$this->_lastError = $e->getMessage();
			$this->_lastStatus = 'EXCEPTION';
			$return = array(
				'url' => $fullUrl,
				'status' => 500,
				'error' => $this->_lastError,
				'raw' => '',
				'length' => 0,
				'type' => ''
			);
			if (Saf_Debug::isEnabled()) {
				$return['stack'] = $e->getTrace();
			}
			$this->_lastStatus = $return['status'];
			return $return;
		}
		curl_close($curl);
		$return = array(
			'url' => $fullUrl,
			'status' => $resultInfo['http_code'],
			'length' => $resultInfo['download_content_length'],
			'type' => $resultInfo['content_type'],
			'redirectCount' => $resultInfo['redirect_count'],
			'sentHeaders' => (
			array_key_exists('request_header',$resultInfo)
				? $resultInfo['request_header']
				: ''
			),
			'receivedHeaders' => $resultHead,
			'raw' => $resultBody
		);
		if ($fullUrl != $resultInfo['url']) {
			$return['effectiveUrl'] = $resultInfo['url'];
		}
		if (0 != $resultInfo['ssl_verify_result']) {
			$return['ssl_error_code'] = $return['ssl_error_code'] . ' ' . self::$_sslCodes[$resultInfo['ssl_error_code']];
		}
		return $return;
	}

	public function enableAntiqueMode()
	{
		$this->_antiqueServerMode = 1;
		return $this;
	}

	public function disableAntiqueMode()
	{
		$this->_antiqueServerMode = 0;
		return $this;
	}

	public static function flattenPostArray($array, $prefix='')
	{
		$flatArray = array();
		foreach($array as $key=>$value) {
			if (is_array($value)) { //#TODO handle iterable objects, other objects...
				$flatArray = array_merge($flatArray,self::flattenPostArray($value, $key . '[]'));
			} else {
				$flatArray[$prefix.$key] = $value;
			}
		}
		return $flatArray;
	}
}