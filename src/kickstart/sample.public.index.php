<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * sample transaction kickstart, specifies an install path and optional bulb
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

(static function($install_path, $bulb = null) {
	try{
		$kick_path = "{$install_path}/src/kickstart";
		$gateway_path = "{$kick_path}/gateway.php";
		if (!is_readable($gateway_path)) {
			throw new Exception('Gateway Unavailable', 127);
		}
		$seed_path = $bulb ? "{$kick_path}/{$bulb}.php" : null;
		$seed = is_readable($seed_path) ? (require $seed_path) : null;
		if ($bulb && !is_array($seed)) {
			$invalid_reason =
				is_readable($seed_path)
				? 'Gateway Root Inaccessible'
				: 'Gateway Root Invalid';
			$seed = [['invalidSeed' => [$bulb => $invalid_reason]]];
		}
		(require($gateway_path))(...$seed);
	} catch (Error | Exception $e) {
		header('HTTP/1.0 500 Internal Server Error');
		header('Saf-Meditation-State: gateway');
		die($e->getMessage());
	}
})('..', 'dev.bulb');