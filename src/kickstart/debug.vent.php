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

return function ($trace, $data) {
    ob_start();
?>
    <div class="debug">
        <h2 class="header data">Vent Data</h2>
        <div class="code data">
<?php        
    //#TODO foreach $trace
    //$TODO pretty-print $data
    print_r($data);
?>
        </div>
        <h2 class="header trace">Vent Trace</h2>
        <div class="code trace">
<?php
    print_r($trace);
?>
        </div>
    </div>
<?php
    $result = ob_get_contents();
    ob_end_clean();
    return $result;

};