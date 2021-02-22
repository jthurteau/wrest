<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Class for filter replacing (case-overriding)

*******************************************************************************/

class Saf_Filter_Replacer_Replace extends Saf_Filter_Replacer_Abstract
{
    
    public static function replace($value, $segment)
    {
        if (is_array($segment) && array_key_exists('postfilter', $segment)) {
            $segment['postfilter'] = $value;
        }
        return parent::replace($value, $segment);
    }
}