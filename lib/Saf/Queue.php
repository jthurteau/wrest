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

	protected static $_maxMailAttempts = 2;

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
	
	public static function pushEmail($recall, $when, $payload, $user, $classification)
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
			Saf_Pdo_Connection::escapeString($when) //#NOTE don't insulate the timestamp
			. ', ' . Saf_Pdo_Connection::escapeString(trim($classification))
			. ', ' . Saf_Pdo_Connection::escapeString(trim($recall))
			. ', ' . Saf_Pdo_Connection::escapeString(serialize($payload))
			. ', ' . Saf_Pdo_Connection::escapeString(trim($user));
		$query = "INSERT INTO {$table} ({$cols}) VALUES ({$values});";
		$result = self::$_db->insert($query);
		if (!$result) {
			$count = Saf_Cache::get('queueFailCount', NULL);
			if (is_null($count)) {
				$count = 0;
			}
			Saf_Cache::save('queueFailCount', ++$count);
			Saf_Debug::outData(array('failed to queue email', self::$_db->getErrorMessage(),self::$_db));
		}
		return $result;
	}

	public static function updateEmail($recall, $when, $payload)
	{
		if (!array_key_exists('email', self::$_path)) {
			throw new Exception('Email Queue Not Configured');
		}
		$table = self::$_path['email'];
		$updates = array();
		if (Saf_Time::isTimeStamp($when)) {
			$when = date(Saf_Time::FORMAT_DATETIME_DB, $when);
		}
		$updates[] = '`when` = ' . Saf_Pdo_Connection::escapeString($when);
		$updates[] = '`payload` = ' . Saf_Pdo_Connection::escapeString(serialize($payload));
		$escapedRecall = Saf_Pdo_Connection::escapeString(trim($recall));
		$updates = implode(',', $updates);
		$query = "UPDATE {$table} SET {$updates} WHERE `recall` = {$escapedRecall} AND NOT `sent`;";
		$result = self::$_db->update($query);
		if (!$result) {
			Saf_Audit::add('notice', 'email not updated, missing or already sent', json_encode($payload), $recall);
		}
	}

	public static function cancelEmail($recall)
	{
		if (!array_key_exists('email', self::$_path)) {
			throw new Exception('Email Queue Not Configured');
		}
		$table = self::$_path['email'];
		$escapedRecall = Saf_Pdo_Connection::escapeString($recall);
		$query = "DELETE FROM {$table} WHERE recall = {$escapedRecall};";
		$result = self::$_db->delete($query);
	}
	
	public static function pop($count = 1)
	{
		if (!array_key_exists('email', self::$_path)) {
			throw new Exception('Email Queue Not Configured');
		}
		$table = self::$_path['email'];
		$query = "SELECT * FROM {$table} WHERE {$where}";
		$result = self::$_db->query($query);
		print_r($result); die;
	}

	public static function getOverdueMailIds($limit = NULL)
	{
		if (!array_key_exists('email', self::$_path)) {
			throw new Exception('Email Queue Not Configured');
		}
		$table = self::$_path['email'];
		$when = Saf_Pdo_Connection::escapeDate(date(Saf_Time::FORMAT_DATETIME_DB, Saf_Time::time() - 1800), TRUE);
		$maxAttempts = self::$_maxMailAttempts;
		$query =
			"SELECT `id` FROM {$table} WHERE `when` <= {$when} AND NOT `sent` AND `attempts` < {$maxAttempts}"
			. ' ORDER BY `when` ASC'
			. (!is_null($limit) ? " LIMIT {$limit}" : '');
		$result = self::$_db->all(self::$_db->query($query));
		if (is_null($result)) {
			Saf_Audit::add('defect', "priorty mail query failing");
			return array();
		}
		$ids = array();
		foreach($result as $record => $data) {
			$ids[] = $data['id'];
		}
		return $ids;
	}

	public static function getDueMailIdsNotIn($exclude = array(), $limit = NULL)
	{
		if (!array_key_exists('email', self::$_path)) {
			throw new Exception('Email Queue Not Configured');
		}
		$table = self::$_path['email'];
		$when = Saf_Pdo_Connection::escapeDate(date(Saf_Time::FORMAT_DATETIME_DB, Saf_Time::time()), TRUE);
		if (!is_array($exclude)) {
			$exclude = array($exclude);
		}
		$excludeIds = implode(', ', $exclude);
		$where = "WHERE `when` <= {$when}" . (count($exclude) > 0 ? " AND `id` NOT IN ({$excludeIds})" : '');
		$maxAttempts = self::$_maxMailAttempts;
		$query =
			"SELECT `id` FROM {$table} {$where} AND NOT `sent` AND `attempts` < {$maxAttempts}"
			. ' ORDER BY `when` ASC'
			. (!is_null($limit) ? " LIMIT {$limit}" : '');
		$result = self::$_db->all(self::$_db->query($query));
		if (is_null($result)) {
			Saf_Audit::add('defect', "due mail query failing");
			return array();
		}
		$ids = array();
		foreach($result as $record => $data) {
			$ids[] = $data['id'];
		}
		return $ids;
	}

	public static function getMailById($ids)
	{
		if (!array_key_exists('email', self::$_path)) {
			throw new Exception('Email Queue Not Configured');
		}
		$table = self::$_path['email'];
		if (!is_array($ids)) {
			$ids = array($ids);
		}
		if (count($ids) == 0) {
			return array();
		}
		$includeIds = implode(', ', $ids);
		$where = "WHERE `id` IN ({$includeIds})";
		$maxAttempts = self::$_maxMailAttempts;
		$query =
			"SELECT `id`, `payload` FROM {$table} {$where} AND NOT `sent` AND `attempts` < {$maxAttempts}";
		$result = self::$_db->all(self::$_db->query($query));
		if (is_null($result)) {
			Saf_Audit::add('defect', "select mail query failing");
			return array();
		}
		$objects = array();
		foreach($result as $record => $data) {
			$objects[$data['id']] = unserialize($data['payload']);
		}
		return $objects;
	}

	public static function markMailSent($id)
	{
		if (!array_key_exists('email', self::$_path)) {
			throw new Exception('Email Queue Not Configured');
		}
		$table = self::$_path['email'];
		$query = "UPDATE {$table} SET `sent` = 1 WHERE `id` = {$id};";
		$result = self::$_db->update($query);
	}

	public static function incrementMailAttempts($id)
	{
		if (!array_key_exists('email', self::$_path)) {
			throw new Exception('Email Queue Not Configured');
		}
		$table = self::$_path['email'];
		$select = "SELECT `attempts` FROM {$table} WHERE `id` = {$id}";
		$attempts = self::$_db->one(self::$_db->query($select));
		$attempts++;
		$query = "UPDATE {$table} SET `attempts` = {$attempts} WHERE `id` = {$id};";
		$result = self::$_db->update($query);
		if (is_null($result) || $result == 0) {
			throw new Saf_Exception_DbUpdate('Unable to increment mail queue attempts');
		}
		return $attempts;
	}

}