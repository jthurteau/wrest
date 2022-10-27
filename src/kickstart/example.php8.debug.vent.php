<?php

/**
 * Localizer Vent (supersedes default gateway vent)
 *
 * PHP version 8
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/example.php8.debug.vent.php
 * @link   install:local-dev.debug.vent.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

return (static function ($result, array|\Saf\Canister|null &$canister = null) {
    $kickVentPath = 
        key_exists('kickPath', $canister) 
        ? "{$canister['kickPath']}/vent" 
        : (__DIR__ . '/vent');
    $localVent = function ($ventFile, $result, $canister = null) use ($kickVentPath) {
        $localVent = __DIR__ . "/local-dev.{$ventFile}";
        file_exists($localVent)
            ? (require $localVent)($result, $canister)
            : (require "{$kickVentPath}/debug/{$ventFile}")($result, $canister);
    };
?>
<html>
    <head>
        <title>Debug Vent</title>
<?php
    $localVent('html-head.debug.vent.php', $result, $canister);
?>
    </head>
    <body>
        <h1>Debug Vent Result:</h1>
        <div class="result">
            <span class="result-type">Type: <?php print(gettype($result)); ?></span>
            <div class="result-value">
<?php
    if (is_object($result)) {
?>
                <div class="result-object">
                    <?php (require "{$kickVentPath}/debug/object.vent.php")($result, $canister); ?>
                </div>
<?php
    } elseif (is_array($result)) {
?>
                <div class="result-array">
                    <?php (require "{$kickVentPath}/debug/array.vent.php")($result, $canister); ?>
                </div>
<?php
    } else {
                print($result);
    }
?>
            </div>
        </div>
        <h1>Canister</h1>
        <div class="canister">
<?php
            $canisterEntryVent = require "{$kickVentPath}/debug/value-entry.vent.php";
            foreach ($canister as $key => &$value) {
?>
            <div class="canister-entry">
                <div class="canister-key"><?php print($key); ?><span class="key-type"> (<?php print(gettype($key)); ?>)</span>: </div>
                <?php $canisterEntryVent($value); ?>
            </div>
<?php
            }
?>
        </div>
<?php
    //$localVent('html-head.debug.foot.php', $result, $canister);
?>
    </body>
</html>
<?php
});