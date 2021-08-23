<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * gateway pylon, specifies an install path and optional bulb
 * @link saf.src:kickstart/gateway.php7.pylon.php
 * @link install:public/index.php
 */

declare(strict_types=1);

(static function() {
	try{
        $installPath = realpath('..');
        $pylonVector = 'gateway';
		$canister = [
			'installPath' => $installPath,
            'bulb' => 'app',
		];
		$tetherPath = "{$installPath}/src/kickstart/{$pylonVector}.tether.php";
		$fileException = new Exception($tetherPath);
		if (!is_readable($tetherPath)) {
			throw new Exception('Gateway Unavailable', 127, $fileException);
		}
		$gatewayTether = require($tetherPath);
		if (!is_callable($gatewayTether)) {
			throw new Exception('Gateway Invalid', 126, $fileException);
		}
		return $gatewayTether($canister);
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