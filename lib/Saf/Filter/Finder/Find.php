<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Class for filter matching (case-sensitive)

*******************************************************************************/

class Saf_Filter_Finder_Find extends Saf_Filter_Finder_Abstract
{

    public static function find($find, $search)
    {
//print_r(array('insideFind', $find, $search)); die;        
        $result = array();
        foreach($search as $currentSearch) {
            do {
// $thresh = 10000000;
// $usage = memory_get_usage();
// if ($usage > $thresh) {
//     print_r(array($search,$currentSearch,$parts,$result)); //die;
//     //throw new Exception("Memory Usage Throttled at $usage");
// }	
                $parts = Saf_Filter::breakup($currentSearch, $find);
//print_r(array('breakup_parts', $find, $currentSearch, $parts)); //die;
                if (array_key_exists('before', $parts)) {
                    $result[] = $parts['before'];
                }
                if (array_key_exists('match', $parts)) {
                    $result[] = array(
                        'prefilter' => $parts['match'],
                        'postfilter' => $parts['match']
                    );
                }
                if (array_key_exists('after', $parts)) {
                    $currentSearch = $parts['after'];
                } else {
                    break;
                }            
            } while($parts['start'] !== FALSE);

//print_r(array('breakup_parts_after_loop', $find, $currentSearch, $parts)); //die;
            // if (array_key_exists('before', $parts)) {
            //     $result[] = $parts['before'];
            // }

        }
        return $result;
    }

}