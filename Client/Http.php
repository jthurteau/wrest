<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base Client for Http Based Services

*******************************************************************************/

class Saf_Client_Http{
	
	const UNENCODED_POST_DATA = -1;
	
	protected $_url = '';
	protected $_actionUrl = '';
	protected $_query = '';
	protected $_postData = NULL;
	protected $_headers = array();
	protected $_tempHeaders = array();
	protected $_user = '';
	protected $_password = '';
	protected $_authenticate = false;
	protected $_lastStatus = '';
	protected $_lastError = '';
	protected $_lastResult = NULL;
	protected $_debugEnabled = FALSE;
	protected $_connection = NULL;
	protected $_manualCookies = NULL;
	protected $_curlConfig = array(
		CURLOPT_HEADER => TRUE,
		CURLINFO_HEADER_OUT => TRUE,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_CONNECTTIMEOUT => 2,
		CURLOPT_TIMEOUT => 10
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
	protected static $_httpCodes = array(
		200 => 'OK',
		201 => 'OK_CREATED',
		202 => 'OK_ACCEPTED',
		203 => 'OK_NONAUTHINFO',
		204 => 'OK_NOCONTENT',
		205 => 'OK_RESETCONTENT',
		300 => 'REDIRECT_MULTIPLE',
		301 => 'REDIRECT_PERMANENT',
		302 => 'REDIRECT_FOUND',
		303 => 'REDIRECT_OTHER',
		304 => 'REDIRECT_NOTMODIFIED',
		307 => 'REDIRECT_TEMPORARY',
		400 => 'NOPE_BADREQUEST',
		401 => 'NOPE_NOTAUTH',
		403 => 'NOPE_FORBIDDEN',
		404 => 'NOPE_NOTFOUND',
		405 => 'NOPE_BADMETHOD',
		406 => 'NOPE_NOTACCEPTABLE',
		408 => 'NOPE_REQUESTTIMEOUT',
		409 => 'NOPE_CONFLICT',
		410 => 'NOPE_GONE',
		412 => 'NOPE_PRECONDITION',
		413 => 'NOPE_ENTITYSIZE',
		415 => 'NOPE_BADMEDIA',
		417 => 'NOPE_EXPECTATION',
		500 => 'ERROR',
		501 => 'ERROR_NOTIMPLEMENTED',
		502 => 'ERROR_BADGATEWAY',
		503 => 'ERROR_UNAVAILABLE',
		504 => 'ERROR_GATEWAYTIMEOUT'
	);
	protected $_antiqueServerMode = FALSE;
	
	public function __construct($urlOrConfig = '')
	{
		$this->setProperty($urlOrConfig);
	}
	
	public function enableDebug($mode = TRUE)
	{
		$this->_debugEnabled = $mode;
	}
	
	public function getError()
	{
		return 'Status: ' . $this->_lastStatus . (
			'' != $this->_lastError
			? ' Error:' . $this->_lastError
			: ' No Error'
		);
	}
	
	public function getLastResult()
	{
		return $this->_lastResult;
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
	
	public function setProperty($urlOrConfig, $value = NULL)
	{
		if (!is_array($urlOrConfig) && is_null($value)) {
			$this->_url = $urlOrConfig; 
		} else {
			if (!is_null($value)) {
				$urlOrConfig = array($urlOrConfig => $value);
			}
			if (array_key_exists('url', $urlOrConfig)) {
				$this->_url = $urlOrConfig['url'];
				unset($urlOrConfig['url']);
			}
			if(array_key_exists('user', $urlOrConfig)) {
				$this->_user = $urlOrConfig['user'];
				$this->_authenticate = true;
				unset($urlOrConfig['user']);
			}
			if(array_key_exists('password', $urlOrConfig)) {
				$this->_password = $urlOrConfig['password'];
				$this->_authenticate = true;
				unset($urlOrConfig['password']);
			}
			if (array_key_exists('loosyGoosySecurity', $urlOrConfig) && $urlOrConfig['loosyGoosySecurity']) {
				$this->_curlConfig[CURLOPT_SSL_VERIFYPEER] = FALSE;
				$this->_curlConfig[CURLOPT_SSL_VERIFYHOST] = FALSE;
				unset($urlOrConfig['loosyGoosySecurity']);
			}		
			if(array_key_exists('headers', $urlOrConfig)) {
				$this->mergeHeaders($urlOrConfig['headers']);
				unset($urlOrConfig['headers']);
			}			
			if(array_key_exists('CURLOPT_HTTPHEADER', $urlOrConfig)) {
				$this->mergeHeaders($urlOrConfig['CURLOPT_HTTPHEADER']);
				unset($urlOrConfig['CURLOPT_HTTPHEADER']);
			}
			foreach($urlOrConfig as $key=>$value) {
				if(defined($key)){
					$this->_curlConfig[constant($key)] = $value; //#TODO #1.1.0 these are not yet being pulled on go()
				} else {
					throw new Exception('Unrecognized Curl Option in Http Client Config');
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
	
	public function unsetProperty($field, $value)
	{
		
	}
	
	public function pickup(){
		if ($this->_connection) {
			$this->_putdown(); //#TODO #2.0.0 for now no connection pool
		}
		$this->_connection = curl_init();
		curl_setopt_array($this->_connection, $this->_curlConfig);
	}
	
	public function putdown(){
		if ($this->_connection) {
			curl_close($this->_connection);
		}
		$this->_connection = NULL;
	}

	public function resource($url, $get = array(), $post = array(), $postContentType = '')
	{
		$currentURL = $this->_actionUrl;
		try {
			$this->_actionUrl .= $url;
			$result = $this->go($get, $post, $postContentType);
			$this->_actionUrl = $currentURL;
			return $result;
		} catch (Exception $e) {
			$this->_actionUrl = $currentURL;
			throw $e;
		}
	}

	public function go($get = array(), $post=array(), $postContentType = '')
	{
		if ('' == trim($this->_url)) {
			throw new Exception('Must specify a url before using the Http Client.');
		}
		$persist = $this->_connection;
		if (!$persist) {
			$this->pickup();
		}
		if(is_array($get)){
			$query = $this->buildQuery($get);
		} else if (!is_null($get) && '' != trim($get)) {
			$cleanQuery = ltrim($get,'?');
			$query = '?' . Saf_UrlRewrite::makeUrlSafe($cleanQuery);
		} else {
			$query = '';
		}
		$headers = array_merge($this->_headers, $this->_tempHeaders);
		$this->clearTempHeaders();
		$fullUrl = $this->_url . $this->_actionUrl . $query;		
		$options = array();
		$options[CURLOPT_URL] = $fullUrl;
		$debugPost = '';
		$resultInfo = NULL;
		if(is_array($post) && count($post) > 0){
			$debugPost = json_encode($debugPost, JSON_FORCE_OBJECT);
			$options[CURLOPT_POSTFIELDS] = $post; 
			//#TODO #2.0.0 the path reported by this client for any sent files will be fully qualified. if the server is too stupid to handle this, a work around will be needed, possibly chdir...
		} else if (!is_array($post) && '' != trim($post)) {
			$debugPost = $post;
			$options[CURLOPT_POST] = TRUE;
			if ($postContentType === self::UNENCODED_POST_DATA) {
				$post = urlencode($post);
			}
			$options[CURLOPT_POSTFIELDS] = $post;
			if ('' != $postContentType && $postContentType !== self::UNENCODED_POST_DATA) {
				$headers[] = 'Content-type: ' . $postContentType;
			}
		} else { #TODO #2.0.0 make sure switching back to GET mode when persisting works properly
			if (!array_key_exists(CURLOPT_POST, $options)) {
Saf_Debug::out('switching back');
				$options[CURLOPT_POST] = FALSE; //or unset?
			} else {
Saf_Debug::out('not switching');
			}

		}
		if ($this->_authenticate){
			$username = $this->_user;
			$password =  $this->_password;
			$options[CURLOPT_USERPWD] = "{$username}:{$password}";
		}
		$options[CURLOPT_HTTPHEADER] = $headers;
		if ($this->_antiqueServerMode) {
			$options[CURLOPT_HTTPHEADER] = array('Expect:');
		}
		curl_setopt_array($this->_connection, $options);
		if (!is_null($this->_manualCookies)) {
			curl_setopt($this->_connection, CURLOPT_COOKIE, $this->_manualCookies);
		}
		try {
			$result = curl_exec($this->_connection);
			$resultHead = '';
			$resultRest = $result;
			$resultHeadEnd = strpos($resultRest,"\r\n\r\n");
//			$count = 0;
//			$head = str_replace("\r\n", "\\r\\n<br/>", $resultHead);
//			$body = str_replace("\r\n", "\\r\\n<br/>", $resultRest);
			while ($resultHeadEnd !== FALSE){
//				$count++;
				$resultHead .= (
					substr($resultRest, 0, $resultHeadEnd + 4)
				);
				$resultRest = substr($resultRest, $resultHeadEnd + 4);
				$resultHeadEnd = strpos($resultRest , "\r\n\r\n");
// 				if (
// 					strpos($resultRest, 'HTTP') === 0
// 					|| strpos($resultRest, '\r\n\r\n') !== FALSE
// 				) {
					
// 				} else {
// 					$resultHeadEnd = FALSE;
// 				}
// 				$resultHeadEnd = 
// 					(
// 						strpos($resultRest,'HTTP') !== 0
// 					//	&& strpos($resultRest,'Content-Length:') !== 0
// 					) ? FALSE
// 					: strpos($resultRest,"\r\n\r\n");
// 				if (strpos($resultRest,'\r\n') === 0) {
// 					$resultHead .= (
// 							substr($result, 0, 2)
// 					);
// 					$resultRest = substr($result, 2);
// 					$resultHeadEnd +=2;
// 				}
				$head = str_replace("\r\n", "\\r\\n<br/>", $resultHead);
				$body = str_replace("\r\n", "\\r\\n<br/>", $resultRest);
//Saf_Debug::outData(array($resultHeadEnd,$head,$body));
			}
//			if ($count > 2) { 
//				die('server sent too many continues'); //#TODO #1.1.0
//			}
			if ($this->_debugEnabled) {
				Saf_Debug::outData(array(
					$fullUrl,
					htmlentities($debugPost),
					htmlentities($head), 
					htmlentities($body)
				));
			}
			$resultBody = $resultRest;
			$this->_lastError = curl_error($this->_connection);
			$resultInfo = curl_getinfo($this->_connection);
			$this->_lastResult = array(
				'response' => $result,
				'status' => $resultInfo,
				'error' => $this->_lastError
			);
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
		if (is_null($resultInfo) && !is_null($this->_connection)) {
			$resultInfo = curl_getinfo($this->_connection);
		}
		if (!$persist) {
			$this->putdown();
		}
		$status = (int)$resultInfo['http_code'];
		$return  = array(
			'url' => $fullUrl,
			'status' => $status,
			'status_label' => (
				array_key_exists($status, self::$_httpCodes)
				? self::$_httpCodes[$status]
				: 'UNKNOWN'
			),
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
		if ($resultInfo['size_upload'] < $resultInfo['upload_content_length']) {
			$return['up'] = floor($resultInfo['size_upload'] /  $resultInfo['upload_content_length'] * 100);
		} if ($resultInfo['size_download'] < $resultInfo['download_content_length']) {
			$return['down'] = floor($resultInfo['size_download'] /  $resultInfo['download_content_length'] * 100);
		}
		if ($fullUrl != $resultInfo['url']) {
			$return['effectiveUrl'] = $resultInfo['url'];
		}
		if (array_key_exists('ssl_verify_result', $resultInfo) && 0 != $resultInfo['ssl_verify_result']) {
			$return['ssl_error_code'] = $resultInfo['ssl_verify_result']
			. (
					array_key_exists($resultInfo['ssl_verify_result'], self::$_sslCodes)
					? (' ' . self::$_sslCodes[$resultInfo['ssl_verify_result']])
					: ' unknown SSL connection error'
			);
		}
		if ($status < 200 || $status >= 300) {
			$return['failedConnectionInfo'] = $resultInfo;
		}
		if ($post && Saf_Debug::isEnabled()) { //#TODO #2.0.0 make this more configurable
			if (is_array($post) && count($post) > 0) {
				ob_start();
				print_r($post);
				$rawRequest = ob_get_contents();
				ob_end_clean();
				$return['request'] = $rawRequest;
			} else {
				$return['request'] = $post;
			}
		}
		return $return;
	}
	
	public static function get($url, $query = array())
	{
		
	}
	
	public static function post($url, $postData, $postType)
	{
		
	}
	
	public function mergeHeaders($headers)
	{
		foreach($headers as $header) {
			$this->_headers[] = $header;
		}
	}
	
	public function addHeader($header)
	{
		$this->_headers[] = $header;
	}
	
	public function clearHeaders()
	{
		$this->_headers[] = array();
		$this->clearTempHeaders();
	}
	
	public function addTempHeader($header)
	{
		$this->_tempHeaders[] = $header;
	}
	
	public function clearTempHeaders()
	{
		$this->_tempHeaders = array();
	}
	
	public function setTimeout($timeInSeconds)
	{
		$this->_curlConfig[CURLOPT_TIMEOUT] = $timeInSeconds;
	}
	
	public function getTimeout()
	{
		return $this->_curlConfig[CURLOPT_TIMEOUT];
	}
	
	public function setConnectionTimeout($timeInSeconds)
	{
		$this->_curlConfig[CURLOPT_CONNECTTIMEOUT] = $timeInSeconds;
	}
	
	public function getConnectionTimeout()
	{
		return $this->_curlConfig[CURLOPT_CONNECTTIMEOUT];
	}
	
	public function enableAntiqueMode()
	{
		$this->_antiqueServerMode = TRUE;
		return $this;
	}
	
	public function disableAntiqueMode()
	{
		$this->_antiqueServerMode = FALSE;
		return $this;
	}

	public function getConnection()
	{
		return $this->_connection;
	}

	public function setManualCookies($array)
	{
		$this->_manualCookies = $array;
	}
}
