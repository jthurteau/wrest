<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * default exception/error vent, accepts an optional canister to use in a view script
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

return function (
    array &$canister = []
){
    $viewPath = 
        key_exists('viewPath', $canister) 
        ? $canister['viewPath'] 
        : (
            dirname(__DIR__) . '/views'
        );
    if (!file_exists("{$viewPath}/gateway.php") || !is_readable("{$viewPath}/gateway.php")) {
        #TODO #2.0.0 meditate on missing view
        return false;
    }
    require_once("{$viewPath}/gateway.php");
    return true;
};