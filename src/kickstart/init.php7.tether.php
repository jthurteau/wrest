<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * init tether, binds a canister to core callables
 * @link saf.src:kickstart/init.php7.tether.php
 * @link install:kickstart/init.tether.php
 */

declare(strict_types=1);

return function (&$canister) {
    static $init = null; #NOTE static closure vars only get assigned once.
    if (!is_array($canister) && !($canister instanceof ArrayAccess)) {
        throw new Exception('Initialization Canister Invalid');
    }
    if (!$init) {
        key_exists('installed', $canister) || ($canister['installed'] = []);
        $canister['installed']['install'] = __FILE__;
        $canister['install'] = function($util) use (&$canister) {
            is_array($util) || $util = [$util];
            if (!key_exists('installPath', $canister) || !is_string($canister['installPath'])) {
                throw new Exception('Agent installer misconfigured.');
            }
            foreach ($util as $u) {
                $file = is_string($u) ? realpath("{$canister['installPath']}/src/kickstart/installable/{$u}.php") : null;
                if($file && !is_readable($file)) {
                    throw new Exception("Agent installer:{$u} missing.", 127, new Exception($file));
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
                        key_exists($u, $canister['installed']) || ($canister['installed'][$u] = $file);
                    } else {
                        throw new Exception("Agent installer:{$u} invalid.", 127, new Exception($file));
                    }
                }
            }
        };
        $canister['installed']['uninstall'] = __FILE__;
        $canister['uninstall'] = function () use (&$canister){
            foreach ($canister as $key => $value) {
                if (is_callable($canister[$key])) {
                    unset($canister[$key]);
                    if (key_exists($key, $canister['installed'])) {
                        unset($canister['installed'][$key]);
                    }
                }
            }
        };
        $canister['installed']['shell'] = __FILE__;
        $canister['shell'] = function &() use (&$canister){
            $shell = [];
            foreach ($canister as $key => $value) {
                if ('installed' == $key) {
                    $shell['previouslyInstalled'] = $canister[$key];
                } elseif (!is_callable($canister[$key])) {
                    $shell[$key] = &$canister[$key];
                }
            }
            return $shell;
        };
        $required = 
            key_exists('requires', $canister) 
            ? $canister['requires']
            : ['tether','root','vent','merge'];
        $canister['install']($required);
    }
    $init = true;
};