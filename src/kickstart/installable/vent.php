<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * installable closure to add venting tool,
 * for use with $canister['install']
 * @link saf.src:kickstart/installable/vent.php
 */

declare(strict_types=1);

if (!isset($canister)) {
    throw new Exception("Calling install:" . basename(__FILE__, '.php') . " out of context.");
}
return function ($final, ?string $vent = null) use (&$canister) {
    $defaultPayload = [__FILE__, $final, $canister];
    $errors = error_get_last();
    if ($errors) {
        $defaultPayload['errors'] = $errors;
    }
    if ($vent) {
        $basePath =
            strpos($vent, '/') === 0
            ? $vent
            : "{$canister['installPath']}/{$vent}";
        $ext =  strrpos($vent, '/src') === (strlen($vent) - strlen('/src')) ? '/' : '.';
        $ventFile = 
            key_exists('installPath', $canister) && is_string($canister['installPath']) 
            ? "{$basePath}{$ext}vent.php"
            : '';
        $genericFile = 
            key_exists('installPath', $canister) && is_string($canister['installPath']) 
            ? "{$basePath}.php"
            : '';
        if(!file_exists($ventFile) || !is_readable($ventFile)){
            if (!file_exists($genericFile) || !is_readable($genericFile)) {
                return null;
            } else {
                $vent = require($genericFile);
            }
        } else {
            $vent = require($ventFile);
        }
        return is_callable($vent) ? $vent($final) : $vent;
    } else {
        print_r($defaultPayload);
        return true;
    }
};