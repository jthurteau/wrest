<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class and driver object for managing PDO based queries
 */

namespace Saf\Pdo;

use Saf\Pdo;
use Saf\Pdo\Db;
use Saf\Pdo\Exception as PdoException;
use Saf\Exception\DbDuplicate;

class Query
{

    protected $connection = null;
    protected $fetchMode = \PDO::FETCH_ASSOC;
    protected $debugMode = false;
    protected $lastResult = null;

    public function __construct($connectionCallback, ?string $query = null, ?array $args = null)
    {
        $this->connection = $connectionCallback;
        if ($query) {
            $this->query($query, $args);
        }
    }

    public function enableDebug()
    {
        $this->debugMode = true;
        return $this;
    }

    public function getLastResult()
    {
        return $this->lastResult;
    }

    public function setMode(int $mode)
    {
        $this->fetchMode = $mode;
    }

    public function query($query, $args = null)
    {
        if (!$this->connection) {
            throw new PdoException('Not Connected');
        }
        $this->lastResult && $this->close();
        if (!is_null($args)) {
            return $this->prepareExecute($query, $args);
        }
        $statement = ($this->connection)('query', $query, $args);
        $this->lastResult = $statement;
        return $statement;
    }

     protected function prepStatement($query, $args)
     {
         return
             !is_null($args)
             ? $this->prepareExecute($query, $args)
             : $this->query($query);
     }

     public function insert($query, $args = null)
     {
         if(strpos(strtoupper($query),'INSERT') === false) {
             throw new PdoException('Attempting to call ::insert on a non-INSERT statement.');
         }
         $statement = $this->prepStatement($query, $args);
         return
             $statement //#NOTE lastInsertId only works for autoincrement, so -1 indicates explicit id insert
             ? (($this->connection)('lastInsertId') ?: Db::STATE_NOT_DETECTED)
             : null;
     }

     public function delete($query, $args = null)
     {
         if(strpos(strtoupper($query),'DELETE') === false) {
             throw new PdoException('Attempting to call ::delete on a non-DELETE statement.');
         }
         $statement = $this->prepStatement($query, $args);
         return
             $statement
             ? $this->count()
             : NULL;
     }

     public function update($query, $args = null)
     {
         if(strpos(strtoupper($query),'UPDATE') === false) {
             throw new PdoException('Attempting to call ::update on a non-UPDATE statement.');
         }
         $statement = $this->prepStatement($query, $args);
         return
             $statement
             ? $this->count()
             : null;
     }

    public function prepareExecute($query, $args)
    {
        $this->lastResult && $this->close();
        $args && self::autowire($query, $args);
        $this->lastResult = ($this->connection)('prepare', $query);
        if ($this->lastResult) {
            ($this->connection)('execute', $this->lastResult, $args);
            $result = $this->lastResult->execute($args);
        }
        return $this->lastResult;
    }

    public function fetch($resultOrMode = null, $mode = null)
    {
        if (is_int($resultOrMode)) {
            $mode = $resultOrMode;
            $resultOrMode = null;
        }
        return !is_null($resultOrMode) ? $resultOrMode->fetch($mode) : $this->lastResult?->fetch($mode);
    }

    public function all($result = null)
    {
        if (is_null($result)) {
            $result = $this->_lastResult;
        }
        if (!$result || !method_exists($result, 'fetchAll')) {
            ($this->connection)('error', 'Unable to fetchAll, no result to pull from.');
        }
        return ($result ? $result->fetchAll($this->fetchMode) : null);
    }

    public function next($result = null)
    {
        is_null($result) && ($result = $this->lastResult);
        if (!$result || !method_exists($result, 'fetch')) {
            ($this->connection)('error', 'Unable to fetch, no result to pull from.');
        }
        return $result?->fetch($this->fetchMode);
    }

     public function one($result = null)
     {
         is_null($result) && ($result = $this->lastResult);
         if (!$result || !method_exists($result, 'fetch')) {
             ($this->connection)('error', 'Unable to fetch, no result to pull from.');
             return null;
         }
         $row = $result?->fetch($this->fetchMode);
         if (is_null($row) || $row === false) {
             ($this->connection)('error', 'Unable to fetch, no rows in result.');
             return null;
         }
         return current($row);
     }

     public function count($result = null)
     {
         is_null($result) && ($result = $this->lastResult);
//         if ($this->debugMode) {
//             \Saf\Debug::outData(['count', $result, $result->rowCount()]);
//         }
         return $result?->rowCount();
     }

     public function current()
     {
         return $this->lastResult;
     }

     public function close()
     {
         $this->lastResult?->closeCursor();
     }

     public static function simpleResult($result)
     {
         $return = [];
         if (!is_array($result) && (!is_object($result) || !is_a($result, 'Traversable'))) {
             $return[] = $result;
         } else {
             foreach ($result as $row) {
                 $row && ($return[] = $row);
             }
         }
         return $return;
     }

     public static function autowire(string &$query, array &$args)
     {
         $cleanArgs = [];
         if (!is_array($args)) { //#TODO Hash:: method to force indexed array
             $cleanArgs = [$args];
         } else {
             foreach($args as $arg) {
                 $cleanArgs[] = $arg;
             }
         }
         $explodingBinds = [];
         foreach($cleanArgs as $key=>$arg) {
             if (is_array($arg) && count($arg) > 1){
                 $explodingBinds[$key] = count($arg);
             } elseif(is_array($arg)) {
                 $explodingBinds[$key] = 1;
                 $cleanArgs[$key] = key_exists(0, $arg) ? $arg[0] : null;
             }
         }
         if (count($explodingBinds) > 0) {
             $query = Pdo::explodePreparedQuery($query, $explodingBinds);
             $args = Pdo::flattenParams($cleanArgs);
         }
     }

}