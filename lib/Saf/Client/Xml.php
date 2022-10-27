<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

require_once(LIBRARY_PATH . '/Saf/Client/Http.php');

class Saf_Client_Xml //#TODO #2.0.0 not currently used needs work
{
	const PARSE_PATTERN_NONE = 0;
	const PARSE_PATTERN_SINGLETON = 1;
	
	const KEY_DEFAULT = 'ID';
	const KEY_ITERATIVE = 0;
	const KEY_SINGLETON = -1;

	protected $_host = ''; 
	protected $_server = NULL;
	protected $_url = '';
	protected $_client = NULL;
	protected $_protocol = 'https';
	protected $_contentType = 'application/soap+xml; charset=utf-8'; //'text/xml; charset=utf-8'
	protected $_timeout = 5;
	protected $_connectionTimeout = 2;

	public function __construct($options = array())
	{
		if (
			!array_key_exists('host', $options)
		) {
			throw new Exception('Xml Client Requires a hostname.');
		}
		$this->_host = $options['host'];
		$this->_url = 
			array_key_exists('url', $options)
			? $options['url']
			: '';
		$this->_server = "{$this->protocol}://{$this->_host}";
		$this->_client = new Saf_Client_Http(array(
			'url' => $this->_server . $this->_url,
			'headers' => array(
				//"POST {$this->_url} HTTP/1.1",
				"Host: {$this->_host}"
			),
			'loosyGoosySecurity' => TRUE
			//#TODO #2.0.0 doesn't follow redirects to https
		));
		if (
			array_key_exists('timeout', $options)
			&& (int)$options['timeout']
		) {
			$this->_timeout = (int)$options['timeout'];
		}
		if (
			array_key_exists('connectiontimeout', $options)
			&& (int)$options['connectiontimeout']
		) {
			$this->_connectionTimeout = (int)$options['connectiontimeout'];
		}
		$this->_client->setTimeout($this->_timeout);
		$this->_client->setConnectionTimeout($this->_connectionTimeout);
		$this->_client->pickup();
	}
	
	public function enableClientDebug()
	{
		$this->_client->enableDebug();
	}

	public function disableClientDebug()
	{
		$this->_client->enableDebug(FALSE);
	}	
	
	public function send($message){
		$length = strlen($processedInput);
		$this->_client->addTempHeader("Content-Length: {$length}");
		return $this->_client->go(NULL, $processedInput, self::$_contentType);
	}
	
	private function _simpleSend($call, $keys = NULL, $idKey = self::API_KEY_DEFAULT, $params = NULL)
	{
		if (is_null($idKey)) {
			$idKey = self::API_KEY_DEFAULT;
		}
		$currentKey =
			$idKey === self::KEY_ITERATIVE
				|| $idKey === self::API_KEY_SINGLETON
			? 0
			: $idKey;
		$response = array();
		$message = new Saf_Message_Template($call);
		$messageParams = (is_null($params))? array(): $params;
		$dereferenceConfig = array(
				'params' => $messageParams
		);
		$dereferencedMessage = $message->get($dereferenceConfig);
		$rawResponse =  $this->send($dereferencedMessage);
		$finalPattern = 
			$idKey === self::API_KEY_SINGLETON
			? self::API_PARSE_PATTERN_SINGLETON
			: self::API_PARSE_PATTERN_NONE;
		$payload = $this->parseResponse($rawResponse, $finalPattern);
		if ($payload) {
			if ($finalPattern == self::API_PARSE_PATTERN_SINGLETON) {
				$payload  = array($payload);
			}
			foreach ($payload as $item) {
				if (
					(
						is_array($item) 
						|| !$keys
					) && (
						$idKey === self::API_KEY_SINGLETON
						|| $idKey === self::KEY_ITERATIVE
						|| array_key_exists($currentKey, $item) 
					)
				) {
					$itemKey = 
						$idKey === self::API_KEY_SINGLETON
							|| $idKey === self::KEY_ITERATIVE
						? $currentKey
						: $item[$currentKey];
					$response[$itemKey] = $keys ? array() : $item;
					if ($keys) {
						foreach($keys as $keyTransform => $key){
							$targetKey = is_numeric($keyTransform) ? $key : $keyTransform;
							$response[$itemKey][$targetKey] =
							array_key_exists($key, $item)
							? $item[$key]
							: NULL;
						}
					}
					if ($idKey === self::KEY_ITERATIVE) {
						$currentKey++;
					}
				}
			}
		}
		return 
			$finalPattern == self::API_PARSE_PATTERN_SINGLETON
			? (array_key_exists(0, $response) ? $response[0] : NULL)
			: $response;
	}
	
	public function parseResponse($rawResponseArray, $finalPattern = self::API_PARSE_PATTERN_NONE, $levelsDeep = 2)
	{
		if (is_null($finalPattern)) {
			$finalPattern = self::API_PARSE_PATTERN_NONE;
		}
		if (!array_key_exists('failedConnectionInfo', $rawResponseArray)) {
			if ($rawResponseArray['status'] > 200) {
				ob_start();
				print_r($rawResponseArray['failedConnectionInfo']);
				$rawFail = ob_get_contents();
				ob_end_clean();
				$prev = Saf_Debug::isEnabled() ? new Exception(htmlentities($rawFail)) : NULL;
				throw new Saf_Exception_BadGateway('The scheduling system failed. ', $rawResponseArray['status'], $prev);
			}
			$xmlResult = simplexml_load_string($rawResponseArray['raw'], 'SimpleXMLElement', 0, 'http://www.w3.org/2003/05/soap-envelope', FALSE);
			if ($xmlResult) {
				libxml_clear_errors(); //#TODO #2.0 implement memory checks from r-r/model/Ems/Api
				$envelope = $xmlResult->children('http://www.w3.org/2003/05/soap-envelope');
				$current = $envelope;
				for($i=0; $i<$levelsDeep; $i++) {
					$current = $current->children();
				}
				$payloadXml = (string)$current;
				$data = simplexml_load_string($payloadXml);
				$parsedData = Saf_Config::arrayMap($data);
				if (is_array($parsedData) && array_key_exists('Error', $parsedData)){
					if (is_array($parsedData['Error']) && array_key_exists('Message', $parsedData['Error'])) {
						$message = $parsedData['Error']['Message'];
						$userMessage =
							Saf_Debug::isEnabled()
							? $message
							: 'Server returned an error message that has been logged';
						//#TODO #1.1.0 decide how to handle error logging
						throw new Saf_Exception_Upstream($message, 0);
					} else {
						Saf_Debug::outData(array("XML Client Error Message "  => $parsedData['Error']));
						throw new Saf_Exception_Upstream('Server returned error with no message', 0);
					}
				}
				return (
					$parsedData
					? (
						$finalPattern == self::API_PARSE_PATTERN_NONE
						? $parsedData
						: current($parsedData)
					) : NULL	
				);
			} else {
				$head = str_replace("\r\n", "\\r\\n<br/>", $rawResponseArray['receivedHeaders']);
				$body = str_replace("\r\n", "\\r\\n<br/>", $rawResponseArray['raw']);
				$libXmlErrors = libxml_get_errors();
				$xmlErrors = array();
				$errorMap = array(
					LIBXML_ERR_WARNING => 'LIBXML_ERR_WARNING',
					LIBXML_ERR_ERROR => 'LIBXML_ERR_ERROR',
					LIBXML_ERR_FATAL => 'LIBXML_ERR_FATAL'
				);
				foreach($libXmlErrors as $error) {
					$xmlErrors[] = "{$error->level} {$error->code}"
						. ($error->file ? " in {$error->file}" : "") 
						." on line {$error->line},{$error->column}" 
						. ($error->message ? ": $error->message" : '');
				}
				$libXmlErrors = 'LIB_XML_ERRORS: <br/>' . implode('<br/>', $xmlErrors)
					. '<br/>BAD_XML: ' . htmlentities($rawResponseArray['raw'])
					. '<br/>SERVER_HEADERS: ' . htmlentities($head)
					. '<br/>SERVER_BODY: ' .  htmlentities($body);
				throw new Exception('Unable to parse response XML', 0, Saf_Debug::isEnabled() ? new Exception($libXmlErrors) : NULL);
			}
		} else {
			ob_start();
			print_r($rawResponseArray['failedConnectionInfo']);
			$rawFail = ob_get_contents();
			ob_end_clean();
			if ($rawResponseArray['status'] == 0) {
				if ($rawResponseArray['failedConnectionInfo']['connect_time'] > $this->_client->getConnectionTimeout()) {
					throw new Saf_Exception_GatewayTimeout('Connection to the remote system timed out.');
				} else if ($rawResponseArray['failedConnectionInfo']['total_time'] > $this->_client->getTimeout()) {
					throw new Saf_Exception_GatewayTimeout('Response from the remote system timed out.');
				}

				$prev = new Exception(htmlentities($rawFail));
				throw new Saf_Exception_BadGateway('Unable to contact the remote system.', $rawResponseArray['status'], $prev);
			}
			$rawRequest = 
				array_key_exists('request', $rawResponseArray) 
				? (
					'RAW_REQUEST ' . (
						array_key_exists('request', $rawResponseArray)
						? htmlentities($rawResponseArray['request'])
						: ''
					)
				) : '';
			$prev = 
				Saf_Debug::isEnabled()
				? new Exception(
					'RAW_FAIL ' . htmlentities($rawFail)
					. '<br/>' 
					. ($rawRequest ? (htmlentities($rawRequest) . '<br/>') : '' )
					. ('RAW_RESPONSE ' . htmlentities(htmlentities($rawResponseArray['raw'])))
				) : NULL;
			throw new Saf_Exception_BadGateway('Communication with the remote system failed.', $rawResponseArray['status'], $prev);
		}
	}
	
	protected function _getMatching($ids, $data)
	{
		if (is_null($ids)) {
			return $data;
		}
		$returnArray = is_array($ids);
		$ids = is_array($ids) ? $ids : array($ids);
		$results = array();
		foreach($ids as $id) {
			if (array_key_exists($id, $data)) {
				$results[$id] = $data[$id];
			}
		}
		return $returnArray ? $results : current($results);
	}

}