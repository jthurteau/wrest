<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * sample gateway pylon, specifies an install path and optional bulb
 */

declare(strict_types=1);

(static function(string $installPath, ?string $bulb = null) {
	try{
		$bulbPath = $bulb ? "{$installPath}/{$bulb}.php" : null;
		$canister = 
			$bulbPath && is_readable($bulbPath) 
			? (require($bulbPath))
			: [];
		if (!is_array($canister) && !($canister instanceof ArrayAccess)) {
			$canister = ['invalidBulb' => [$bulbPath => 'Gateway Bulb Invalid']];
		}
		key_exists('installPath', $canister) || ($canister['installPath'] = $installPath);
		$tetherPath = "{$installPath}/src/kickstart/gateway.tether.php";
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
			: $e->getMessage()
		);
	}
})('..', 'local-dev.root');