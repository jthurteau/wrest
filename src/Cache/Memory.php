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

    public static function available(?string $facet = null)
    {
        return !is_null($facet) && key_exists($facet, self::$memory);
    }

    public static function load(?string $facet = null, $default = null)
    {
        return 
            self::available($facet)
            ? self::$memory[$facet] 
            : $default;
    }

    public static function save(?string $facet = null, $data, $maxSize = self::DEFAULT_MAX_PERCENT) : bool
    {
        //#TODO test MAX_MEMORY
        !is_null($facet) && (self::$memory[$facet] = $data);
        return true;
    }

}