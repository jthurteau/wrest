<?php
/**
 * Trace Stack Debug Vent
 *
 * PHP version 7
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/example.vent.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

//return (static function(array &$stack, array|\Saf\Canister|null &$canister = null){
//return (static function(array &$stack, &$canister = null){
return (static function(array &$stack, &$canister = null){
    $canisterEntryVent = require __DIR__ . '/value-entry.vent.php';
?>
            <div class="trace">
<?php
    foreach ($stack as $entry => &$trace) {
?>
                <div class="trace-entry">
                    <span class="entry-number">[<?php print($entry); ?>]</span>
                    <span class="entry-location">
<?php 
        if(key_exists('file', $trace)) {
?>
                        <span class="location-file"><?php print($trace['file']); ?></span><?php 
        }
        if(key_exists('line', $trace)) {
?>
                        <span class="location-line"> (<?php print($trace['line']); ?>) </span><?php
        }
        if (key_exists('class', $trace)) {
?><span class="location-context-class"><?php print($trace['class']); ?></span><?php
        }
        if (key_exists('type', $trace)) {
?><span class="location-context-type"><?php print($trace['type']); ?></span><?php
        }
        if (key_exists('function', $trace)) {
?><span class="location-context-function"><?php print($trace['function']); ?></span>
<?php
        }
?>
                    </span>
                    <div class="context-params">
<?php
        if (key_exists('args', $trace)) { //#NOTE ternarys break return by reference
            $args = &$trace['args'];
        } else {
            $args = [];
        }
        foreach($args as $argn => &$argv) {
?>
                        <div class="param-entry">
                            <div class="param-number">[<?php print($argn); ?>]</div>
                            <div class="param-value"><?php $canisterEntryVent($argv, $canister); ?></div>
        </div>
<?php
        }
?>
                    </div>
                </div>
<?php
    }
?>
            </div>
<?php
});