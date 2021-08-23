<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * installable closure to add tethering tool,
 * for use with $canister['install']
 * @link saf.src:kickstart/installable/tether.php
 */

declare(strict_types=1);

if (!isset($canister)) {
    throw new Exception("Calling install:" . basename(__FILE__, '.php') . " out of context.");
}
return function (string $tether, ?string $fail = null) use (&$canister) {
    $basePath =
        strpos($tether, '/') === 0
        ? $tether
        : "{$canister['installPath']}/{$tether}";
    $tetherFile = 
        key_exists('installPath', $canister) && is_string($canister['installPath']) 
        ? "{$basePath}.tether.php"
        : '';
    $genericFile = 
        key_exists('installPath', $canister) && is_string($canister['installPath']) 
        ? "{$basePath}.php"
        : '';
    if(!file_exists($tetherFile) || !is_readable($tetherFile)){
        if (!file_exists($genericFile) || !is_readable($genericFile)) {
            if (!is_null($fail)) {
                $failMessage = str_replace('{$}', $tether, $fail);
                throw new Exception($failMessage, 127, new Exception($tetherFile));
            } else {
                return null;
            }
        } else {
            $tether = require($genericFile);
        }
    } else {
        $tether = require($tetherFile);
    }
    return is_callable($tether) ? $tether($canister) : $tether;
};