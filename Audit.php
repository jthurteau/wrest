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

	protected static function _auditFail($additional = '')
	{
		if (trim($additional) != '') {
			$additional = ' ' . $additional;
		}
		$count = Saf_Cache::get('auditFailCount', NULL);
		if (is_null($count)) {
			$count = 0;
		}
		Saf_Cache::save('auditFailCount', ++$count);
		Saf_Debug::outData(array('failed to audit activity.' . $additional, self::$_db->getErrorMessage(),self::$_db));
	}

	public static function add($classification, $message = NULL, $request = NULL, $user = NULL)
	{
		try {
			if (!self::$_db) {
				self::_auditFail('not initialized.');
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
				} else if (is_array($request)){
					$remote['raw'] = json_encode($request,JSON_FORCE_OBJECT); //Saf_Array::toString($request);
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
				self::_auditFail('query fail.');
			}
			return $result;
		} catch (Exception $e){
			if (self::$_critical) {
				throw($e);
			} else {
				self::_auditFail($e->getMessage());
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

	public static function search($params, $limit = 1000, $page = 0)
	{
		if (!self::$_db) {
			throw new Exception('Audit not initialized');
		}
		$table = self::$_path;
		$defaultStart = Saf_Time::modify(Saf_Time::time(), Saf_Time::MODIFIER_SUB_WEEK);
		$defaultEnd = Saf_Time::modify(Saf_Time::time(), Saf_Time::MODIFIER_END_DAY) - 1;
		$start =
			array_key_exists('when', $params)
			? (
				is_array($params['when'])
				? (
					array_key_exists(0, $params['when'])
					? $params['when'][0]
					: $defaultStart
				)
				: is_array($params['when'])
			)
			: $defaultStart;
		$end = array_key_exists('when', $params)
			? (
				is_array($params['when'])
				? (
					array_key_exists(1, $params['when'])
					? $params['when'][1]
					: $defaultEnd
				)
				: is_array($params['when'])
			)
			: $defaultEnd;
		$startDate = date(Saf_Time::FORMAT_DATETIME_DB, $start);
		$endDate = date(Saf_Time::FORMAT_DATETIME_DB, $end);
		$classification = array_key_exists('classification', $params) ? $params['classification'] : NULL;
		$message = array_key_exists('message', $params) ? $params['message'] : NULL;
		$agent = array_key_exists('agent', $params) ? $params['agent'] : NULL;
		$uri = array_key_exists('uri', $params) ? $params['uri'] : NULL;
		$ip = array_key_exists('ip', $params) ? $params['ip'] : NULL;
		$payload = array_key_exists('payload', $params) ? $params['payload'] : NULL;
		$sort = array_key_exists('sort', $params) ? $params['sort'] : 'asc';
		$where = "`when` >= '{$startDate}' AND `when` <= '{$endDate}'";
		if (!is_null($classification)) {
			if (is_array($classification)) {
				$classWhere = ' AND `classification` IN (';
				$classCount = 0;
				foreach($classification as $class) {
					if ($class == '*') {
						$classCount = 0;
						break;
					} else if ($class != '') {
						$classWhere .= ($first ? '' : ',') . Saf_Pdo_Connection::escapeString($class);
						$classCount++;
					}
				}
				$classWhere .= ')';
				if ($classCount) {
					$where .= $classWhere;
				}
			} else if ($classification != '*' && $classification != '') {
				$where .= ' AND `classification` = ' . Saf_Pdo_Connection::escapeString(trim($classification));
			}
		}
		if (!is_null($message) && trim($message) != '') {
			if (strpos($message, '%') === FALSE) {
				$message = "%{$message}%";
			}
			$where .= ' AND `message` LIKE ' . Saf_Pdo_Connection::escapeString(trim($message));
		}
		if (!is_null($agent) && trim($agent) != '') {
			$agent = substr(json_encode($agent), 1, -1);
			$agent = "%\"agent\":\"{$agent}%";
			$where .= ' AND `request` LIKE ' . Saf_Pdo_Connection::escapeString(trim($agent));
		}
		if (!is_null($uri) && trim($uri) != '') {
			$uri = substr(json_encode($uri), 1, -1);
			$uri = "%\"uri\":\"{$uri}%";
			$where .= ' AND `request` LIKE ' . Saf_Pdo_Connection::escapeString(trim($uri));
		}
		if (!is_null($ip) && trim($ip) != '') {
			$ip = substr(json_encode($ip), 1, -1);
			$ip = "%\"addr\":\"{$ip}%";
			$where .= ' AND `request` LIKE ' . Saf_Pdo_Connection::escapeString(trim($ip));
		}
		if (!is_null($payload) && trim($payload) != '') {
			if (strpos($payload, '%') !== FALSE) {
				$payload = str_replace('%', '\%', $payload);
			}
			$payload = '%' . substr(json_encode($payload), 1, -1) . '%';
			$where .= ' AND `request` LIKE ' . Saf_Pdo_Connection::escapeSpecialString(trim($payload));
		}
		switch ($sort) {
			case 'asc':
				$sortString = '`when` ASC';
				break;
			case 'desc':
				$sortString = '`when` DESC';
				break;
			case 'user':
				$sortString = '`username` ASC';
				break;
			default:
				$sortString = '`id`';
		}
		$limit = Saf_Pdo_Connection::escapeInt($limit);
		$page = Saf_Pdo_Connection::escapeInt($page);
		$limitString = "LIMIT {$limit} OFFSET {$page}";
		$query = "SELECT * FROM {$table} WHERE {$where}";
		$countQuery = "SELECT COUNT(`id`) FROM {$table} WHERE {$where} {$limitString}";
		$query .= " ORDER BY {$sortString} {$limitString}";
		Saf_Debug::outData(array('query', $query));
		$result = self::$_db->all(self::$_db->query($query));
		if (is_null($result)) {
			return array('success' => FALSE);
		}
		$countResult = self::$_db->one(self::$_db->query($countQuery));
		return array('success' => TRUE, 'recordCount' => count($result), 'totalCount' => $countResult, 'records' => $result);
	}

	public static function getCurrentClassifications()
	{
		if (!self::$_db) {
			throw new Exception('Audit not initialized');
		}
		$values = array();
		$table = self::$_path;
		$query = "SELECT DISTINCT classification FROM {$table};";
		$result = self::$_db->all(self::$_db->query($query));
		if (!$result) {
			Saf_Debug::outData(array('failed to get audit classifications', self::$_db->getErrorMessage(),self::$_db));
		}
		foreach($result as $record) {
			if ($record && array_key_exists('classification',$record)) {
				$values[] = $record['classification'];
			}
		}
		return $values;
	}

}