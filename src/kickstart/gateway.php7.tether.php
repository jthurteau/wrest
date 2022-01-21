<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * web gateway tether
 * @link saf.src:kickstart/gateway.php7.tether.php
 * @link install:kickstart/gateway.tether.php
 */

declare(strict_types=1);

return static function (&$canister = []) {
    try{
        if (!is_array($canister) && !($canister instanceof ArrayAccess)) {
            throw new Exception('Gateway Canister Invalid', 126);
        }
        key_exists('installPath', $canister) 
            || ($canister['installPath'] = realpath('..'));
        key_exists('kickPath', $canister)
            || ($canister['kickPath'] = __DIR__);
        key_exists('gatewayVector', $canister) 
            || ($canister['gatewayVector'] = 'instance');
        if (!key_exists('tether', $canister) || is_callable($canister['tether'])) {
            $initPath = "{$canister['kickPath']}/init.tether.php";
            $initTether = is_readable($initPath) ? require($initPath) : null;
            if (!is_callable($initTether)) {
                throw new Exception('Gateway Failed', 126, new Exception($initPath));
            }
            $initTether($canister);
        }
        //#NOTE this doesn't handle multiple bulb options (needs to use :first)
        key_exists('bulb', $canister) && $canister['merge']($canister['bulb']);
        $vectorFail = 'Gateway vector ({$}) unavailable';
        return $canister['tether']("{$canister['gatewayVector']}", $vectorFail);
    } catch (Error | Exception $e) {
        if (!is_array($canister) && !($canister instanceof ArrayAccess)) {
            $canister = [];
        }
        $previousExceptionHandler = set_exception_handler(null);
        // if (class_exists('Whoops\Run', false)) { #TODO move this into Saf/Framework/Mode/Mezzio::run
        //     $previousErrorHandler && $previousErrorHandler($e);
        //     return;
        // }
        header('HTTP/1.0 500 Internal Server Error');
        header('Saf-Meditation-State: gateway');
        $canister['fatalMeditation'] = $e;
        $vent = 
            key_exists('gatewayVent', $canister)
            ? $canister['gatewayVent'] 
            : 'src/kickstart/exception';
        $ventResult = 
            key_exists('vent', $canister) && is_callable($canister['vent'])
            ? $canister['vent'](['fatalMeditation' => $e], $vent)
            : null;
        $allowLeak = (
            (key_exists('localDevEnabled', $canister) && $canister['localDevEnabled'])
            || (key_exists('enableDebug', $canister) && $canister['enableDebug'])
            || (key_exists('forceDebug', $canister) && $canister['forceDebug'])
        );
        $ventResult || print($allowLeak ? $e->getMessage() : 'Critical Application Error');
        $previousExceptionHandler && $previousExceptionHandler($e);
    }
};