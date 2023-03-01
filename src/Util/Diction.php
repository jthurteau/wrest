<?php 

/**
 * Utility class for layout handling
 */

declare(strict_types=1);

namespace Saf\Util;

class Diction {

    protected static $language = [];

    public static function lookup(string $string) : string
    {
        return key_exists($string, self::$language) ? self::$language : $string;
    }

    public static function learn(array $entries)
    {
        foreach($entries as $index => $string) {
            $language[$index] = $string;
        }
    }

}