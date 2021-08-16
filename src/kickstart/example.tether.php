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

return function ( #TODO #PHP8 allows throw as an expression
    array &$canister = []
){
    key_exists('installPath', $canister) || ($canister['installPath'] = __DIR__);
    $tools = ['log'];//,'doctor']; //#NOTE enables /src/tools/[ToolName].tether.php
    $canister['install']('modulate'); //#NOTE enables /module/[ModuleName]/src/tether.php

    foreach($canister['root']('host.root') as $key => $value){
        !key_exists($key, $canister) && $canister[$key] = $value;
    }
    $canister['tether']('pipe.tether', 'Application pipe unavailable.');
    //$canister['tether']('cache.tether', 'Application cache unavailable.');

    $script = 
        key_exists('foundationScript', $canister) 
        ? $canister['foundationScript'] 
        : 'kick';
    $scriptErrorMessage = 'Application foundation unavailable.';

    $applicationRoot =
        key_exists('applicationRoot', $canister) 
        ? $canister['applicationRoot'] 
        : '/var/www/application';
    $defaultPath = 
        file_exists("{$canister['installPath']}/vendor/Saf/src/") #file_exists("{$canister['installPath']}/vendor/Saf/src/{$script}.php")
        ? "{$canister['installPath']}/vendor/Saf/src/"
        : "{$applicationRoot}/vendor/Saf/src";
    $path = 
        key_exists('foundationPath', $canister) 
        ? $canister['foundationPath'] 
        : $defaultPath;

    if (key_exists('resolverPylon', $canister) && in_array($canister['resolverPylon'], $tools) ) {
        $script = $canister['resolverPylon'];
        $path = "{$canister['installPath']}/src/tools";
        $scriptErrorMessage = "Application tool: {$script}, is unavailable";
    }

    if (key_exists('enableDoctor', $canister) && $canister['enableDoctor']) {
        $canister['tether']("//{$canister['installPath']}/src/tools/doctor.tether", 'Error including doctor tether');
    }
    //throw new Exception('hi');
    $canister['tether']("//${path}/{$script}.tether", $scriptErrorMessage);
};