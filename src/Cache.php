<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for cache management. Includes fuzzy cache support.
 */

namespace Saf;

class Cache
{

    public const SHORT_CACHE_EXP = 60; // one minute
    public const MED_CACHE_EXP = 900; // fifteen minutes
    public const LONG_CACHE_EXP = 10800; // one day
    public const MAX_MEMORY_PERCENT = 10;

    public const CONFIG_DEFAULT = 'DEFAULT';
    public const CONFIG_MAX_SIZE = 'maxSize';
    public const CONFIG_MAX_AGE = 'maxAge';
    public const CONFIG_AGE_FUZZY = 'fuzzy';
    public const CONFIG_STAMP_MODE = 'stampMode';
    public const STAMP_MODE_REPLACE = 0;
	public const STAMP_MODE_AVG = 1;
	public const STAMP_MODE_KEEP = 2;
    public const CONFIG_HASH_STORAGE = 'hashFacet';

    public const LOG_BASE = M_E; //natural log

    /**
     * The the number of non-caches calls channeled, more cling 
     * increases the amount of additional fuzziness per call
     */
    protected static int $cling = 0;

    /**
     * The accumluated fuzziness for non-cached calls, higher
     * fuzz increases the chance of accepting older cached data
     * in favor of loading fresh data
     */
    protected static float $fuzziness = 1;

    /**
     * Temporary memory for stored hashes
     */
    protected static array $hashMemory = [];

    /**
     * Optional callback for simple get/set cache plugin
     */
    protected static $callback = null;

    public static function getFactor():float
    {
        return self::$fuzziness;
    }

    public static function setFactor(float $factor):void
    {
        self::$fuzziness = $factor;
    }

    public static function increaseCling():float
    {
        self::$fuzziness = 1 + log(++self::$cling, self::LOG_BASE);
        return self::$fuzziness;
    }

    public static function fuzzyCling(int $threshold):int
    {
		return $threshold + rand(0, ceil($threshold * self::$fuzziness));
    }

    public static function staticCling(int $threshold):int
    {
		return $threshold + ceil($threshold * self::$fuzziness);
    }

    public static function resetCling():void
    {
        self::$cling = 0;
        self::$fuzziness = 1;
    }

    public static function getHashed(string $facet, string $uname, $callback = null):mixed
    {
        $stored =
            key_exists($facet, self::$hashMemory)
            && key_exists($uname, self::$hashMemory[$facet]);
        if ($stored) {
            return self::$hashMemory[$file][$uname];
        }
        return null;
    }

    public static function setHashed(string$facet, string $uname, mixed $data):void
    {
        //#TODO set limit like Saf\Memory (or delegate?)
        key_exists($facet, self::$hashMemory) || (self::$hashMemory[$facet] = []);
        self::$hashMemory[$facet][$uname] = $data;
    }

    public static function get(string $facet)
    {
        if (self::$callback) {
            return self::$callback($facet);
        }
        return false;
    }

    public static function store(string $facet, mixed $data)
    {
        if (self::$callback) {
            self::$callback($facet, $data);
        }
        return false;
    }

    public static function registerCallback($callback)
    {
        self::$callback = $callback;
    }

}