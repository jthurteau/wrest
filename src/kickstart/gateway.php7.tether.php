<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * web gateway tether
 */

declare(strict_types=1);

return static function (&$canister = []) {
	try{
		if (!is_array($canister) && !($canister instanceof ArrayAccess)) {
			throw new Exception('Gateway Canister Invalid');
		}
		key_exists('installPath', $canister) 
			|| ($canister['installPath'] = realpath('..'));
		key_exists('gatewayVent', $canister) 
			|| ($canister['gatewayVent'] = 'exception');
		$defaultVector = 
			is_readable("{$canister['installPath']}/local-instance.tether.php") 
			? 'local-instance' 
			: 'instance';
		key_exists('gatewayVector', $canister) 
			|| ($canister['gatewayVector'] = $defaultVector);
		if (!key_exists('tether', $canister) || is_callable($canister['tether'])) {
			$initPath = __DIR__ . "/init.php.tether.php";
			$initTether = is_readable($initPath) ? require($initPath) : null;
			if (!is_callable($initTether)) {
				throw new Exception('Initialization failed.', 126, new Exception($initPath));
			}
			$initTether($canister);
		}
		$vectorFail = 'Entry vector ({$}) unavailable.';
		return $canister['tether']("{$canister['gatewayVector']}.tether", $vectorFail);
	} catch (Error | Exception $e) {
		if (!is_array($canister) && !($canister instanceof ArrayAccess)) {
			$canister = [];
		}
		$previousErrorHandler = set_exception_handler(null);
		if (class_exists('Whoops\Run', false)) { #TODO move this into Saf/Framework/Mode/Mezzio::run
			$previousErrorHandler && $previousErrorHandler($e);
			return;
		}
		header('HTTP/1.0 500 Internal Server Error');
		header('Saf-Meditation-State: gateway');
		$canister['fatalMeditation'] = $e;
		$ventResult = 
			key_exists('tether', $canister) 
			? $canister['tether']("{$canister['gatewayVent']}.vent")
			: null;
		$allowLeak = (
			(key_exists('localDevEnabled', $canister) && $canister['localDevEnabled'])
			|| (key_exists('enableDebug', $canister) && $canister['enableDebug'])
			|| (key_exists('forceDebug', $canister) && $canister['forceDebug'])
		);
		$ventResult || print($allowLeak ? $e->getMessage() : 'Critical Application Error');
		$previousErrorHandler && $previousErrorHandler($e);
	}
};