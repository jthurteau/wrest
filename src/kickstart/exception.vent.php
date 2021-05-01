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
    // print_r([__FILE__,__LINE__, dirname(__FILE__) . "/views/gateway.php",realpath(dirname(__FILE__) . "/views/gateway.php"), $canister]);
    // print_r([__FILE__,__LINE__,
    // file_get_contents(dirname(__FILE__) . "/views/gateway.php"),
    // file_exists(dirname(__FILE__) . "/views/gateway.php")
    // ]);

    if (!file_exists(dirname(__FILE__) . "/views/gateway.php") || !is_readable(dirname(__FILE__) . "/views/gateway.php")) {
        #TODO #2.0.0 meditate on missing view
        return false;
    }
    require_once(dirname(__FILE__) . "/views/gateway.php");
    return true;
};