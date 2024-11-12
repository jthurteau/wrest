<?php 

/**
 * Utility class for layout handling
 */

declare(strict_types=1);

namespace Saf\Util;

class Diction {

    protected static $language = [];
    protected static $dialects = [];

    public static function lookup(string $string): string
    {
        return key_exists($string, self::$language) ? self::$language[$string] : $string;
    }

    public static function learn(array $entries, ?string $dialect = null)
    {
        foreach($entries as $index => $string) {
            if (is_null($dialect)) {
                self::$language[$index] = $string;
            } else {
                if (!key_exists($dialect, self::$dialects)) {
                    self::$dialects[$dialect][$index] = $string;
                }

            }
        }
    }

    public static function dialect(string $string, string $dialect)
    {
        return 
            key_exists($dialect, self::$dialects) 
            ? (
                key_exists($string, self::$dialects[$dialect]) 
                ? self::$dialects[$dialect][$string]
                : $string  
            ) : $string;
    }

}