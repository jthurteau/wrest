<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * web pylon tether, 
 * accepts an optional $canister from upstream pylons and,
 * tethers the main gateway
 * @link saf.src:kickstart/pylon.php7.tether.php
 * @link install:kickstart/pylon.tether.php
 */

declare(strict_types=1);

return static function (&$canister = []) {
	try{
        $installPath = realpath('..');
		key_exists('installPath', $canister) 
            || ($canister['installPath'] = $installPath);
        key_exists('bulb', $canister) 
            || ($canister['bulb'] = [
				'local-dev.debug',
				'json:{$storageRoot}/{$applicationHandle}/cache', 
				'app'
			]);
		$tetherPath = __DIR__ . 'gateway.tether.php';
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
        $eCode = $e->getCode();
		exit(
			$canister 
				&& (is_array($canister) || $canister instanceof ArrayAccess)
				&& key_exists('vent', $canister)
				&& is_callable($canister['vent'])
			? $canister['vent'](['fatalMeditation' => $e])
			: (
				method_exists($e, 'getPublicMessage') 
				? $e->getPublicMessage()
				: (get_class($e) . ( $eCode ? "({$eCode})" : '')': ' . $e->getMessage())
			)
		);
	}
};