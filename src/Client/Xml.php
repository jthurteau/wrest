<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Client for XML HTTP Based Services
 */

namespace Saf\Client;

use Saf\Debug;
use Saf\Hash;

use Saf\Client\Http;
use Saf\Client\Backend;

use Saf\Exception\Upstream;
use Saf\Exception\BadGateway;
use Saf\Exception\GatewayTimeout;

class Xml extends Backend
{
	public const PARSE_PATTERN_NONE = 0;
	public const PARSE_PATTERN_SINGLETON = 1;
	
	public const KEY_DEFAULT = 'ID'; #TODO consilidate with Ems\Api\Client
	public const KEY_ITERATIVE = 0; #TODO consilidate with Ems\Api\Client
	public const KEY_SINGLETON = -1; #TODO consilidate with Ems\Api\Client

	public const MIMETYPE_SOAP = 'application/soap+xml; charset=utf-8';
	public const MIMETYPE_XML = 'text/xml; charset=utf-8';

	protected $contentType = 'application/soap+xml; charset=utf-8'; //'text/xml; charset=utf-8'

	protected $host = ''; 
	protected $server = null;
	protected $path = '';

	protected $_client = NULL;
	protected $_protocol = 'https';
	protected $_timeout = 5;
	protected $_connectionTimeout = 2;

	public function __construct(array $options)
	{
		if (key_exists('url', $options)) {
			self::setUrl($options['url']);
			unset($options['url']);
		}
		if (is_null($this->host) && !key_exists('host', $options)) {
			throw new \Exception('Xml Client Requires a hostname.');
		} elseif (key_exists('host', $options)) {
			$this->host = $options['host'];
		}
		// if (is_null()) {

		// }
		$this->path = 
			key_exists('url', $options)
			? $options['url']
			: '';
		$this->_server = "{$this->protocol}://{$this->_host}";
		$this->_client = new Http(array(
			'url' => $this->_server . $this->_url,
			'headers' => array(
				//"POST {$this->_url} HTTP/1.1",
				"Host: {$this->_host}"
			),
			'loosyGoosySecurity' => TRUE
			//#TODO #2.0.0 doesn't follow redirects to https
		));
		if (
			key_exists('timeout', $options)
			&& (int)$options['timeout']
		) {
			$this->_timeout = (int)$options['timeout'];
		}
		if (
			key_exists('connectiontimeout', $options)
			&& (int)$options['connectiontimeout']
		) {
			$this->_connectionTimeout = (int)$options['connectiontimeout'];
		}
		$this->_client->setTimeout($this->_timeout);
		$this->_client->setConnectionTimeout($this->_connectionTimeout);
		
		$this->client->pickup();
	}
	
	public function setUrl(string $url)
	{
		#TODO post
	}

	public function enableClientDebug()
	{
		$this->client->enableDebug();
	}

	public function disableClientDebug()
	{
		$this->client->enableDebug(false);
	}	
	
	public function send($message){
		$processedInput = $message;
		$length = strlen($processedInput);
		$this->_client->addTempHeader("Content-Length: {$length}");
		return $this->_client->go(NULL, $processedInput, self::$contentType);
	}
	
	private function _simpleSend($call, $keys = NULL, $idKey = self::KEY_DEFAULT, $params = NULL)
	{
		if (is_null($idKey)) {
			$idKey = self::KEY_DEFAULT;
		}
		$currentKey =
			$idKey === self::KEY_ITERATIVE
				|| $idKey === self::KEY_SINGLETON
			? 0
			: $idKey;
		$response = array();
		$message = "";//new Saf_Message_Template($call);
		throw new \Exception('XML Clienc Simple Send not implemented');
		$messageParams = (is_null($params))? array(): $params;
		$dereferenceConfig = array(
				'params' => $messageParams
		);
		$dereferencedMessage = $message->get($dereferenceConfig);
		$rawResponse =  $this->send($dereferencedMessage);
		$finalPattern = 
			$idKey === self::KEY_SINGLETON
			? self::PARSE_PATTERN_SINGLETON
			: self::PARSE_PATTERN_NONE;
		$payload = $this->parseResponse($rawResponse, $finalPattern);
		if ($payload) {
			if ($finalPattern == self::PARSE_PATTERN_SINGLETON) {
				$payload  = array($payload);
			}
			foreach ($payload as $item) {
				if (
					(
						is_array($item) 
						|| !$keys
					) && (
						$idKey === self::KEY_SINGLETON
						|| $idKey === self::KEY_ITERATIVE
						|| array_key_exists($currentKey, $item) 
					)
				) {
					$itemKey = 
						$idKey === self::KEY_SINGLETON
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
			$finalPattern == self::PARSE_PATTERN_SINGLETON
			? (array_key_exists(0, $response) ? $response[0] : NULL)
			: $response;
	}
	
	public function parseResponse($rawResponseArray, $finalPattern = self::PARSE_PATTERN_NONE, $levelsDeep = 2)
	{
		if (is_null($finalPattern)) {
			$finalPattern = self::PARSE_PATTERN_NONE;
		}
		if (!array_key_exists('failedConnectionInfo', $rawResponseArray)) {
			if ($rawResponseArray['status'] > 200) {
				$rawFail = \Saf\Debug::stringR($rawResponseArray['failedConnectionInfo']);
				$prev = \Saf\Debug::isEnabled() ? new \Exception(htmlentities($rawFail)) : NULL;
				throw new BadGateway('The scheduling system failed. ', $rawResponseArray['status'], $prev);
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
				$parsedData = Hash::arrayMap($data);
				if (is_array($parsedData) && array_key_exists('Error', $parsedData)){
					if (is_array($parsedData['Error']) && array_key_exists('Message', $parsedData['Error'])) {
						$message = $parsedData['Error']['Message'];
						$userMessage =
							\Saf\Debug::isEnabled()
							? $message
							: 'Server returned an error message that has been logged';
						//#TODO #1.1.0 decide how to handle error logging
						throw new Upstream($message, 0);
					} else {
						\Saf\Debug::outData(array("XML Client Error Message "  => $parsedData['Error']));
						throw new Upstream('Server returned error with no message', 0);
					}
				}
				return (
					$parsedData
					? (
						$finalPattern == self::PARSE_PATTERN_NONE
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
				throw new \Exception('Unable to parse response XML', 0, \Saf\Debug::isEnabled() ? new \Exception($libXmlErrors) : NULL);
			}
		} else {
            $rawFail = \Saf\Debug::stringR($rawResponseArray['failedConnectionInfo']);
			if ($rawResponseArray['status'] == 0) {
				if ($rawResponseArray['failedConnectionInfo']['connect_time'] > $this->_client->getConnectionTimeout()) {
					throw new GatewayTimeout('Connection to the remote system timed out.');
				} else if ($rawResponseArray['failedConnectionInfo']['total_time'] > $this->_client->getTimeout()) {
					throw new GatewayTimeout('Response from the remote system timed out.');
				}

				$prev = new \Exception(htmlentities($rawFail));
				throw new BadGateway('Unable to contact the remote system.', $rawResponseArray['status'], $prev);
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
				\Saf\Debug::isEnabled()
				? new \Exception(
					'RAW_FAIL ' . htmlentities($rawFail)
					. '<br/>' 
					. ($rawRequest ? (htmlentities($rawRequest) . '<br/>') : '' )
					. ('RAW_RESPONSE ' . htmlentities(htmlentities($rawResponseArray['raw'])))
				) : NULL;
			throw new BadGateway('Communication with the remote system failed.', $rawResponseArray['status'], $prev);
		}
	}
	
	protected function _getMatching($ids, $data) #TODO this is implemented in Hash::match
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

	public function getRemote()
	{
		return $this->path;
	}

}