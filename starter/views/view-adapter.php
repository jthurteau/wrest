<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * view adapter closure, accepts an object (i.e. view binding) or array (i.e. canister)
 * returns a branching, null-safe getter function 
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

/**
 * @param object|array $target to generate a getter for 
 */
return function ($target) {
    /**
     * @param string|array $find traverses $target, 
     *  if $find is an array any entries will match, 
     *  if $find is a colon delimited string, the getter recursively traverses down
     * @param mixed $default if no match is found, return $default
     * @param mixed $subTarget object or array to search, reverts to $target if null
     */
    $getter =  function ($find, $default = null, $subTarget = null) use ($target, &$getter) {
        if (is_array($find)){
            foreach($find as $sub){
                $subFind = $getter($sub, null);
                if (!is_null($subFind)){
                    return $subFind;
                }
            }
            return $default;
        } else {
            $findParts = explode(':', $find);
            $currentFind = array_shift($findParts);
            $currentTarget = $subTarget ?: $target;
            if (
                is_object($currentTarget)
                && $currentFind 
                && property_exists($currentTarget, $currentFind)
            ) {
                return 
                    !$findParts 
                    ? $currentTarget->$currentFind 
                    : $getter(implode(':', $findParts), $default, $currentTarget->$currentFind);
            } elseif (
                is_array($currentTarget)
                && $currentFind 
                && array_key_exists($currentFind, $currentTarget)
            ) {
                return
                    !$findParts
                    ? $currentTarget[$currentFind]
                    : $getter(implode(':', $findParts), $default, $currentTarget[$currentFind]);
            }
        }
        return $default;
    };
    return $getter;
};