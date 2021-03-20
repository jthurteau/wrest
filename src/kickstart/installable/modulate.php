<?php

/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * installable closure to add module adapter tool,
 * for use with $canister['install']
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

if (!isset($canister)) {
    throw new Exception("Calling install:" . basename(__FILE__, '.php') . " out of context.");
}
return function(string $module, array $params = []) use (&$canister) {
    $moduleName = ucfirst($module);
    $moduleFile = "{$canister['installPath']}/module/{$moduleName}/src/tether.php";
    if(!file_exists($moduleFile) || !is_readable($moduleFile)){
        throw new Exception("{$moduleName}", 127, new Exception($moduleFile));
    }
    $moduleCallable = require($moduleFile);
    return is_callable($moduleCallable) ? $moduleCallable($params) : $moduleCallable;
};