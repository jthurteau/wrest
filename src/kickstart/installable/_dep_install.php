<?php

/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * installable closure to add install installable tool,
 * for use with $canister['install']
 * #DEPRECATE this is built into the init to cut down on file spidering
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

if (!isset($canister)) {
    throw new Exception("Calling install:" . basename(__FILE__, '.php') . " out of context.");
}
return function($util) use (&$canister) {
    is_array($util) || $util = [$util];
    if (!key_exists('installPath', $canister) || !is_string($canister['installPath'])) {
        throw new Exception('Application agent installer misconfigured.');
    }
    foreach ($util as $u) {
        $file = is_string($u) ? "{$canister['installPath']}/src/tools/installable/{$u}.php" : null;
        if($file && !file_exists($file) || !is_readable($file)) {
            throw new Exception("Application agent installer:{$u} missing.", 127, new Exception($file));
        } else {
            $result = 
                is_string($file) 
                ? (
                    key_exists('validate', $canister) 
                    ? $canister('validate')($file) 
                    : require($file)
                ) : null; #TODO #2.0.0 add support for callable installers
            if (is_callable($result)) {
                $canister[$u] = $result;
            } else {
                throw new Exception("Application agent installer:{$u} invalid.", 127, new Exception($file));
            }
        }
    }
};