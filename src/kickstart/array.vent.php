<?php

/**
 * Array Debug Vent
 *
 * PHP version 7
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/example.vent.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

//return (static function(array $value, array|\Saf\Canister|null &$canister = null){
return (static function(array $value, &$canister = null){
?>
            <div class="serial">
<?php
    $canisterEntryVent = require __DIR__ . '/value-entry.vent.php';
    foreach($value as $key => &$subValue) {
?>
                <div class="array-key"><?php print($key); ?>
                    <span class="array-key-type"> (<?php print(gettype($key)); ?>)</span>
                </div>
                <div class="array-value">
<?php
        $canisterEntryVent($subValue, $canister);
?>
                </div>
<?php
    }
?>
            </div>
<?php
});