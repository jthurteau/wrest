<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * instance script, accepts an optional canister and initiates kickstart
 * @link saf.src:kickstart/example.instance.php7.tether.php
 * @link install:/instance.tether.php
 */

declare(strict_types=1);

return function (&$canister = []){
    if (!is_array($canister) && !($canister instanceof ArrayAccess)) {
        throw new Exception('Tethered Canister Invalid', 126);
    }
    key_exists('installPath', $canister) || ($canister['installPath'] = __DIR__);
    key_exists('resolvableTools', $canister) || ($canister['resolvableTools'] = []);
    key_exists('inlineTools', $canister) || ($canister['inlineTools'] = []);
    $canister['tether']('src/kickstart/view.tether');
    $toolPath = "src/tools";
    foreach($canister['inlineTools'] as $inlineTool) {
        $inlineToolPath = "{$toolPath}/{$inlineTool}.tether";
        $toolError = "Error including inline {$inlineTool} tether";
        $canister['tether']($inlineToolPath, $toolError);
    }
    if (
        key_exists('resolverPylon', $canister) 
        && in_array($canister['resolverPylon'], $canister['resolvableTools'])
    ) {
        $script = $canister['resolverPylon'];
        $path = "{$canister['installPath']}/src/tools";
        $scriptErrorMessage = "Application tool: {$script}, is unavailable";
    } else {
        $script =
            key_exists('foundationScript', $canister) 
            ? $canister['foundationScript'] 
            : 'kick';
        $vendorPath =
            key_exists('vendorPath', $canister) 
            ? $canister['vendorPath']
            : '/var/www/application/vendor';
        $path =
            key_exists('foundationPath', $canister) 
            ? $canister['foundationPath'] 
            : "{$vendorPath}/Saf/src";
        $scriptErrorMessage = 'Application foundation unavailable.';
    }
    return $canister['tether']("${path}/{$script}.tether", $scriptErrorMessage);
};