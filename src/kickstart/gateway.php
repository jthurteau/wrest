<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * sample web gateway closure, invoked by an optional pylon script 
 * then teathers the canister from bulb/seed
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

(static function () { //#NOTE gateways do not accept arguments, except when seeded
	try{
		$canister = func_get_arg(0);
		key_exists('installPath', $canister) || ($canister['installPath'] = '..');
		key_exists('gatewayVent', $canister) || ($canister['gatewayVent'] = 'exception');
		key_exists('gatewayVector', $canister) || ($canister['gatewayVector'] = 'local-instance');
		(function (&$canister, string $path) {
			$init = "{$path}/init.tether.php";
			$initTether = file_exists($init) && is_readable($init) ? require($init) : null;
			if (!is_callable($initTether)) {
				throw new Exception('Initialization failed.', 127, new Exception($init));
			}
			$initTether($canister);
		})($canister, $canister['installPath']);
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
//#OPTION disable seeds
//})([]);
//#OPTION enable local-dev bulb
})((static function (string $rootPath, string $localDevBulb) {
	$localizationScript = "{$rootPath}/{$localDevBulb}.root.php";
	if (!file_exists($localizationScript)) {
		return [];
	} elseif (!is_readable($localizationScript)) {
		return ['invalidSeed' => [$localDevBulb => 'Localization bulb inaccessible']];
		die($e->getMessage());
	}
	$bulb = require($localizationScript);
	return 
		is_array($bulb) 
		? ['localDevEnabled' => true] + $bulb
		: ['invalidSeed' => [$localDevBulb => 'Localization bulb invalid']];
})('..', 'local-dev'));
//#OPTION enable cache seed
// })((static function (string $rootPath, string $cacheSeed) {
// 	$cacheFinderScript = "{$rootPath}/{$cacheSeed}.root.php";
// 	if (!file_exists($cacheFinderScript)) {
// 		return [];
// 	} elseif (!is_readable($cacheFinderScript)) {
// 		return ['invalidSeed' => [$cacheSeed => 'Cache seed inaccessible']];
// 	}
// 	$seed = require($cacheFinderScript);
// 	return 
// 		is_array($seed) 
// 		? ['localDevEnabled' => true] + $seed 
// 		: ['invalidSeed' => [$cacheSeed => 'Cache seed invalid']];
// })('/var/www/storage/app-name/', 'kickstart-cache'));
//#OPTION enable both?
// ...
//#ENDOPTIONS