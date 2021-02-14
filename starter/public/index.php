<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * initialization closure for SAF, loads an optional canister script then
 * passes the canister to an instance script
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

(static function( #TODO #PHP8 allows throw as an expression
	string $installPath, #relative or absolute path to project root
	string $instance = 'local-instance', #instance name, maps to an init script in project root
	$canister = 'local-dev' #TODO #PHP8 allows string|array type hinting
){
	try{
		$localizer = is_string($canister) ? "{$installPath}/{$canister}.php" : false;
		if ($localizer && file_exists($localizer)){
			if (!is_readable($localizer)) {
				throw new Exception("Application localization ({$canister}) inaccessible.");
			}
			$canister = require_once($localizer);
		}

		// $instance = strtolower(preg_replace(
		// 	"/[^A-Za-z0-9-]/", '', str_replace(['_', ' '],'-', $instance)
		// ));
		$instanceEnvScript = "{$installPath}/{$instance}.init.php";
		if (!file_exists($instanceEnvScript)) {
			throw new Exception("Application instance ({$instance}) not recognized.");
		}
		if (!is_readable($instanceEnvScript)) {
			throw new Exception("Application instance ({$instance}) is inaccessible.");
		}
		$instance = require_once($instanceEnvScript);
		if (is_callable($instance)) {
			$instance(is_array($canister) ? $canister : [
				'canisterFile' => $localizer,
				'invalidCanisterResult' => $canister
			]);
		}
	} catch (Exception $e) {
		header('HTTP/1.0 500 Internal Server Error');
		die($e->getMessage());
	}
})('..');