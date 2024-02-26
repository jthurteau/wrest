<?php

/**
 * Localizer Vent (replaces default gateway vent)
 *
 * PHP version 7
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/example.vent.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

//return (static function(mixed $value, array|\Saf\Canister|null &$canister = null){
return (static function($value, &$canister = null){
    $additionalEntryValueClasses =
        is_scalar($value)
        ? ' value-scalar'
        : '';
?>
            <div class="entry-value<?php print($additionalEntryValueClasses); ?>">
<?php
            if ($value === $canister) {
?>
                <span class="canister-match">CURRENT_CANISTER</span>
<?php
            } elseif (is_object($value)) {
?>
                <span class="entry-type-object">(<?php print(gettype($value)); ?>)</span>
                <div class="value-object"><?php (require __DIR__ . '/object.vent.php')($value, $canister); ?></div>
<?php
            } elseif (is_array($value)) {
?>
                <span class="entry-type">
                    (<?php print(gettype($value))?>:<?php print(count($value)); ?>)
                </span>
                <div class="value-array"><?php (require __DIR__ . '/array.vent.php')($value, $canister); ?></div>
<?php
            } else {
?>
                <div class="value-<?php print(strtolower(gettype($value))); ?>">
                    <span class="entry-type">(<?php print(gettype($value)); ?>)</span>
                    <span class="highlight"><?php print($value); ?></span> 
                </div>
<?php
            }
?>
            </div>
<?php
});