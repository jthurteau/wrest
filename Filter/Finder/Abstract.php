<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Base class for filter match finders

*******************************************************************************/
abstract class Saf_Filter_Finder_Abstract
{
    public static function find($value, $search)
    {
//print_r(array('default_find', $value, $search));
        $result = array();
        foreach($search as $currentSearch) {
            $result[] = $currentSearch;
        }
        // $currentSearch =
        //     is_string($search)
        //     ? $search
        //     : (
        //         is_array($search) && array_key_exists('postfilter', $search)
        //         ? $search['postfilter']
        //         : ''
        //     );
        return $result;
    }
}