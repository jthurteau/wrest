<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for process queuing

*******************************************************************************/

class Saf_Queue {

	protected static $_mode = 'db';
	protected static $_path = NULL;
	protected static $_db = NULL;
	protected static $_statusModel = NULL;
	protected static $_critical = FALSE;

	public static function init($config)
	{
		if (array_key_exists('mode', $config)) {
			if ('db' !== $config['mode']) {
				self::$_mode = $config['mode'];
				throw new Saf_Exception_NotImplemented('Unsupported Queue Mode, ' . $config['mode']);
			}
		} else if (!array_key_exists('db', $config) || !$config['db']) {
			throw new Saf_Exception_NotImplemented('No Queue DB Provided');
		} else if (!array_key_exists('path', $config) || !$config['path']) {
			throw new Saf_Exception_NotImplemented('No Queue DB Tables Provided');
		} else {
			self::$_db = $config['db'];
			self::$_path = $config['path'];
			if (!self::$_db->isConnected()) {
				Saf_Debug::out('Queue DB Unavailable');
			} else {
				$pathString = '(';
				$first = TRUE;
				foreach(self::$_path as $method => $table) {
					$pathString .= ($first ? '' : ', ') . "{$method} : {$table}";
					$first = FALSE;
				}
				$pathString .= ')';
				Saf_Debug::out('Queueing to ' . self::$_db->getHostName() . '/' . self::$_db->getSchemaName() . '/' . $pathString);
			}
		}
	}
	
	public static function pushEmail($when, $payload, $user, $classification, $recall)
	{
		if (!array_key_exists('email', self::$_path)) {
			throw new Exception('Email Queue Not Configured');
		}
		$table = self::$_path['email'];
		if (is_null($classification) || '' == trim($classification)) {
			throw new Exception('Invalid Audit Classification');
		}
		$cols = '`when`, `classification`, `recall`, `payload`, `username`';
		$values =
			Saf_Pdo_Connection::escapeString(date(Saf_Time::FORMAT_DATETIME_DB)) //#NOTE don't insulate the timestamp
			. ', ' . Saf_Pdo_Connection::escapeString(trim($classification));
		if (!is_null($message)) {
			$cols .= ', `message`';
			$values .= ', ' . Saf_Pdo_Connection::escapeString(trim($message));
		}
		$remote = array();
		if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
			$remote['agent'] = $_SERVER['HTTP_USER_AGENT'];
		}
		if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
			$remote['addr'] = $_SERVER['REMOTE_ADDR'];
		}
		if (array_key_exists('HTTP_REFERER', $_SERVER)) {
			$remote['ref'] = $_SERVER['HTTP_REFERER'];
		}
		if (!is_null($request)) {
			if (is_object($request)) {
				$remote['uri'] = $request->getRequestUri();
				$remote['method'] = $request->getMethod();
				$remote['post'] = $request->getPost();
			} else if (is_array($request)){
				$remote['raw'] = Saf_Array::toString($request);
			} else {
				$remote['raw'] = trim($request);
			}
		} else {
			if (array_key_exists('REQUEST_URI', $_SERVER)) {
				$remote['uri'] = $_SERVER['REQUEST_URI'];
			}
			if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
				$remote['method'] = $_SERVER['REQUEST_METHOD'];
			}
			if (is_array($_POST)) {
				$remote['post'] = $_POST;
			}
		}
		$cols .= ', `request`';
		$requestString = json_encode($remote);
		$values .= ', ' . Saf_Pdo_Connection::escapeString($requestString);
		if (!is_null($user)) {
			$cols .= ', `username`';
			$values .= ', ' . Saf_Pdo_Connection::escapeString(trim($user));
		}
		$query = "INSERT INTO {$table} ({$cols}) VALUES ({$values});";
		$result = self::$_db->insert($query);
		if (!$result) {
			$count = Saf_Cache::get('auditFailCount', NULL);
			if (is_null($count)) {
				$count = 0;
			}
			Saf_Cache::save('auditFailCount', ++$count);
			Saf_Debug::outData(array('failed to audit activity', self::$_db->getErrorMessage(),self::$_db));
		}
		return $result;
	}
	
	public static function pop($count = 1)
	{
		
	}
	
}