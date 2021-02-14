<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * instance script, accepts an optional canister then
 * then starts the foundation script
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

return function( #TODO #PHP8 allows throw as an expression
    array $canister = []
){
    $installPath = dirname(__FILE__);
    $script = 
        array_key_exists('foundationScript', $canister) 
        ? $canister['foundationScript'] 
        : 'kick';
    $applicationRoot = 
        array_key_exists('applicationRoot', $canister) 
        ? $canister['applicationRoot'] 
        : '/var/www/application';
    $internalFoundationPath = "{$installPath}/vendor/Saf/src/{$script}.php";
    $defaultFoundationPath = (
        file_exists($internalFoundationPath)
            ? $internalFoundationPath
            : "{$applicationRoot}/vendor/Saf/src"
    );
    $path = 
        array_key_exists('founationPath', $canister) 
        ? $canister['founationPath'] 
        : $defaultFoundationPath;
    $foundation = "{$path}/{$script}.php";
    if (!file_exists($foundation)) {
        throw new Exception('Application foundation is missing.');
    }
    if (!is_readable($foundation)) {
        throw new Exception('Application foundation is inaccessible.');
    }
    $kickstart = require_once($foundation);
    if (is_callable($kickstart)) {
        $kickstart($canister);
    }
};