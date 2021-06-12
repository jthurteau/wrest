<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing PDO based DB access
 */

namespace Saf;


use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
use Saf\Pdo\Db;

class Pdo
{
    const TYPE_MYSQL = 'mysql';
    const TYPE_MSSQL = 'sqlserver';
    const TYPE_ODBC = 'odbc';

    const NON_ERROR = '00000';

    public function __invoke(ContainerInterface $container, string $name, callable $callback) : Db
    {
        $return = $callback();
        $dbConfig = Container::getOptional($container, ['config','db'], []);
        return $return->init($dbConfig);
    }

    public static function dsnString($db) {
        $driver = $db->getDriverName();
        $port = $db->getHostPort();
        $hostSpec = 'host';
        $host = $db->getHostName();
        $dbSpec = 'dbname';
        $schema = $db->getSchemaName();
        $extra = '';
        if (strpos($driver, self::TYPE_ODBC) === 0) {
            $hostSpec = 'SERVER';
            $dbSpec = 'DATABASE';
        }
        if ($port) {
            $extra .= "PORT={$port};";
        }
        $extra .= $db->getAdditionalDsn();
        return 
            (strpos($driver, ':') !== false ? "{$driver};" : "{$driver}:" ) 
            . ($host ? "{$hostSpec}={$host};" : '') 
            . ($schema ? "{$dbSpec}={$schema};" : '') 
            . $extra;
    }

    public static function explodePreparedQuery($query, $map)
    {
        $queryBits = explode('?', $query);
        if (count($map) == 0 || count($queryBits) == 1) {
            return $query;
        }
        //#TODO #1.0.0 throw mismatched sizes
        $replacements = [];
        foreach ($map as $position => $count) {
            $replacements[$position] = implode(',', array_fill(0, $count, '?'));
        }
        $query = $queryBits[0];
        for ($i = 0; key_exists($i+1, $queryBits); $i++) {
            $query .= 
                (key_exists($i, $replacements) ? $replacements[$i] : 'NULL') 
                . $queryBits[$i+1];
        }
        return $query;
    }

    public static function flattenParams($args)
    {//#TODO #2.0.0 surely we have a utility that does this
        $newArgs = [];
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

    public static function isValidIdentifier($id)
    {
        return(
            !is_null($id)
            && !is_array($id)
            && '' != trim($id)
            && intval($id) > 0
        );
    }

    public static function escapeString($string, $quote = true)
    {
        return
            $quote
            ? "'" . addslashes((string)$string) . "'"
            : addslashes((string)$string);
    }

    public static function escapeSpecialString($string)
    {
        return "'" . addcslashes(stripslashes((string)$string), "\0'") . "'";
    }

    public static function unquoteString($string)
    {
        $length = strlen($string);
        return
            (strpos($string,"'") == 0 && strrpos($string, "'") == $length - 1) //#TODO string utility for starts and ends with
                || (strpos($string,'"') == 0 && strrpos($string, '"') == $length - 1)
            ? substr($string, 1, $length - 2)
            : $string
        ;
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
        return
            strpos($stringNumber,'.') !== false
            ? floatval($number)
            : intval($number);
    }

    public static function escapeDate($date, $quote = false)
    {
        //#TODO #1.0.0 there is a lot of functionality we could add here with PHP's date functions...
        $wrapper = is_string($quote) ? $quote : "'";
        $cleanDate = preg_replace('/[^0-9\- :]/', '', $date);
        $return =
            strpos($cleanDate,':') === false
            ? trim($cleanDate) . ' 00:00:00'
            : trim($cleanDate);
        return
            $quote
            ? "{$wrapper}{$return}{$wrapper}"
            : $return;
    }

    public static function escapeAuto($param)
    {
        return is_numeric($param) ? self::escapeNumber($param): self::escapeString($param);
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
                case 'auto':
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

}