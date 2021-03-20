<?php

/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * installable closure to add tethering tool,
 * for use with $canister['install']
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

if (!isset($canister)) {
    throw new Exception("Calling install:" . basename(__FILE__, '.php') . " out of context.");
}
return function (string $tether, ?string $fail = null) use (&$canister) {
    $outsidePath = strpos($tether, '//') === 0;
    $tetherFile = 
        $outsidePath 
        ? substr("{$tether}.php", 2)
        : (
            key_exists('installPath', $canister) && is_string($canister['installPath']) 
            ? "{$canister['installPath']}/{$tether}.php"
            : ''
        );
    if(!file_exists($tetherFile) || !is_readable($tetherFile)){
        if (!is_null($fail)) {
            throw new Exception(str_replace('{$}', $tether, $fail), 127, new Exception($tetherFile));
        }
        return null;
    }
    $tether = require($tetherFile);
    return is_callable($tether) ? $tether($canister) : $tether;
};