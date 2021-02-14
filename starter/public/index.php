<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * gateway closure for SAF, roots an optional localizatoin canister 
 * then teathers the canister to an instance script
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

(static function( #TODO #PHP8 allows throw as an expression
	string $installPath, #relative or absolute path to project root
	string $instance, #instance name, maps to an init script in project root
	$canister = 'local-dev' #TODO #PHP8 allows string|array type hinting
){
	try{
		$localizer = is_string($canister) ? "${installPath}/${canister}.php" : false;
		if ($localizer && file_exists($localizer)){
			if (!is_readable($localizer)) {
				throw new Exception("Application localization (${canister}) inaccessible.");
			}
			$canister = require_once($localizer);
		}

		$instanceEnvScript = "${installPath}/{$instance}.init.php";
		if (!file_exists($instanceEnvScript)) {
			throw new Exception("Application instance (${instance}) not recognized.");
		}
		if (!is_readable($instanceEnvScript)) {
			throw new Exception("Application instance (${instance}) is inaccessible.");
		}
		$result = require_once($instanceEnvScript);
		if (is_callable($result)) {
			$result(is_array($canister) ? $canister : [
				'canisterFile' => $localizer,
				'invalidCanisterResult' => $canister
			]);
		}
	} catch (Exception $e) {
		header('HTTP/1.0 500 Internal Server Error');
		header('Saf-Meditation-State: gateway');
		$fallbackView = "${installPath}/views/gateway.php";
		$meta = "${installPath}/composer-meta.php";
		if ($meta && file_exists($meta) && is_readable($meta)) {
			$metaCanister = require_once($meta);
			if (is_array($metaCanister)) {
				$canister = is_array($canister) ? ( $canister + $metaCanister) : $metaCanister;
			}
		}
		if (file_exists($fallbackView) && is_readable($fallbackView)) {
			include($fallbackView);
		} else {
			die($e->getMessage());
		}
	}
})('..', 'local-instance');