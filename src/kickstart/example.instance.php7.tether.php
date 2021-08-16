<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * instance script, accepts an optional canister and initiates kickstart
 */

declare(strict_types=1);

return function (&$canister = []){
    key_exists('installPath', $canister) || ($canister['installPath'] = __DIR__);
    key_exists('autoTools', $canister) || ($canister['autoTools'] = []);
    key_exists('inlineTools', $canister) || ($canister['inlineTools'] = []);
    #NOTE see _dep_instance_roots.php
    $applicationRoot =
        key_exists('applicationRoot', $canister) 
        ? $canister['applicationRoot'] 
        : '/var/www/application';
    $defaultPath = 
        file_exists("{$canister['installPath']}/vendor/Saf/src/") #file_exists("{$canister['installPath']}/vendor/Saf/src/{$script}.php")
        ? "{$canister['installPath']}/vendor/Saf/src/"
        : "{$applicationRoot}/vendor/Saf/src";
    $canister['tether']('src/kickstart/view.tether');
    $toolPath = "//{$canister['installPath']}/src/tools";
    foreach($canister['inlineTools'] as $inlineTool) {
         $inlineToolPath = "{$toolPath}/{$inlineTool}.tether";
         $toolError = "Error including inline {$inlineTool} tether";
         $canister['tether']($inlineToolPath, $toolError);
    }
    if (
        key_exists('resolverPylon', $canister) 
        && in_array($canister['resolverPylon'], $canister['autoTools'])
    ) {
        $script = $canister['resolverPylon'];
        $path = "{$canister['installPath']}/src/tools";
        $scriptErrorMessage = "Application tool: {$script}, is unavailable";
    } else {
        $script = 
            key_exists('foundationScript', $canister) 
            ? $canister['foundationScript'] 
            : 'kick';
        $path = 
            key_exists('foundationPath', $canister) 
            ? $canister['foundationPath'] 
            : $defaultPath;
        $scriptErrorMessage = 'Application foundation unavailable.';
    }
    return $canister['tether']("//${path}/{$script}.tether", $scriptErrorMessage);
};