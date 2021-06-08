<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility functions for altering URLs
 */

namespace Saf\Utils;

class Vault
{
    private static $vault = [];

    private static $seed = 0;

    //private static $lengths = []; #TODO

    public static function register()
    {
        $next = self::$seed + rand(1,100);
        $id = rand($next, $next + rand(1,100));
        self::$seed = $id + 1;
        self::$vault[$id] = [];
        return $id;
    }

    public static function store($id, $key, $value)
    {
        if (key_exists($id, self::$vault)) {
            self::$vault[$id][$key] = $value;
        }
    }

    public static function retrieve($id, $key)
    {
        return(
            key_exists($id, self::$vault)
                && key_exists($key, self::$vault[$id])
            ? self::$vault[$id][$key]
            : self::noise()
        );
    }

    public static function noise()
    {
        return md5((string)rand( \PHP_INT_MAX / 2, PHP_INT_MAX ));
    }
}