<?php 

/**
 * Utility class for lazyloading config
 */

declare(strict_types=1);

namespace Saf\Util;

use ReflectionFunction;

class Ground {

    public static function &ground(mixed &$value) : mixed
    {
        if (!is_string($value) && is_callable($value)) { //#TODO better to test is_object && closure?
            $v = new ReflectionFunction($value);
            $returnsReference = $v->returnsReference();
            $hasParams = count($v->getParameters()) > 0;
            if (!$hasParams) {
                $replacement = null;
                if ($returnsReference) {
                    $replacement =& $value();
                } else {
                    $replacement = $value();
                }
                return self::ground($replacement);
            } 
        } elseif (is_array($value)) {
            foreach($value as $key => $sub) {
                $value[$key] =& self::ground($value[$key]);
            }
        }
        return $value;
    }

}