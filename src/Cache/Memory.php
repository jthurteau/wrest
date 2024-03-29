<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base Driver for Disk Based Caching
 */

namespace Saf\Cache;

class Memory {

    public const DEFAULT_MAX_PERCENT = 10;

    protected static $memory = [];

    public static function available(string $facet)
    {
        return key_exists($facet, self::$memory);
    }

    public static function load(string $facet, $default = null): mixed
    {
        return 
            self::available($facet)
            ? self::$memory[$facet] 
            : $default;
    }

    public static function save(string $facet, $data, $maxSize = self::DEFAULT_MAX_PERCENT) : bool
    {
        //#TODO test MAX_MEMORY
        //#TODO how to handle references? (dereference?)
        !is_null($facet) && (self::$memory[$facet] = $data);
        return true;
    }

    public static function forget(string $facet): void
    {
        if(key_exists($facet, self::$memory)) {
            unset(self::$memory[$facet]);
        }
    }

}