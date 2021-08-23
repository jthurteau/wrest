<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * installable closure to add rooting tool,
 * for use with $canister['install']
 * @link saf.src:kickstart/installable/root.php
 */

declare(strict_types=1);

if (!isset($canister)) {
    throw new Exception("Calling install:" . basename(__FILE__, '.php') . " out of context.");
}
return function(string $root, ?string $fail = null) use (&$canister) {
    $rootFile = 
        key_exists('installPath', $canister) && is_string($canister['installPath']) 
        ? "{$canister['installPath']}/{$root}.root.php"
        : '';
    $genericFile = 
        key_exists('installPath', $canister) && is_string($canister['installPath']) 
        ? "{$canister['installPath']}/{$root}.php"
        : '';
    if(!file_exists($rootFile) || !is_readable($rootFile)){
        if (!file_exists($genericFile) || !is_readable($genericFile)) {
            if (!is_null($fail)) {
                $failMessage = str_replace('{$}', $root, $fail);
                throw new Exception($failMessage, 127, new Exception($rootFile));
            } else {
                return [];
            }
        } else {
            $root = require($genericFile);
        }
    } else {
        $root = require($rootFile);
    }
    return 
        is_array($root) || $root instanceof ArrayAccess 
        ? $root 
        : [];
};