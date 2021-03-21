<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * web gateway tether
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

return static function ($canister) {
	try{
		key_exists('installPath', $canister) || ($canister['installPath'] = '..');
		key_exists('gatewayVent', $canister) || ($canister['gatewayVent'] = 'exception');
		$defaultVector = 
			is_readable("{$canister['installPath']}/local-instance.tether.php") 
			? 'local-instance' 
			: 'instance';
		key_exists('gatewayVector', $canister) || ($canister['gatewayVector'] = $defaultVector);

		$init_path = dirname(__FILE__) . "/init.tether.php";
		$initTether = is_readable($init_path) ? require($init_path) : null;
		if (!is_callable($initTether)) {
			throw new Exception('Initialization failed.', 127, new Exception($init_path));
		}
		$initTether($canister);
		$vectorFail = 'Entry vector ({$}) unavailable.';
		$vectorResult =	$canister['tether']("{$canister['gatewayVector']}.tether", $vectorFail);
	} catch (Error | Exception $e) {
		$previous = set_exception_handler(null);
		$previous && $previous($e); //#TODO #2.0.0 maybe do this at the end after ventResult?
		if (class_exists('Whoops\Run', false)) {
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
		$ventResult || die($allowLeak ? $e->getMessage() : 'Critical Application Error');
	}
};