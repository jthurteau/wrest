<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for auditing activity

 *******************************************************************************/
class Saf_Audit
{

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
				throw new Saf_Exception_NotImplemented('Unsupported Audit Mode, ' . $config['mode']);
			}
		} else if (!array_key_exists('db', $config) || !$config['db']) {
			throw new Saf_Exception_NotImplemented('No Audit DB Provided');
		} else if (!array_key_exists('path', $config) || !$config['path']) {
			throw new Saf_Exception_NotImplemented('No Audit DB Table Provided');
		} else {
			self::$_db = $config['db'];
			self::$_path = $config['path'];
			if (!self::$_db->isConnected()) {
				Saf_Debug::out('Audit DB Unavailable');
			} else {
				Saf_Debug::out('Auditing to ' . self::$_db->getHostName() . '/' . self::$_db->getSchemaName() . '/' . self::$_path);
			}
		}
	}

	public static function add($classification, $message = NULL, $request = NULL, $user = NULL)
	{
		try {
			if (!self::$_db) {
				throw new Exception('Audit not initialized');
			}
			$table = self::$_path;
			if (is_null($classification) || '' == trim($classification)) {
				throw new Exception('Invalid Audit Classification');
			}
			$cols = '`when`, `classification`';
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
		} catch (Exception $e){
			if (self::$_critical) {
				throw($e);
			} else {
				Saf_Debug::outData(array('failed to audit activity', self::$_db->getErrorMessage(),self::$_db));
			}
		}
		return NULL;
	}

	public static function update($id, $request = NULL, $user = NULL)
	{
		try {
			if (!self::$_db) {
				throw new Exception('Audit not initialized');
			}
			$table = self::$_path;
			if (is_null($id) || 0 == (int)$id) {
				throw new Exception('Invalid Audit ID');
			}
			$id = (int)$id;
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
			$cols = ' `when` = '
				. Saf_Pdo_Connection::escapeString(date(Saf_Time::FORMAT_DATETIME_DB)); //#NOTE don't insulate the timestamp
			$requestString = json_encode($remote);
			$cols .= ', `request` = ' . Saf_Pdo_Connection::escapeString($requestString);
			if (!is_null($user)) {
				$cols .= ', `username` = ' . Saf_Pdo_Connection::escapeString(trim($user));
			}
			$query = "UPDATE {$table} SET {$cols} WHERE `id` = {$id};";
			$result = self::$_db->update($query);
			if (!$result) {
				$count = Saf_Cache::get('auditFailCount', NULL);
				if (is_null($count)) {
					$count = 0;
				}
				Saf_Cache::save('auditFailCount', ++$count);
				Saf_Debug::outData(array('failed to audit activity', self::$_db->getErrorMessage(),self::$_db));
			}
			return $result;
		} catch (Exception $e){
			if (self::$_critical) {
				throw($e);
			} else {
				Saf_Debug::outData(array('failed to audit activity', self::$_db->getErrorMessage(),self::$_db));
			}
		}
		return NULL;
	}

	public static function addOnce($classification, $message = NULL, $request = NULL, $user = NULL)
	{
		try {
			if (!self::$_db) {
				throw new Exception('Audit not initialized');
			}
			$table = self::$_path;
			if (is_null($classification) || '' == trim($classification)) {
				throw new Exception('Invalid Audit Classification');
			}
			$where =
				'`classification` = ' . Saf_Pdo_Connection::escapeString(trim($classification));
			if (!is_null($message)) {
				$where .= ' AND `message` = ' . Saf_Pdo_Connection::escapeString(trim($message));
			}
			if (!is_null($user)) {
				$where .= ' AND `user` = ' . Saf_Pdo_Connection::escapeString(trim($user));
			}
			$query = "SELECT `id` FROM {$table} WHERE {$where}";
			$result = self::$_db->all(self::$_db->query($query));
			if (!count($result)){
				return self::add($classification, $message, $request, $user);
			} else {
				$id = $result[0]['id'];
				return self::update($id, $request, $user);
			}
			return $result;
		} catch (Exception $e){
			if (self::$_critical) {
				throw($e);
			} else {
				Saf_Debug::outData(array('failed to audit activity', self::$_db->getErrorMessage(),self::$_db));
			}
		}
		return NULL;
	}

	public static function exceptionMessage($exception, $withTrace = TRUE)
	{
		if ($exception) {
			if (!is_object($exception)) {
				return 'Non-Object ' .gettype($exception);
			} else if (!is_a($exception,'Exception')) {
				return 'Non-Exception ' . get_class($exception);
			} else {
				$additional = '';
				if ($exception->getPrevious()) {
					$additional = ' (' . self::exceptionMessage($exception->getPrevious(), FALSE) . ')';
				}
				if ($withTrace) {
					$additional .= "\n" . $exception->getTraceAsString();
				}
				return get_class($exception) . ' ' . $exception->getMessage() . $additional;
			}
		} else {
			return 'none';
		}
	}
}