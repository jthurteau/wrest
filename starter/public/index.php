<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * gateway closure, roots an optional localization canister 
 * then teathers the canister to an instance script
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

(static function( #TODO #PHP8 allows throw as an expression
	string $vector, #an init script (*.tether.php) in project root
	string $installPath, #relative or absolute path to project root
	$canister = [], #array of data to initialize the canister or an optional non-cached root file #TODO #PHP8 allows array|string typing
	string $vent = 'exception' #a view script to use in the case of a fatal exception
){
	try{
		$exceptionView = "{$installPath}/{$vent}.view.php";
		$localizer = is_string($canister) ? "{$installPath}/{$canister}.root.php" : false;
		if ($localizer && file_exists($localizer)){
			if (!is_readable($localizer)) {
				throw new Exception("Application localization ({$canister}) inaccessible.");
			}
			$canister = require_once($localizer);
		}

		is_array($canister) || ($canister = []);
		key_exists('meditationScript', $canister) || ($canister['meditationScript'] = $exceptionView);

		$vectorTether = "{$installPath}/{$vector}.tether.php";
		if (!file_exists($vectorTether)) {
			throw new Exception("Application entry ({$vector}) not recognized.");
		} elseif (!is_readable($vectorTether)) {
			throw new Exception("Application entry ({$vector}) is inaccessible.");
		}
		$vector = require_once($vectorTether);
		is_callable($vector) && $vector($canister);
	} catch (Exception $e) {
		print('<pre>');
		print_r($e); die;
		header('HTTP/1.0 500 Internal Server Error');
		header('Saf-Meditation-State: gateway');
		file_exists($exceptionView) && is_readable($exceptionView) 
		? include($exceptionView)
		: die($e->getMessage());
	}
})('local-instance', '..', 'local-dev');