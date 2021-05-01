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

    public const METHOD_DEFAULT = true;
    public const METHOD_MEMORY = true;
    public const METHOD_FILE = true;
    public const METHOD_DB = true;

    public const STAMP_MODE_REPLACE = 0;
	public const STAMP_MODE_AVG = 1;
	public const STAMP_MODE_KEEP = 2;

    protected static $memory = [];
    protected static $hashMemory = [];

    protected static $memoryEnabled = true;
    protected static $fileEnabled = false;
    protected static $dbEnabled = false;

    /**
     * The accumluated fuzziness for non-cached calls, higher
     * fuzz increases the chance of accepting older cached data
     * in favor of loading fresh data
     */
    protected static $fuzziness = 1;

    /**
     * The the number of non-caches calls channeled, more cling 
     * increases the amount of additional fuzziness per call
     */
    protected static $cling = 0;

	public static function available($tag, $method = self::METHOD_DEFAULT)
    {
        return false;
    }

    public static function get($tag, $method = self::METHOD_DEFAULT)
    {
        return null;
    }

    public static function store($tag)
    {
        
    }
}