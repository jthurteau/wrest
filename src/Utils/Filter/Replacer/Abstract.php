<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base class for filter match replacers

*******************************************************************************/
abstract class Saf_Filter_Replacer_Abstract
{

    public static function replace($value, $segment)
    {
//print(\Saf\Debug::outString('default_replace', $value, $segment));
        return is_string($segment)
        ? $segment
        : ( 
            is_array($segment) && array_key_exists('postfilter', $segment)
            ? $segment['postfilter']
            : ''
        );
    }
}