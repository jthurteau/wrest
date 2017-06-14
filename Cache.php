<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for data caching

*******************************************************************************/

class Saf_Cache {

	const DIR_MODE_DIRECT_ONLY = 0;
	const DIR_MODE_RECURSIVE_FLAT = 1;
	const DIR_MODE_RECURSIZE_NESTED = 2;
	
	const STAMP_MODE_REPLACE = 0;
	const STAMP_MODE_AVG = 1;
	const STAMP_MODE_KEEP = 2;
	
	protected static $_memory = array();
	protected static $_hashMemory = array();
	protected static $_path = '';
	protected static $_hashInit = NULL;
	protected static $_fuzzyExpires = 0;
	protected static $_fuzzyFactor = 1;
	
	public static function setPath($path)
	{
		self::$_path = realpath($path);
		if (!self::$_path) {
			throw new Exception('Invalid path specified for cache.');
		} else if (!file_exists(self::$_path)) {
			throw new Exception('Path specified for cache does not exist.');
		} else if (!is_writable(self::$_path)) {
			throw new Exception('Path specified for cache is not writable.');
		}
	}
	
	public static function getPath()
	{
		return self::$_path;
	}
	
	protected static function _initHash($what = NULL)
	{
		if (!self::$_path) {
			throw new Exception('Cannot initialize hash with no path set for cache.');
		}
		$baseHashPath = self::$_path . '/hash';
		if (is_null(self::$_hashInit)) {
			if (!file_exists($baseHashPath)) {
				if(!mkdir($baseHashPath)) {
					throw new Exception('Unable to create hash path.');
				}
			}
			if (!is_writable($baseHashPath)) {
				throw new Exception('Unable to write in hash path.');
			}
			self::$_hashInit = array();
		}
		if (!is_null($what)) {
			$hashStack = explode('/', $what);
			$nextWhat = array_shift($hashStack);
			$fullWhat = '';
			while($nextWhat) {
				$baseHashPath .= '/' . $nextWhat;
				$fullWhat = '' == $fullWhat ? $nextWhat : ($fullWhat . '/' . $nextWhat);
				$nextWhat = array_shift($hashStack);
				if ($nextWhat) {
					if (!file_exists($baseHashPath)) {
						if(!mkdir($baseHashPath)) {
							throw new Exception("Unable to create hash path for \"{$fullWhat}\".");
						}
					}
					if (!is_writable($baseHashPath)) {
						throw new Exception("Unable to write in hash path for \"{$fullWhat}\".");
					}
					self::$_hashInit[$fullWhat] = TRUE;
				}
			}
		}
	}

	public static function prepFile($file)
	{
		if (!self::$_path) {
			throw new Exception('Cannot prep cache file with no path set.');
		}
		$basePath = self::$_path;
		$fileParts = explode('/', $file);
		array_pop($fileParts);
		foreach($fileParts as $filePart) {
			$basePath .= "/{$filePart}";
			if (!file_exists($basePath)) {
				if(!mkdir($basePath)) {
					throw new Exception("Cannot prep cache file, unable to create path for \"{$basePath}\".");
				}
			} else if (!is_dir($basePath)) {
				throw new Exception("Cannot prep cache file, path {$basePath} exists as a non-directory.");
			} else if (!is_writable($basePath)) {
				throw new Exception("Cannot prep cache file, path {$basePath} is not writable.");
			}
		}
		return TRUE;
	}
	
	public static function dir($path, $recursive = self::DIR_MODE_DIRECT_ONLY)
	{
		$return = array();
		$currentPath = self::$_path . '/' . $path;
		if (is_dir($currentPath)) {
			foreach(scandir($currentPath) as $filepath) {
				if (!in_array($filepath, array('.', '..'))) {
					$currentFullPath = $currentPath . '/' . $filepath;
					if (
						is_dir($currentFullPath) 
						&& $recursive != self::DIR_MODE_DIRECT_ONLY
					) {
						$sub = self::dir($path . '/' . $filepath, $recursive);
						if ($recursive == self::DIR_MODE_RECURSIVE_FLAT) {
							foreach($sub as $filename) {
								$return[] = $filename;
							}
						} else {
							$return[$path] = $sub;
						}
					} else if (!is_dir($currentFullPath)) {
						$return[] = $path . '/' . $filepath;
					}
				}

			}
		}
		return $return;
	}
	
	public static function getRaw($file){
		$path = self::$_path . '/' . $file;
		$value = NULL;
		if (file_exists($path)) {
			$pointer = fopen($path, 'r');
			$fileLock = flock($pointer, LOCK_SH);
			if (!$fileLock) {
				Saf_Debug::out("read blocking {$file}");
				$fileLock = flock($pointer, LOCK_SH | LOCK_NB);
			}
			if ($fileLock) {
				$size = filesize($path);
				$contents =
					$size
					? fread($pointer, $size)
					: '';
				$value = json_decode($contents, TRUE);
			} else {
				Saf_Debug::out("unable to read {$file}");
			}
			flock($pointer, LOCK_UN);
			fclose($pointer);
		} else {
//			Saf_Debug::out("failed to read {$file}, it does not exist");
		}
		return $value;		
	}
	
	public static function getRawHash($file, $uname){
		$path = self::$_path . '/' . $file;
		$value = NULL;
		if (file_exists($path)) {
			$pointer = fopen($path, 'r');
			$fileLock = flock($pointer, LOCK_SH);
			if (!$fileLock) {
				Saf_Debug::out("read blocking {$file}");
				$fileLock = flock($pointer, LOCK_SH | LOCK_NB);
			}
			if ($fileLock) {
				$size = filesize($path);
				$contents =
					$size
						? fread($pointer, $size)
						: '';
				$hash = json_decode($contents, TRUE);
				if (is_array($hash) && array_key_exists($uname, $hash)) {
					$value = $hash[$uname];
				} else {
					Saf_Debug::out("hash {$file} does not have {$uname}", 'notice');
				}
			} else {
				Saf_Debug::out("unable to read {$file}");
			}
			flock($pointer, LOCK_UN);
			fclose($pointer);
		} else {
//			Saf_Debug::out("failed to read {$file}, it does not exist");
		}
		return $value;
	}
	
	public static function get($file, $minDate = NULL, $cacheInMemory = FALSE)
	{
		if (array_key_exists($file, self::$_memory)) {
			return self::$_memory[$file];
		}
		$payload = NULL;
		$contents = self::getRaw($file);
		if ( //#TODO consolidate this block with getHash
			$contents
			&& is_array($contents)
			&& array_key_exists('payload', $contents)
		) {
//Saf_Debug::outData(array('Cache', $minDate, array_key_exists('stamp', $contents) ? $contents['stamp'] : 'NONE' ), 'PROFILE');
			if (
				is_null($minDate)
				|| (
					array_key_exists('stamp', $contents)
					&& $contents['stamp'] >= $minDate
				)
			) {
				$payload = $contents['payload'];
				$stamp = array_key_exists('stamp', $contents) ? $contents['stamp'] : NULL;
//	Saf_Debug::out("loaded cached {$file} {$stamp}" . ($cache ? ', caching to memory' : ''));
				if ($cacheInMemory) {
					self::$_memory[$file] = $payload;
				}
			} else {
				$cacheDate = 
					array_key_exists('stamp', $contents)
					? $contents['stamp']
					: NULL;
				$now = time();
// Saf_Debug::outData(array('expired cache', $file, 
// 	'now' . date(Ems::EMS_DATE_TIME_FORMAT ,$now), 
// 	'accept' . date(Ems::EMS_DATE_TIME_FORMAT ,$minDate), 
// 	'cached' . date(Ems::EMS_DATE_TIME_FORMAT ,$cacheDate)
// ));
			}
		}
		return $payload;
	}
	
	public static function getHash($file, $uname, $minDate = NULL, $cache = FALSE)
	{
		if (
			array_key_exists($file, self::$_hashMemory)
			&& array_key_exists($uname, self::$_hashMemory[$file])
		) {
			return self::$_hashMemory[$file][$uname];
		}
		$payload = NULL;
		$contents = self::getRawHash($file, $uname);
		if ( //#TODO consolidate this block with get
				$contents
				&& is_array($contents)
				&& array_key_exists('payload', $contents)
		) {
			if (
				is_null($minDate)
				|| (
						array_key_exists('stamp', $contents)
						&& $contents['stamp'] >= $minDate
				)
			) {
				$payload = $contents['payload'];
				$stamp = array_key_exists('stamp', $contents) ? $contents['stamp'] : NULL;
//Saf_Debug::out("loaded cached hash {$file} {$uname} {$stamp}" . ($cache ? ', caching to memory' : ''));
				if ($cache) {
					if(!array_key_exists($file, self::$_hashMemory)) {
						self::$_hashMemory[$file] = array();
					}
					self::$_hashMemory[$file][$uname] = $payload;
				}
			} else {
				$cacheDate =
				array_key_exists('stamp', $contents)
				? $contents['stamp']
				: NULL;
				$now = time();
// Saf_Debug::outData(array('expired cache', $file, 
// 	'now    ' . date(Ems::EMS_DATE_TIME_FORMAT ,$now), 
// 	'accept ' . date(Ems::EMS_DATE_TIME_FORMAT ,$minDate), 
// 	'cached ' . date(Ems::EMS_DATE_TIME_FORMAT ,$cacheDate)
// ));
			}
		}
		return $payload;
	}

	public static function getHashTime($file, $uname)
	{
		$contents = self::getRawHash($file, $uname);
		if ( //#TODO consolidate this block with get
			$contents
			&& is_array($contents)
			&& array_key_exists('stamp', $contents)
		) {
			return $contents['stamp'];
		} else {
			return NULL;
		}
	}
	
	public static function analyze($file)
	{
		if (file_exists(self::$_path . '/' . $file)) {
			$data = stat(self::$_path . '/' . $file);
			if ($data) {
				return array(
					'uid' => $data['uid'],
					'gid' => $data['gid'],
					'size' => $data['size'],
					'atime' => $data['atime'],
					'mtime' => $data['mtime'],
					'ctime' => $data['ctime']
				);
			}
		} else {
			return NULL;
		}
		return array();
	}
	
	public static function fuzzyLoadHash($file, $name, $threshold, $default = NULL) {
		$rand = rand(0,ceil($threshold * self::$_fuzzyFactor));
		$now = time();
		$fuzzyFreshness = $now - ($threshold + $rand);
//Saf_Debug::outData(array('fuzzyLoad', $file, $now, $fuzzyFreshness));
		$payload = self::getHash($file, $name, $fuzzyFreshness, TRUE);
		if (is_null($payload)) {
			self::_increaseFuzz("{$file} {$name}");
		}
		return !is_null($payload) ? $payload : $default;
	}
	
	public static function fuzzyLoad($file, $threshold, $default = NULL) {
		$rand = rand(0, ceil($threshold * self::$_fuzzyFactor));
		$now = time();
		$fuzzyFreshness = $now - ($threshold + $rand);
//Saf_Debug::outData(array('fuzzyLoad', $file, $now, $fuzzyFreshness));
		$payload = self::get($file, $fuzzyFreshness, TRUE);
		if (is_null($payload)) {
			self::_increaseFuzz($file);
		} else {
//Saf_Debug::outData(array('Cache', "accepted cache for {$file}"), 'PROFILE');
		}
		return !is_null($payload) ? $payload : $default;
	}
	
	protected static function _increaseFuzz($what)
	{
		self::$_fuzzyExpires++;
		self::$_fuzzyFactor = 1 + log(self::$_fuzzyExpires);
		Saf_Debug::outData(array('Cache', "increasing cacheff for {$what} during load", self::$_fuzzyFactor), 'PROFILE');
	}
	
	public static function save($file, $value, $mode = self::STAMP_MODE_REPLACE)
	{
		if (is_null($value)) {
			Saf_Debug::outData(array("saving null value to cache, {$file}"));
		}
		self::$_memory[$file] = $value;
		$path = self::$_path . '/' . $file;
		$pointer = fopen($path, 'c+');
		$fileLock = flock($pointer, LOCK_EX);
		if (!$fileLock) {
			Saf_Debug::out("write blocking {$file}");
			$fileLock = flock($pointer, LOCK_EX | LOCK_NB);
		}
		if ($fileLock) {
			$oldTime = 0;
			if ($mode) {
				$size = filesize($path);
				$contents =
					$size
						? fread($pointer, $size)
						: '';
				$oldValue = json_decode($contents, TRUE);
				if ($oldValue && is_array($oldValue) && array_key_exists('stamp', $oldValue)) {
					$oldTime = $oldValue['stamp'];
				}
			}
			ftruncate($pointer, 0);
			rewind($pointer);
			$time = 
				$mode === self::STAMP_MODE_KEEP
				? $oldTime
				: (
					$mode === self::STAMP_MODE_AVG && $oldTime > 0
					? floor(floatval(time() + $oldTime) / 2)
					: time()
				);
			fwrite($pointer,json_encode(
				array('stamp' => $time, 'payload' => $value),
				JSON_FORCE_OBJECT
			));
//	Saf_Debug::out("cached {$file}");
		} else {
			Saf_Debug::out("unable to save {$file}");
		}
		flock($pointer, LOCK_UN);
		fclose($pointer);
	}
	
	public static function calcHashFile($file, $uname){
		$hash = md5($file . $uname);
		$prefix = substr($hash, 0, 2);
		return 'hash/perm/' . $prefix . '/' . $hash;
	}
	
	public static function saveHash($file, $uname, $value)
	{
		if (is_null($value)) {
			Saf_Debug::outData(array("saving null value to hash, {$file}:{$uname}"));
		}
		if (!array_key_exists($file, self::$_hashMemory)) {
			self::$_hashMemory[$file] = array();
		}
		self::$_hashMemory[$file][$uname] = $value;
		if (strpos($file, 'hash/') === 0) {
			try{
				$fileUnhash = substr($file, 5);
				self::_initHash($fileUnhash);
			} catch (Exception $e) {
				Saf_Debug::out("unable to prepare hash for {$file} : {$uname}. " . $e->getMessage());
			}
		}
		$path = self::$_path . '/' . $file;
		$mode = file_exists($path) ? 'r+' : 'w'; //#NOTE could use c+, but $mode is overloaded
		$pointer = fopen($path, $mode);
		$fileLock = flock($pointer, LOCK_EX);
		if (!$fileLock) {
			Saf_Debug::out("write blocking {$file}");
			$fileLock = flock($pointer, LOCK_EX | LOCK_NB);
		}
		if ($fileLock) {
			$size = filesize($path);
			$contents =
				$size
					? fread($pointer, $size)
					: '';
			$hashValue = 
				'r+' == $mode 
				? json_decode($contents, TRUE)
				: array();
			if (is_null($hashValue)) {
				Saf_Debug::out("cache invalid, resetting {$file}");
				$hashValue = array();
			}
			ftruncate($pointer, 0);
			rewind($pointer);
			$time = time();
			$hashValue[$uname] = array('stamp' => $time, 'payload' => $value);
			$jsonOutput = json_encode($hashValue, JSON_FORCE_OBJECT);
			if ($jsonOutput) {
				fwrite($pointer, $jsonOutput);
			} else {
				Saf_Debug::out("unable to encode {$file} : {$uname}");
				fwrite($pointer, $contents);
			}
//Saf_Debug::out("cached {$file} : {$uname}");
		} else {
			Saf_Debug::out("unable to save {$file} : {$uname}");
		}
		flock($pointer, LOCK_UN);
		fclose($pointer);		
	}

	public static function clear($file)
	{
		if (strpos($file, '/*') == strlen($file) - 2) {
			$path = substr($file, 0, strlen($file) - 2);
			$clearableFiles = self::dir($path);
			if (count($clearableFiles) > 0) {
				foreach($clearableFiles as $clearable) {
					if (!self::clear($clearable)) {
						return FALSE;
					}
				}
			}
			return TRUE;
		} else {
			if (file_exists(self::$_path . '/' . $file)) {
				unlink(self::$_path . '/' . $file);
			}
			if (array_key_exists($file, self::$_memory)) {
				unset(self::$_memory[$file]);
			}
			clearstatcache(FALSE, self::$_path . '/' . $file);
			return !file_exists(self::$_path . '/' . $file);
		}

	}
}