<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

based on source
by https://stackoverflow.com/users/4622767/fra
from https://stackoverflow.com/questions/16592142/retrieve-time-from-ntp-server-via-php

Client for NTP (time server) interaction

*******************************************************************************/


class Saf_Client_Ntp{

	const PACKET_LENGTH = 48; //#NOTE Time server port was 49;
	//const SEND_STRING = "\n"; //#NOTE for old Time server format
	//const FORMAT_NTP = 1;
	//const FORMAT_UNIX_TS = 0;
	//const HEX_CONV = '7fffffff';  //#NOTE for old Time server format
	const EPOCH_CONV = 2208988800;
	const BIT_MAX = 4294967296;
	const LEAP_IND = '00'; # no warning
	const VERSION = '011'; # Version 3
	const ASSOC = '011'; # 3 = client
	const PACK_LOOP = 40;
	const MAX_TIMEOUT = 10;

	protected static $_defaultPort = 123;//#NOTE was 37;
	protected static $_defaultTimeout = 6;

	protected $_hosts = array();
	protected $_sock = NULL;
	protected $_lastErrNo = NULL;
	protected $_lastErrStr = NULL;
	protected $_timeout = NULL;
	protected $_errors = array();
	
	public function __construct($hostnameOrConfig = NULL)
	{
		$this->_timeout = self::$_defaultTimeout;
		if(!is_null($hostnameOrConfig)) {
			if (!is_array($hostnameOrConfig)) {
				$this->_addHosts($hostnameOrConfig);
			} else {
				foreach($hostnameOrConfig as $key => $value) {
					switch ($key) {
						case 'server':
							$this->_addHosts($value);
							break;
						case 'timeout':
							$this->_timeout = min($value,self::MAX_TIMEOUT);
							break;
					}
				}
			}
		}
	}
	
	protected function _addHosts($hosts)
	{
		if (!is_array($hosts)) {
			$hosts = array($hosts);
		}
		foreach ($hosts as $hostname) {
			$parts = explode(':', $hostname);
			$hostPart = $parts[0];
			$port = array_key_exists(1, $parts) ? $parts[1] : self::$_defaultPort;
			if ($hostPart) {
				$this->_hosts["udp://{$hostPart}"] = $port;
			}
		}
	}

	public function get() //$format = self::FORMAT_UNIX_TS)
	{
		foreach($this->_hosts as $hostname => $port) {
			$this->_sock = fsockopen($hostname, $port, $this->_lastErrNo, $this->_lastErrStr, $this->_timeout);
			if ($this->_sock) {
//				fputs($this->_sock, self::SEND_STRING);
//        		$time = fread($this->_sock, self::PACKET_LENGTH);
//        		fclose($this->_sock);
//        		return $this->parse($time, $format);
//# NOTE above is older "Time" protocol
				$transaction = $this->prepPacket();
				if (fwrite($this->_sock, $transaction['send'])) {
					stream_set_timeout($this->_sock, $this->_timeout);
					$transaction['receive'] = fread($this->_sock, self::PACKET_LENGTH);
					$transaction['end'] = microtime(true);
					$transaction['duration'] = $transaction['end'] - $transaction['start'];
					$transaction['remaining'] = $this->_timeout - ($transaction['duration']);
				}
				fclose($this->_sock);
				if (!array_key_exists('receive', $transaction)) {
					$this->_errors[] = array('code' => -2, 'message' => 'no data sent', 'raw' => $transaction);
				} else if (!$transaction['receive']) {
					$this->_errors[] = array('code' => -1, 'message' => 'no response, possible timeout', 'raw' => $transaction);
				} else {
					return $this->processPacket($transaction);
				}
			} else {
				$this->_errors[] = array('code' => $this->_lastErrNo, 'message' => $this->_lastErrStr);
			}
		}
		return NULL;
	}

	public function prepPacket()
	{
		$return = array(
			'send' => chr(bindec(self::LEAP_IND . self::VERSION . self::ASSOC))
		);
		for ($i = 1; $i < self::PACK_LOOP; $i++) {
			$return['send'] .= chr(0x0);
		}
		$localTimeParts = explode(' ', microtime()); //#NOTE returns 'msec sec'
		$return['start'] = microtime(true);
		$originateSeconds = $localTimeParts[1] + self::EPOCH_CONV;
		$originateFraction = round($localTimeParts[0] * self::BIT_MAX);
//#TODO use padding and not sprintf?
		$paddedOrigFrac = sprintf('%010d', $originateFraction);
		$return['send'] .= pack('N', $originateSeconds) . pack('N',$paddedOrigFrac);
//		print_r(array(
//		$localTimeParts,
//		$return['start'],
//		$originateSeconds, $originateFraction,
//		$paddedOrigFrac,
//		pack('N', $originateSeconds),pack('N', $paddedOrigFrac)
//		)); die;
		return $return;
	}

	public function processPacket($transaction)
	{
	 	$unpackUnSignedLong = unpack('N12', $transaction['receive']);
	 	//$unpackAcsii = unpack('C12', $transaction['receive']);
  		//$originateSeconds = sprintf('%u', $unpackUnSignedLong[7]) - self::EPOCH_CONV;
  		//$originateFraction = sprintf('%u', $unpackUnSignedLong[8]) / self::BIT_MAX;
  		$receivedSeconds = sprintf('%u', $unpackUnSignedLong[9]) - self::EPOCH_CONV;
  		$receivedFraction = sprintf('%u', $unpackUnSignedLong[10]) / self::BIT_MAX;
  		$transmittedSeconds = sprintf('%u', $unpackUnSignedLong[11]) - self::EPOCH_CONV;
  		$transmittedFraction = sprintf('%u', $unpackUnSignedLong[12]) / self::BIT_MAX;

		//$originate = $originateSeconds + $originateFraction;
		$received = $receivedSeconds + $receivedFraction;
		$transmitted = $transmittedSeconds + $transmittedFraction;

 	 	//$header =  base_convert($unpackAcsii[1], 10, 2);
		//$paddedHeader = sprintf('%08d', $header);
		//$version = bindec(substr($paddedHeader, -6, 3));
		//$mode = bindec(substr($paddedHeader, -3)); //#NOTE: last 3
		//$stratum = bindec(base_convert($unpackAcsii[2], 10, 2));

		// source indicates this is based on symmetrical delay and that fixed point would be more accurate
		$transaction['delay'] = (($transaction['end'] - $transaction['start']) / 2) - ($transmitted - $received);

		$transaction['remote'] = $transmitted - $transaction['delay'];
		$transaction['local'] = $transaction['end'];
		return $transaction;
	}

	public function getErrors()
	{
		return $this->_errors;
	}

//	public function parse($ntpBinary, $nativeFormat)
//	{
//		$hex = bin2hex($ntpBinary);
////		$dec = abs(hexdec(self::HEX_CONV) - hexdec($hex) - hexdec(self::HEX_CONV));
////# NOTE above is older "Time" protocol
//		return $nativeFormat ? $dec : ($dec - self::EPOCH_CONV);
//	}

}