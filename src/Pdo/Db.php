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
    public const STATE_NOT_CONNECTED = -2;
    public const STATE_NOT_IN_TRANSACTION = -1;
    public const STATE_QUERY_BUILD_FAILED = -1;
    public const STATE_QUERY_NO_RESULT = -2;
    public const STATE_NOT_DETECTED = -1; // last insert did not use auto-id

    protected $config = [];
    protected $connection = null;
    protected $connectionFailure = false;
    protected $driverName = self::DEFAULT_DRIVER;
    protected $hostName = 'localhost';
    protected $hostPort = '';
    protected $userName = '';
    protected $schemaName = '';
    protected $additionalDsn = '';
    protected $debugMode = false;
    protected $vaultId = null;
    protected $vaultKey = null;
    protected $openQueries = [];

    public function __construct($options = [])
    {
        $this->init($options);
    }

    public function init($config) : Db
    {
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
        }
        if (
            key_exists('autoConnect', $config)
            && $config['autoConnect']
        ) {
            $this->connect(key_exists('dsn', $config) ? $config['dsn'] : null);
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

//     public function reconnectAs($user, $password, $dbName = '')
//     {
//         if (!is_null($this->connection)) {
//             $this->disconnect();
//         }
//         return $this->_connectAs($user, $password, $dbName);
//     }

    public function query(?string $query = null, ?array $args = null)
    {
        $statement = null;
        $callback = function($directive, $p1 = null, $p2 = null, $p3 = null) {
            switch ($directive) {
                case 'prepare':
                    $statement = $this->connection->query($p1);
                    if (!$statement) {
                        $this->addError('Query Failed');
                        $this->pullError();
                    }
                    break;
                case 'query':
                    $statement =
                        $p2
                        ? $this->connection->query($p1, $p2)
                        : $this->connection->query($p1);
                    if (!$statement) {
                        $this->addError('Query Failed');
                        $this->pullError();
                    }
                    break;
                case 'execute':
                    $statement = $p1->execute($p2);
                    if(Pdo::NON_ERROR != $p1->errorCode()) {
                        $errorInfo = $p1->errorInfo();
                        $errorMessage = (key_exists(2, $errorInfo)) && '' != trim($errorInfo[2])
                            ? $errorInfo[2]
                            : 'No error message given by the DB.';
                        // #TODO #2.0.0 throw some more specific exceptions: duplicate/constraint viloations, syntax, etc.
                        if (strpos(strtolower($errorMessage),'duplicate entry') === 0) {
                            throw new DbDuplicate('The specified action would create a duplicate DB entry.');
                        }
                        throw new PdoException("Bad Query Detected. {$errorMessage}.");
                    }
                    break;
                case 'error':
                    $this->addError($p1);
                    $p2 && $this->pullError();
                    break;
                default:
                    return $this;
            }
            return $statement;
        };
        $opened = new Query($callback, $query, $args);
        $this->openQueries[] = $opened;
        return $opened;
    }

     public function getVersion()
     {
         return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
     }

     public function hasTable($tableName)
     {
         $queries = [ //#TODO #2.0.0 this isn't driver agnostic yet.
             0 => 'SHOW TABLES;',
         ];
         $tableQuery =
             key_exists($this->driverName, $queries)
             ? $queries[$this->driverName]
             : $queries[0];
         $result = $this->connection->query($tableQuery); //, PDO::FETCH_NUM
         $tables =  $result->all();
         foreach($tables as $table) {
             if($table[0] == $tableName) {
                 return true;
             }
         }
         return false;
     }

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
        return $error;
    }

    public function hasError(): bool
    {
        return count($this->errorMessage) > 0;
    }

    public function pullErrorCallback()
    {
        return function(){
            $errorInfo = $this->connection?->errorInfo();
            return 
                is_array($errorInfo)
                ? $errorInfo[2]
                : null;
        };
    }

     public function beginTransaction()
     {
         if (!$this->connection) {
             return self::STATE_NOT_CONNECTED;
         }
         return $this->connection->beginTransaction();
     }

     public function inTransaction()
     {
         return $this->connection?->inTransaction();
     }

     public function rollback()
     {
         if (!$this->connection) {
             return self::STATE_NOT_CONNECTED;
         }
         if ($this->connection->inTransaction()) {
             return $this->connection->rollback();
         } else {
             return self::STATE_NOT_IN_TRANSACTION;
         }
     }

     public function commit()
     {
         if (!$this->connection) {
             return self::STATE_NOT_CONNECTED;
         }
         if ($this->connection->inTransaction()) {
             return $this->connection->commit();
         } else {
             return self::STATE_NOT_IN_TRANSACTION;
         }
     }

     public function getLastInsertId(string $table)
     {
         $queries = [ //#TODO #2.0.0 this isn't driver agnostic yet.
             0 => "SELECT LAST_INSERT_ID() FROM {$table};",
         ];
         $select =
             key_exists($this->driverName, $queries)
                 ? $queries[$this->driverName]
                 : $queries[0];
         $select =  $this->query($queries);
         $id = $select->fetch(PDO::FETCH_NUM);
         return $id[0];
     }

    public function isConnected()
    {
        return !is_null($this->connection) && !$this->connectionFailure;
    }

    public function clear()
    {
        foreach($this->openQueries as $index => $open) {
            $open->close();
        }
        $this->openQueries = [];
    }


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

}