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

return function ($trace, $data = null) {
    ob_start();
?>
    <div class="debug">
        <h2 class="header data">Vent Data</h2>
        <div class="code data">
<?php
    $dataVent = require(__DIR__ . '/value-entry.vent.php');
    $dataVent($data);
?>
        </div>
        <h2 class="header trace">Vent Trace (<?php print(count($trace)); ?>)</h2>
        <div class="code trace">
<?php
    //print_r($trace);
    $t = new Exception();
    //print_r([__FILE__,__LINE__,gettype($trace),array_keys($trace),array_keys($trace[0]), gettype($data), $t->getTraceAsString()]); die;
    $traceVent = require(__DIR__ . '/trace.vent.php');
    //print_r([__FILE__,__LINE__,gettype($trace),array_keys($trace),array_keys($trace[0]), gettype($data)]); die;
    $traceVent($trace);
?>
        </div>
    </div>
<?php
    $result = ob_get_contents();
    ob_end_clean();
    return $result;

};