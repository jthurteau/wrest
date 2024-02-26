<?php

/**
 * Object Debug Vent
 *
 * PHP version 7
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/example.vent.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

//return (static function(object $value, array|\Saf\Canister|null &$canister = null){
return (static function(object $value, &$canister = null){
?>
            <div class="serial">
<?php
            if (is_a($value, 'Error') || is_a($value, 'Exception')) {
                $trace = $value->getTrace(); //NOTE this may not need to be PBR?
?>
                <span class="thrown-class"><?php print(get_class($value)); ?></span>
                <span class="thrown-message highlight"><?php print(htmlentities($value->getMessage())); ?></span>
                <div class="thrown-value">
                    
                    <div class="thrown-trace"><?php (require __DIR__ . '/trace.vent.php')($trace, $canister); ?></div>
<?php
                if (!is_null($value->getPrevious())) {
?>
                    <span class="thrown-member">previous</span>
                    <div class="thrown-previous"><?php (require __DIR__ . '/object.vent.php')($value->getPrevious(), $canister); ?></div>
<?php
                }
?>
                </div>
<?php
            } else {
?>
                <span class="object-class"><?php print(get_class($value)); ?></span>
                <div class="object-value">
<?php
                if (true) {

                }
?>
                </div>
<?php
            }
?>
            </div>
<?php
});