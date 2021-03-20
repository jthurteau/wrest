<?php

/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * installable closure to add rooting tool,
 * for use with $canister['install']
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

if (!isset($canister)) {
    throw new Exception("Calling install:" . basename(__FILE__, '.php') . " out of context.");
}
return function(string $root, ?string $fail = null) use (&$canister) {
    $rootFile = 
        key_exists('installPath', $canister) && is_string($canister['installPath']) 
        ? "{$canister['installPath']}/{$root}.php"
        : '';
    if(!file_exists($rootFile) || !is_readable($rootFile)){
        if (!is_null($fail)) {
            throw new Exception(str_replace('{$}', $root, $fail), 127, new Exception($rootFile));
        }
        return null;
    }
    $root = require($rootFile);
    return is_array($root) ? $root : [];
};