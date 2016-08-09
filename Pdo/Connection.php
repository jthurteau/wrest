<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Middleware class for PDO

 *******************************************************************************/
//require_once (LIBRARY_PATH . '/lib/Saf/Pdo.php');

/**
 *
 * Wrapper class for the PHP PDO object.
 * @author jthurtea
 *
 */

class Saf_Pdo_Connection{

	const TYPE_MYSQL = 'mysql';

	protected $_connection = NULL;
	protected $_hostName = '';
	protected $_userName = '';
	protected $_schemaName = '';
	protected $_driverName = '';
	protected $_errorMessage = array();
	protected $_lastResult = NULL;
	protected $_debugMode = FALSE;

	public function __construct($options = array())
	{
		if (is_array($options) && array_key_exists('dsn', $options)) {
			$this->connect($options['dsn']);
		}
	}

	public function connect($dsn){
		if (!is_null($this->_connection)) {
			$this->disconnect();
		}
		$this->_driverName = (
		array_key_exists('pdodriver', $dsn)
			? $dsn['pdodriver']
			: self::TYPE_MYSQL
		);
		$this->_hostName = (
		array_key_exists('hostspec', $dsn)
			? $dsn['hostspec']
			: 'localhost'
		);
		$this->_userName = (
		array_key_exists('username', $dsn)
			? $dsn['username']
			: ''
		);
		$this->_schemaName = (
		array_key_exists('database', $dsn)
			? $dsn['database']
			: 'reservesdirect'
		);
		$password = (
		array_key_exists('password', $dsn)
			? $dsn['password']
			: ''
		);
		return $this->_connectAs($this->_userName,$password,$this->_schemaName);
	}

	protected function _connectAs($user, $password, $dbName = '')
	{
		$this->_userName = $user;
		if ('' != $dbName) {
			$this->_schemaName = $dbName;
		}
		$dsnString = $this->_driverName . ':host=' . $this->_hostName . ';dbname=' . $this->_schemaName;
		$options = array();
		try{
			$this->_connection = new PDO($dsnString, $this->_userName, $password, $options);
			self::clearErrors();
		} catch (Exception $e) {
			$this->addError($e->getMessage());
			return FALSE;
		}
		return TRUE;
	}

	public function disconnect()
	{
		$this->_connection = NULL;
	}

	public function enableDebug()
	{
		$this->_debugMode = TRUE;
	}

	public function reconnectAs($user, $password, $dbName = '')
	{
		if (!is_null($this->_connection)) {
			$this->disconnect();
		}
		return $this->_connectAs($user, $password, $dbName);
	}

	public function getSchemaName()
	{
		return $this->_schemaName;
	}

	public function getHostName()
	{
		return $this->_hostName;
	}

	public function getUsername()
	{
		return $this->_userName;
	}

	public function getDriverName()
	{
		return $this->_driverName;
	}

	public function query($query, $args = NULL)
	{
		if ($this->_lastResult) {
			$this->_lastResult->closeCursor();
		}
		if (!is_null($args)) {
			return $this->prepareExecute($query, $args);
		}
		$statement = $this->_connection->query($query, PDO::FETCH_ASSOC);
//print_r(array('query', $statement, $statement->rowCount()));
		if (!$statement) {
			$this->addError('Query Failed');
			$this->_pullError();
		}
		$this->_lastResult = $statement;
		return $statement;
	}

	protected function _prepStatement($query, $args)
	{
		return
			!is_null($args)
			? $this->prepareExecute($query, $args)
			: $this->query($query);
	}

	public function insert($query, $args = NULL)
	{
		if(strpos(strtoupper($query),'INSERT') === FALSE) {
			throw new Saf_Pdo_Exception('Attempting to call ::insert on a non-INSERT statement.');
		}
		$statement = $this->_prepStatement($query, $args);
		return
			$statement
			? $this->_connection->lastInsertId()
			: NULL;
	}

	public function delete($query, $args = NULL)
	{
		if(strpos(strtoupper($query),'DELETE') === FALSE) {
			throw new Saf_Pdo_Exception('Attempting to call ::delete on a non-DELETE statement.');
		}
		$statement = $this->_prepStatement($query, $args);
		return
			$statement
			? $this->count()
			: NULL;
	}

	public function update($query, $args = NULL)
	{
		if(strpos(strtoupper($query),'UPDATE') === FALSE) {
			throw new Saf_Pdo_Exception('Attempting to call ::update on a non-UPDATE statement.');
		}
		$statement = $this->_prepStatement($query, $args);
//print_r(array('update',$query,$statement,$this->count(),$statement->rowCount(),$this->getError()));
		return
			$statement
			? $this->count()
			: NULL;
	}

	public function prepareExecute($query, $args)
	{
		if ($this->_lastResult) {
			$this->_lastResult->closeCursor();
		}
		$cleanArgs = array();
		if (!is_array($args)) {
			$cleanArgs = array($args);
		} else {
			foreach($args as $arg) {
				$cleanArgs[] = $arg;
			}
		}
		$explodingBinds = array();
		foreach($cleanArgs as $key=>$arg) {
			if (is_array($arg) && count($arg) > 1){
				$explodingBinds[$key] = count($arg);
			} else if(is_array($arg)) {
				$explodingBinds[$key] = 1;
				$cleanArgs[$key] = (
				array_key_exists(0, $arg)
					? $arg[0]
					: NULL
				);
			}
		}
		if (count($explodingBinds) > 0) { //#TODO #1.0.0 consoidate with ::query
			$query = self::_explodePreparedQuery($query, $explodingBinds);
			$cleanArgs = self::_flattenParams($cleanArgs);
		}
		$statement = $this->_connection->prepare($query);
		if (!$statement) {
			$this->addError('Query Failed');
			$this->_pullError();
		}
		$this->_lastResult = $statement;
		if ($statement) {
			$result = $statement->execute($cleanArgs);
//print_r(array('exec', $statement, $result, $statement->rowCount()));
			if('00000' != $statement->errorCode()) {
				$errorInfo = $statement->errorInfo();
				$errorMessage = (array_key_exists(2, $errorInfo)) && '' != trim($errorInfo[2])
					? $errorInfo[2]
					: 'No error message given by the DB.';
				// #TODO #1.0.0 throw some more specific exceptions: duplicate/constraint viloations, syntax, etc.
				if (strpos(strtolower($errorMessage),'duplicate entry') === 0) {
					throw new Saf_Exception_DbDuplicate('The specified action would create a duplicate DB entry.');
				}
				throw new Saf_Pdo_Exception('Bad Query Detected. ' . $errorMessage . '.');
			}
		}
		return $statement;
	}

	protected static function _explodePreparedQuery($query, $map)
	{
		$queryBits = explode('?', $query);
		if (count($map) == 0 || count($queryBits) == 1) {
			return $query;
		}
		//#TODO #1.0.0 throw mismatched sizes
		$replacements = array();
		foreach ($map as $position => $count) {
			$replacements[$position] = implode(',', array_fill(0, $count, '?'));
		}
		$query = $queryBits[0];
		for ($i = 0; array_key_exists($i+1, $queryBits); $i++) {
			$query .=
				(
					array_key_exists($i, $replacements)
					? $replacements[$i]
					: 'NULL'
				) . $queryBits[$i+1];
		}
		return $query;
	}

	protected static function _flattenParams($args)
	{//#TODO #1.0.0 surely we have a utility that does this
		$newArgs = array();
		foreach($args as $arg) {
			if(is_array($arg)) {
				foreach($arg as $subArg) {
					$newArgs[] = $subArg;
				}
			} else {
				$newArgs[] = $arg;
			}
		}
		return $newArgs;
	}

	public function all($result = NULL)
	{//}, $mode = PDO::FETCH_BOTH){
		if (is_null($result)) {
			$result = $this->_lastResult;
		}
		if (!$result || !method_exists($result, 'fetchAll')) {
			$this->addError('Unable to fetchAll, no result to pull from.');
		}
		return ($result ? $result->fetchAll(PDO::FETCH_ASSOC) : NULL);
	}

	public function next($result = NULL)
	{//, $mode = PDO::FETCH_BOTH){
		if (is_null($result)) {
			$result = $this->_lastResult;
		}
		if (!$result || !method_exists($result, 'fetch')) {
			$this->addError('Unable to fetch, no result to pull from.');
		}
		return ($result ? $result->fetch(PDO::FETCH_ASSOC) : NULL);
	}

	public function one($result = NULL) //#TODO #NOW this is different than the old ::one, returns a single value,not a row
	{//, $mode = PDO::FETCH_BOTH){
		if (is_null($result)) {
			$result = $this->_lastResult;
		}
		if (!$result || !method_exists($result, 'fetch')) {
			$this->addError('Unable to fetch, no result to pull from.');
		}
		//return ($result ? $result->fetch(PDO::FETCH_ASSOC) : NULL);
		/*
		$id = $select->fetch(PDO::FETCH_NUM);
		return $id[0];
		$result = $this->_connection->query('SHOW TABLES;', PDO::FETCH_NUM);
		$tables =  $result->fetchAll();
		foreach($tables as $table) {
			if($table[0] == $tableName) {
				return TRUE;
			}
		}
		 */
	}

	public function count($result = NULL)
	{
		if (is_null($result)) {
			$result = $this->_lastResult;
		}
		if ($this->_debugMode) {
			Saf_Debug::outData(array('count', $result, $result->rowCount()));
		}
		return ($result ? $result->rowCount() : NULL);
	}

	public function getVersion()
	{
		return $tihs->_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

	public function hasTable($tableName)
	{//#TODO #1.0.0 this isn't driver agnostic yet.
		$result = $this->_connection->query('SHOW TABLES;', PDO::FETCH_NUM);
		$tables =  $result->fetchAll();
		foreach($tables as $table) {
			if($table[0] == $tableName) {
				return TRUE;
			}
		}
		return FALSE;
	}

	public function getError()
	{
		if ($this->_connection) {
			return $this->_connection->errorInfo();
		}
		return array(0, NULL, 'Not Connected');
	}

	public function getInfo()
	{
		if ($this->_lastResult) {
			ob_start();
			$this->_lastResult->debugDumpParams();
			$debug = ob_get_contents();
			ob_end_clean();
			return array(
				'debug' => $debug,
				'count' => $this->_lastResult->rowCount(),
				'status' => $this->_lastResult->errorInfo()
			);
		}
		return NULL;
	}

	public function hasError(){
		if (!$this->_connection) {
			return FALSE;
		}
		$error =  $this->_connection->errorInfo();
		return $error[1];
	}

	public function getErrorMessage($clear = FALSE){
		$error =
			$this->_connection
			? $this->_connection->errorInfo()
			: implode("\n ", $this->_errorMessage);
		if ($clear) {
			$this->clearErrors();
		}
		return
			is_array($error)
			? $error[2]
			: $error;
	}

	public function addError($error)
	{
		$this->_errorMessage[] = $error;
		if ($this->_debugMode) {
			Saf_Debug::out($error);
		}
	}

	protected function _pullError()
	{
		$errorInfo = $this->_connection->errorInfo();
		if (is_array($errorInfo)){
			$this->addError($errorInfo[2]);
		}
	}

	public function clearErrors()
	{
		$this->_errorMessage = array();
	}

	public function isError($what = NULL)
	{//#TODO #1.0.0 needs refactor
		if (
			!is_null($what)
			&& is_object($what)
			&& method_exists($what, 'errorInfo')
		) {
			$error = $what->errorInfo();
		} else if (
			is_null($what)
			|| $what === FALSE
		) {
			$error = $this->getError();
		} else {
			return false;
		}
		return (
			is_array($error)
			&& array_key_exists(1, $error)
			&& array_key_exists(2, $error)
			&& ($error[1] || $error[2])
		);
	}

	public function beginTransaction()
	{
		if (!$this->_connection) {
			return -2; //#TODO #1.0.0 constants?
		}
		return $this->_connection->beginTransaction();
	}

	public function inTransaction()
	{
		return $this->_connection->inTransaction();
	}

	public function rollback()
	{
		if (!$this->_connection) {
			return -2;  //#TODO #1.0.0 constants?
		}
		if ($this->_connection->inTransaction()) {
			return $this->_connection->rollback();
		} else {
			return -1;  //#TODO #1.0.0 constants?
		}
	}

	public function commit()
	{
		if (!$this->_connection) {
			return -2;  //#TODO #1.0.0 constants?
		}
		if ($this->_connection->inTransaction()) {
			return $this->_connection->commit();
		} else {
			return -1;  //#TODO #1.0.0 constants?
		}
	}

	public static function isValidIdentifier($id)
	{
		return(
			!is_null($id)
			&& !is_array($id)
			&& '' != trim($id)
			&& intval($id) > 0
		);
	}

	public static function escapeString($string, $quote = TRUE)
	{
		return (
			$quote
			? "'" . addslashes((string)$string) . "'"
			: addslashes((string)$string)
		);
	}

	public static function unquoteString($string)
	{
		$length = strlen($string);
		return(
			(strpos($string,"'") == 0 && strrpos($string, "'") == $length - 1)
			|| (strpos($string,'"') == 0 && strrpos($string, '"') == $length - 1)
				? substr($string, 1, $length - 2)
				: $string
		);
	}

	public static function escapeBool($bool)
	{
		return $bool ? 'TRUE' : 'FALSE';
	}

	public static function escapeInt($int)
	{
		return intval($int);
	}

	public static function escapeNumber($number)
	{
		$stringNumber = (string)$number;
		return(
		strpos($number,'.') !== FALSE
			? floatval($number)
			: intval($number)
		);
	}

	public static function escapeDate($date, $quote = FALSE)
	{
		//#TODO #1.0.0 there is a lot of functionality we could add here with PHP's date functions...
		$cleanDate = preg_replace('/[^0-9\- :]/','',$date);
		$return = (
		strpos($cleanDate,':') === FALSE
			? trim($cleanDate) . ' 00:00:00'
			: trim($cleanDate)
		);
		return (
		$quote
			? "'{$return}'"
			: $return
		);
	}

	public static function escapeAuto($param)
	{
		if (is_numeric($param)) {
			return self::escapeNumber($param);
		} else {
			return self::escapeString($param);
		}
	}

	public static function escapeArray($array, $delimiter = ',', $cast = 'auto')
	{
		if (!is_array($array)) {
			$array = explode($delimiter, $array);
		}
		foreach($array as $key=>$param) {
			switch(strtolower($cast)) {
				case 'int':
				case 'integer':
					$array[$key] = self::escapeInt($param);
					break;
				case 'string':
				case 'str':
					$array[$key] = self::escapeString($param);
					break;
				case 'date':
					$array[$key] = self::escapeDate($param);
					break;
				default:
					$array[$key] = self::escapeAuto($param);
			}
		}
		return $array;
	}

	public static function escapeList($array, $delimiter = ',', $cast = 'auto'){
		$return = self::escapeArray($array,$delimiter,$cast);
		return (implode(', ', $return));
	}

	public function getLastInsertId($table)
	{
		$select =  $this->query("SELECT LAST_INSERT_ID() FROM {$table}");
		$id = $select->fetch(PDO::FETCH_NUM);
		return $id[0];
	}

}