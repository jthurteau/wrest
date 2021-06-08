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


}