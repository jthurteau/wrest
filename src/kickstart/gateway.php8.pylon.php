<?php

/**
 * Gateway pylon, specifies an install path and optional bulb
 *
 * PHP version 8
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/gateway.php8.pylon.php
 * @link   install:public/index.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

(static function () {
    try {
        $installPath = realpath('..');
        $canister = [
            'installPath' => $installPath,
            'bulb' => [ //#NOTE first valid match is used
                'local-dev.debug',
                'json:/var/www/storage/{$applicationHandle}/cache',
                'app'
            ],
        ];
        $tetherPath = "{$installPath}/src/kickstart/gateway.tether.php";
        $fileException = new Exception($tetherPath);
        is_readable($tetherPath)
            || throw new Exception('Gateway Unavailable', 127, $fileException);
        $gatewayTether = require($tetherPath);
        return is_callable($gatewayTether) ? $gatewayTether($canister) : $gatewayTether;
    } catch (Error | Exception $e) {
        header('HTTP/1.0 500 Internal Server Error');
        header('Saf-Meditation-State: pylon');
        exit(
            $canister
                && (is_array($canister) || $canister instanceof ArrayAccess)
                && key_exists('vent', $canister)
                && is_callable($canister['vent'])
            ? $canister['vent'](['fatalMeditation' => $e])
            : (
                method_exists($e, 'getPublicMessage')
                ? $e->getPublicMessage()
                : (get_class($e) . ': ' . $e->getMessage())
            )
        );
    }
})();
