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

(static function($installPath, $optionalBulb = null) {
	try{
		$tetherPath = "{$installPath}/src/kickstart/gateway.tether.php";
		if (!is_readable($tetherPath)) {
			throw new Exception('Gateway Unavailable', 127,  new Exception($tetherPath));
		}
		$rootPath = 
			$optionalBulb 
			? "{$installPath}/{$optionalBulb}.php" 
			: null;
		$root = 
			$optionalBulb && is_readable($rootPath) 
			? (require($rootPath))
			: [];
		return (require($tetherPath))(
			is_array($root)
			? $root
			: ['invalidRoot' => [$optionalBulb => 'Gateway Root Invalid']]
		);
	} catch (Error | Exception $e) {
		header('HTTP/1.0 500 Internal Server Error');
		header('Saf-Meditation-State: pylon');
		die($e->getMessage());
	}
})('..', 'local-dev.root');