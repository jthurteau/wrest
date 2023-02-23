<?php

/**
 * Web gateway tether
 * 
 * PHP version 7
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link saf.src:kickstart/gateway.php7.tether.php
 * @link install:kickstart/gateway.tether.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
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
        if (
            !key_exists('tether', $canister) 
            || !is_callable($canister['tether'])
        ) {
            $initPath = "{$canister['kickPath']}/init.tether.php";
            $initTether = is_readable($initPath) ? require($initPath) : null;
            if (!is_callable($initTether)) {
                throw new Exception('Initialization failed.', 126, new Exception($initPath));
            }
            $initTether($canister);
        }
        $firstBulb = $canister['first']($canister['bulb'], 'root');
        is_array($firstBulb) && key_exists('bulb', $canister) && $canister['merge']($firstBulb);
        if (!key_exists('germinated', $canister) || !$canister['germinated']) {
            key_exists('gatewayRoots', $canister) && $canister['each']($canister['gatewayRoots'], 'deep');
            if (key_exists('stdInlets', $canister)) {
                foreach($canister['stdInlets'] as $inlet => $data) {
                    $inletPath = "{$canister['kickPath']}/{$inlet}";
                    $inletFail = 'Inlet ({$}) unavailable.';
                    $inletResult = $canister['inlet']($data, $inletPath, $inletFail);
                }
            }
            $environmentPath = 
                key_exists('environmentPath', $canister)
                ? $canister['environmentPath']
                : 'config/kickstart';
            $environmentRoot = 
                key_exists('environmentName', $canister)
                ? "{$environmentPath}/env.{$canister['environmentName']}"
                : null;
            $environmentRoot && $canister['deep']($environmentRoot);
        }
        key_exists('gatewayQuays', $canister) && $canister['each']($canister['gatewayQuays'], 'quay');
        key_exists('gatewayVector', $canister) 
            || ($canister['gatewayVector'] = 'src/kickstart/instance');
        $vectorFail = 'Entry vector ({$}) unavailable.';
        return $canister['tether']("{$canister['gatewayVector']}", $vectorFail);
    } catch (Error | Exception $e) {
        if (!is_array($canister) && !($canister instanceof ArrayAccess)) {
            $canister = [];
        }
        $previousExceptionHandler = set_exception_handler(null);
        header('HTTP/1.0 500 Internal Server Error');
        header('Saf-Meditation-State: gateway');
        $vent = 
            key_exists('gatewayVent', $canister)
            ? $canister['gatewayVent'] 
            : 'src/kickstart/exception';
        $ventResult = 
            key_exists('vent', $canister) && is_callable($canister['vent'])
            ? $canister['vent'](['fatalMeditation' => $e], $vent)
            : null;
        $allowLeak = (
            key_exists('localDevEnabled', $canister) && $canister['localDevEnabled']
            && (
                (key_exists('enableDebug', $canister) && $canister['enableDebug'])
                || (key_exists('forceDebug', $canister) && $canister['forceDebug'])
            )
        );
        $ventResult || print($allowLeak ? $e->getMessage() : 'Critical Application Error');
        $previousExceptionHandler && $previousExceptionHandler($e);
    }
};