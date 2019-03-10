<?php
class Saf_Jtemp_Cache
{
	protected static $_cacheBasePath = 'application/cache/';
	protected $_cacheFilePath = ''; //data/wolftechGenCache2.pser';

	public function __construct($filePath, $options = array()){
		$this->_cacheFilePath = self::$_cacheBasePath . $filePath;
	}
	
	public function getCache($freshness = NULL)
	{
		if (!file_exists($this->_cacheFilePath)) {
			file_put_contents($this->_cacheFilePath, serialize(array(
				'payload' => NULL,
				'timestamp' => time()
			)));
		}
		$data = unserialize(file_get_contents($this->_cacheFilePath));
		$payload = is_array($data) && array_key_exists('payload', $data) ? $data['payload'] : NULL;
		$time = is_array($data) && array_key_exists('timestamp', $data) ? $data['timestamp'] : NULL;
		return is_null($freshness) || $freshness <= $time ? $payload : NULL;
	}

	public function storeCache($data)
	{
		file_put_contents($this->_cacheFilePath, serialize(array(
			'payload' => $data,
			'timestamp' => time()
		)));
	}


}