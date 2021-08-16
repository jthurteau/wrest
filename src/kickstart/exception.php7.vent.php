<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * default exception/error vent, accepts an optional canister to use in a view script
 */

declare(strict_types=1);

return function (
    array &$canister = []
){
    // print_r([__FILE__,__LINE__, __DIR__ . "/views/gateway.php",realpath(__DIR__ . "/views/gateway.php"), $canister]);
    // print_r([__FILE__,__LINE__,
    // file_get_contents(__DIR__ . "/views/gateway.php"),
    // file_exists(__DIR__ . "/views/gateway.php")
    // ]);

    if (!file_exists(__DIR__ . "/views/gateway.php") || !is_readable(__DIR__ . "/views/gateway.php")) {
        #TODO #2.0.0 meditate on missing view
        return false;
    }
    require_once(__DIR__ . "/views/gateway.php");
    return true;
};