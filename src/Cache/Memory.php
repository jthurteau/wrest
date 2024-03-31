<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base Driver for Disk Based Caching
 */

namespace Saf\Cache;

use Saf\Cache\Strategy;
use Saf\Cache;

class Memory implements Strategy{

    public const DEFAULT_MAX_PERCENT = 10;
    public const DEFAULT_LOAD_SPEC = [Cache::CONFIG_DEFAULT => null];
    public const DEFAULT_SAVE_SPEC = [Cache::CONFIG_MAX_SIZE => self::DEFAULT_MAX_PERCENT];

    protected static $memory = [];

    public static function available(string $facet): bool
    {
        return key_exists($facet, self::$memory);
    }

    public static function load(string $facet, mixed $spec = self::DEFAULT_LOAD_SPEC): mixed
    {
        $default = 
            is_array($spec) && key_exists(Cache::CONFIG_DEFAULT, $spec) 
            ? $spec[Cache::CONFIG_DEFAULT] 
            : null;
        return 
            self::available($facet)
            ? self::$memory[$facet] 
            : $default;
    }

    public static function save(string $facet, mixed $data, mixed $spec = self::DEFAULT_SAVE_SPEC): bool
    {
        //#TODO test MAX_MEMORY and shift as needed?
        //$maxSize = Cache::CONFIG_MAX_SIZE
        //#TODO how to handle references? (preserve or dereference, arrays and objects will behave differently otherwise)
        !is_null($facet) && (self::$memory[$facet] = $data);
        return true;
    }

    public static function forget(string $facet): void
    {
        if(key_exists($facet, self::$memory)) {
            unset(self::$memory[$facet]);
        }
    }

    public static function canStore(mixed $data): bool
    {
        return true;
    }

    public static function parseSpec(mixed $spec): mixed
    {
        return $spec;
    }

    public static function getDefaultLoadSpec(): mixed
    {
        return self::DEFAULT_LOAD_SPEC;
    }

    public static function getDefaultSaveSpec(): mixed
    {
        return self::DEFAULT_SAVE_SPEC;
    }

}