<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class and driver object for managing PDO based DB access
 */

namespace Saf\Pdo;

use Saf\Traits\ErrorHandler;
use Saf\Pdo;
use Saf\Pdo\PropertyAccess;
use Saf\Pdo\Exception as PdoException;
use Saf\Exception\DbDuplicate;
use Saf\Utils\Vault;

class Db
{
    use ErrorHandler;
    use PropertyAccess;

    public const DEFAULT_DRIVER = Pdo::TYPE_MYSQL;

    protected $config = [];
    protected $connection = null;
    protected $connectionFailure = false;
    protected $driverName = self::DEFAULT_DRIVER;
    protected $hostName = 'localhost';
    protected $hostPort = '';
    protected $userName = '';
    protected $schemaName = '';
    protected $additionalDsn = '';
    protected $lastResult = null;
    protected $debugMode = false;
    protected $vaultId = null;
    protected $vaultKey = null;

    public function __construct($options = [])
    {
        $this->init($options);
    }

    public function init($config) : Db
    {
        //throw new \Saf\Exception\Inspectable($config);
        $this->config = $config;
        if ( key_exists('dsn', $config) && is_array($config['dsn'])) {
            $this->configure($config['dsn']);
            if (
                key_exists('storePasswords', $config)
                && $config['storePasswords']
                && key_exists('password', $config['dsn'])
            ) {
                $this->vaultId = Vault::register();
                $this->vaultKey = Vault::noise();
                Vault::store($this->vaultId, $this->vaultKey, $config['dsn']['password']);
            }
            if (
                key_exists('autoConnect', $config)
                && $config['autoConnect']
            ) {
                $this->connect($config['dsn']);
            }
        }
        return $this;
    }

    /**
     * @param string|array password string, or dsn spec array
     * @return bool connection success
     */
    public function connect($dsn = null)
    {
        if (!is_null($this->connection)) {
            $this->disconnect();
        }
        is_null($dsn) && (
            $dsn = 
                !is_null($this->vaultId) && !is_null($this->vaultKey)
                ? Vault::retrieve($this->vaultId, $this->vaultKey)
                : ''
        ); 
        is_string($dsn) && ($dsn = ['password' => $dsn]);
        $this->configure($dsn);
        $dsnString = Pdo::dsnString($this);
        $password = key_exists('password', $dsn) ? $dsn['password'] : '';
        $options = [];
        try{
            $this->connection = new \PDO($dsnString, $this->getUser(), $password, $options);
            $this->connection && ($this->connectionFailure = !is_null($this->connection->errorCode()));
            $this->clearErrors();
        } catch (\Error | \Exception $e) {
            $withPassword = $password ? ' (with password)' : ' (without password)';
            $this->addError($e->getMessage() . " {$dsnString}{$withPassword}");
            $this->connectionFailure = true;
            return false;
        }
        return true;
    }

    protected function configure($dsn)
    {
        if (!is_array($dsn)) {
            return;
        }
        key_exists('pdodriver', $dsn) && ($this->driverName = $dsn['pdodriver']);
        key_exists('hostspec', $dsn) && ($this->hostName = $dsn['hostspec']);
        key_exists('hostport', $dsn) && ($this->hostPort = $dsn['hostport']);
        key_exists('username', $dsn) && ($this->userName = $dsn['username']);
        key_exists('database', $dsn) && ($this->schemaName = $dsn['database']);
        key_exists('password', $dsn) && ($password = $dsn['password']);
        key_exists('additional', $dsn) && ($this->additionalDsn = $dsn['additional']);
    }

    public function disconnect()
    {
        //#TODO if !is_null($this->connection) ... disconnect
        $this->connection = null;
        $this->connectionFailure = false;
    }

//     public function enableDebug()
//     {
//         $this->_debugMode = TRUE;
//     }

//     public function reconnectAs($user, $password, $dbName = '')
//     {
//         if (!is_null($this->connection)) {
//             $this->disconnect();
//         }
//         return $this->_connectAs($user, $password, $dbName);
//     }

    public function query($query, $args = null)
    {
        if (!$this->connection) {
            throw new PdoException('Not Connected');
        }
        if ($this->lastResult) {
            $this->lastResult->closeCursor();
        }
        if (!is_null($args)) {
            return $this->prepareExecute($query, $args);
        }
        $statement = $this->connection->query($query, \PDO::FETCH_ASSOC);
        if (!$statement) {
            $this->addError('Query Failed');
            $this->pullError();
        }
        $this->lastResult = $statement;
        return $statement;
    }

//     protected function _prepStatement($query, $args)
//     {
//         return
//             !is_null($args)
//             ? $this->prepareExecute($query, $args)
//             : $this->query($query);
//     }

//     public function insert($query, $args = NULL)
//     {
//         if(strpos(strtoupper($query),'INSERT') === FALSE) {
//             throw new Saf_Pdo_Exception('Attempting to call ::insert on a non-INSERT statement.');
//         }
//         $statement = $this->_prepStatement($query, $args);
//         return
//             $statement //#NOTE lastInsertId only works for autoincrement, so -1 indicates explicit id insert
//             ? ($this->connection->lastInsertId() ? $this->connection->lastInsertId() : -1)
//             : NULL;
//     }

//     public function delete($query, $args = NULL)
//     {
//         if(strpos(strtoupper($query),'DELETE') === FALSE) {
//             throw new Saf_Pdo_Exception('Attempting to call ::delete on a non-DELETE statement.');
//         }
//         $statement = $this->_prepStatement($query, $args);
//         return
//             $statement
//             ? $this->count()
//             : NULL;
//     }

//     public function update($query, $args = NULL)
//     {
//         if(strpos(strtoupper($query),'UPDATE') === FALSE) {
//             throw new Saf_Pdo_Exception('Attempting to call ::update on a non-UPDATE statement.');
//         }
//         $statement = $this->_prepStatement($query, $args);
//         return
//             $statement
//             ? $this->count()
//             : NULL;
//     }

    public function prepareExecute($query, $args)
    {
        if ($this->lastResult) {
            $this->lastResult->closeCursor();
        }
        $cleanArgs = array();
        if (!is_array($args)) { //#TODO Hash:: method to force indexed array
            $cleanArgs = [$args];
        } else {
            foreach($args as $arg) {
                $cleanArgs[] = $arg;
            }
        }
        $explodingBinds = array();
        foreach($cleanArgs as $key=>$arg) {
            if (is_array($arg) && count($arg) > 1){
                $explodingBinds[$key] = count($arg);
            } elseif(is_array($arg)) {
                $explodingBinds[$key] = 1;
                $cleanArgs[$key] = key_exists(0, $arg) ? $arg[0] : null;
            }
        }
        if (count($explodingBinds) > 0) { //#TODO #1.0.0 consoidate with ::query
            $query = Pdo::explodePreparedQuery($query, $explodingBinds);
            $cleanArgs = Pdo::flattenParams($cleanArgs);
        }
        $statement = $this->connection->prepare($query);
        if (!$statement) {
            $this->addError('Query Failed');
            $this->pullError();
        }
        $this->lastResult = $statement;
        if ($statement) {
            $result = $statement->execute($cleanArgs);
//print_r(array('exec', $statement, $result, $statement->rowCount()));
            if(Pdo::NON_ERROR != $statement->errorCode()) {
                $errorInfo = $statement->errorInfo();
                $errorMessage = (key_exists(2, $errorInfo)) && '' != trim($errorInfo[2])
                    ? $errorInfo[2]
                    : 'No error message given by the DB.';
                // #TODO #12.0.0 throw some more specific exceptions: duplicate/constraint viloations, syntax, etc.
                if (strpos(strtolower($errorMessage),'duplicate entry') === 0) {
                    throw new DbDuplicate('The specified action would create a duplicate DB entry.');
                }
                throw new PdoException("Bad Query Detected. {$errorMessage}.");
            }
        }
        return $statement;
    }

//     public function all($result = NULL)
//     {//}, $mode = PDO::FETCH_BOTH){
//         if (is_null($result)) {
//             $result = $this->_lastResult;
//         }
//         if (!$result || !method_exists($result, 'fetchAll')) {
//             $this->addError('Unable to fetchAll, no result to pull from.');
//         }
//         return ($result ? $result->fetchAll(PDO::FETCH_ASSOC) : NULL);
//     }

    public function next($result = null)
    {//, $mode = PDO::FETCH_BOTH){
        if (is_null($result)) {
            $result = $this->lastResult;
        }
        if (!$result || !method_exists($result, 'fetch')) {
            $this->addError('Unable to fetch, no result to pull from.');
        }
        return ($result ? $result->fetch(\PDO::FETCH_ASSOC) : null);
    }

//     public function one($result = NULL)
//     {//, $mode = PDO::FETCH_BOTH){
//         if (is_null($result)) {
//             $result = $this->_lastResult;
//         }
//         if (!$result || !method_exists($result, 'fetch')) {
//             $this->addError('Unable to fetch, no result to pull from.');
//             return NULL;
//         }
//         $row = ($result ? $result->fetch(PDO::FETCH_ASSOC) : NULL);
//         if (is_null($row) || $row === FALSE) {
//             $this->addError('Unable to fetch, no rows in result.');
//             return NULL;
//         }
//         return current($row);
//     }

//     public function count($result = NULL)
//     {
//         if (is_null($result)) {
//             $result = $this->_lastResult;
//         }
//         if ($this->_debugMode) {
//             Saf_Debug::outData(array('count', $result, $result->rowCount()));
//         }
//         return ($result ? $result->rowCount() : NULL);
//     }

//     public function getVersion()
//     {
//         return $tihs->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
//     }

//     public function hasTable($tableName)
//     {//#TODO #1.0.0 this isn't driver agnostic yet.
//         $result = $this->connection->query('SHOW TABLES;', PDO::FETCH_NUM);
//         $tables =  $result->fetchAll();
//         foreach($tables as $table) {
//             if($table[0] == $tableName) {
//                 return TRUE;
//             }
//         }
//         return FALSE;
//     }

//     public function getError()
//     {
//         if ($this->connection) {
//             return $this->connection->errorInfo();
//         }
//         return array(0, NULL, 'Not Connected');
//     }

//     public function getInfo()
//     {
//         if ($this->_lastResult) {
//             ob_start();
//             $this->_lastResult->debugDumpParams();
//             $debug = ob_get_contents();
//             ob_end_clean();
//             return array(
//                 'debug' => $debug,
//                 'count' => $this->_lastResult->rowCount(),
//                 'status' => $this->_lastResult->errorInfo()
//             );
//         }
//         return NULL;
//     }

//     public function hasError(){
//         if (!$this->connection) {
//             return $this->hasInternalError();
//         }
//         $error =  $this->connection->errorInfo();
//         return $error && is_array($error) && array_key_exists(1, $error) ? $error[1] : $this->hasInternalError();
//     }


    public function getErrorMessage($clear = false){
        $currentError =
            $this->connection
            ? $this->connection->errorInfo()
            : (
                $this->connectionFailure
                ? ['----not connected----']
                : ['----unknown----']
            );
        $currentErrorString =
            $currentError[0] != Pdo::NON_ERROR
            ? ("--current state--\n"
                . implode("\n ", $currentError)
                . "\n--/current state --\n"
            ) : "--current state--\nno error\n--/current state --\n";
        $error =
            $currentErrorString
            . implode("\n ", $this->errorMessage);
        if ($clear) {
            $this->clearErrors();
        }
        return
            is_array($error)
            ? $error[2]
            : $error;
    }

    public function pullErrorCallback()
    {
        return function(){
            $errorInfo = $this->connection->errorInfo();
            return 
                is_array($errorInfo)
                ? $errorInfo[2]
                : null;
        };
    }

//     public function isError($what = NULL)
//     {//#TODO #1.0.0 needs refactor
//         if (
//             !is_null($what)
//             && is_object($what)
//             && method_exists($what, 'errorInfo')
//         ) {
//             $error = $what->errorInfo();
//         } else if (
//             is_null($what)
//             || $what === FALSE
//         ) {
//             $error = $this->getError();
//         } else {
//             return false;
//         }
//         return (
//             is_array($error)
//             && array_key_exists(1, $error)
//             && array_key_exists(2, $error)
//             && ($error[1] || $error[2])
//         );
//     }

//     public function beginTransaction()
//     {
//         if (!$this->connection) {
//             return -2; //#TODO #1.0.0 constants?
//         }
//         return $this->connection->beginTransaction();
//     }

//     public function inTransaction()
//     {
//         return $this->connection->inTransaction();
//     }

//     public function rollback()
//     {
//         if (!$this->connection) {
//             return -2;  //#TODO #1.0.0 constants?
//         }
//         if ($this->connection->inTransaction()) {
//             return $this->connection->rollback();
//         } else {
//             return -1;  //#TODO #1.0.0 constants?
//         }
//     }

//     public function commit()
//     {
//         if (!$this->connection) {
//             return -2;  //#TODO #1.0.0 constants?
//         }
//         if ($this->connection->inTransaction()) {
//             return $this->connection->commit();
//         } else {
//             return -1;  //#TODO #1.0.0 constants?
//         }
//     }

//     public static function isValidIdentifier($id)
//     {
//         return(
//             !is_null($id)
//             && !is_array($id)
//             && '' != trim($id)
//             && intval($id) > 0
//         );
//     }

//     public static function escapeString($string, $quote = TRUE)
//     {
//         return (
//             $quote
//             ? "'" . addslashes((string)$string) . "'"
//             : addslashes((string)$string)
//         );
//     }

//     public static function escapeSpecialString($string)
//     {
//         return "'" . addcslashes(stripslashes((string)$string), "\0'") . "'";
//     }

//     public static function unquoteString($string)
//     {
//         $length = strlen($string);
//         return(
//             (strpos($string,"'") == 0 && strrpos($string, "'") == $length - 1)
//             || (strpos($string,'"') == 0 && strrpos($string, '"') == $length - 1)
//                 ? substr($string, 1, $length - 2)
//                 : $string
//         );
//     }

//     public static function escapeBool($bool)
//     {
//         return $bool ? 'TRUE' : 'FALSE';
//     }

//     public static function escapeInt($int)
//     {
//         return intval($int);
//     }

//     public static function escapeNumber($number)
//     {
//         $stringNumber = (string)$number;
//         return(
//         strpos($number,'.') !== FALSE
//             ? floatval($number)
//             : intval($number)
//         );
//     }

//     public static function escapeDate($date, $quote = FALSE)
//     {
//         //#TODO #1.0.0 there is a lot of functionality we could add here with PHP's date functions...
//         $cleanDate = preg_replace('/[^0-9\- :]/','',$date);
//         $return = (
//         strpos($cleanDate,':') === FALSE
//             ? trim($cleanDate) . ' 00:00:00'
//             : trim($cleanDate)
//         );
//         return (
//         $quote
//             ? "'{$return}'"
//             : $return
//         );
//     }

//     public static function escapeAuto($param)
//     {
//         if (is_numeric($param)) {
//             return self::escapeNumber($param);
//         } else {
//             return self::escapeString($param);
//         }
//     }

//     public static function escapeArray($array, $delimiter = ',', $cast = 'auto')
//     {
//         if (!is_array($array)) {
//             $array = explode($delimiter, $array);
//         }
//         foreach($array as $key=>$param) {
//             switch(strtolower($cast)) {
//                 case 'int':
//                 case 'integer':
//                     $array[$key] = self::escapeInt($param);
//                     break;
//                 case 'string':
//                 case 'str':
//                     $array[$key] = self::escapeString($param);
//                     break;
//                 case 'date':
//                     $array[$key] = self::escapeDate($param);
//                     break;
//                 default:
//                     $array[$key] = self::escapeAuto($param);
//             }
//         }
//         return $array;
//     }

//     public static function escapeList($array, $delimiter = ',', $cast = 'auto'){
//         $return = self::escapeArray($array,$delimiter,$cast);
//         return (implode(', ', $return));
//     }

//     public function getLastInsertId($table)
//     {
//         $select =  $this->query("SELECT LAST_INSERT_ID() FROM {$table}");
//         $id = $select->fetch(PDO::FETCH_NUM);
//         return $id[0];
//     }

    public function isConnected()
    {
        return !is_null($this->connection) && !$this->connectionFailure;
    }

//     public static function simpleResult($result)
//     {
//         $return = array();
//         if (is_bool($result)) {
//             $return[] = $result;
//         } else {
//             foreach ($result as $row) {
//                 if ($row) {
//                     $return[] = $row;
//                 }
//             }
//         }
//         return $return;
//     }

}