<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * sample transaction kickstart pylon, specifies an install path and optional bulb
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

(static function($installPath, $requiredBulb) {
	try{
		$tetherPath = "{$installPath}/src/kickstart/gateway.tether.php";
		if (!is_readable($tetherPath)) {
			$fileException = new Exception($tetherPath);
			throw new Exception('Gateway Unavailable', 127, $fileException);
		}
		$bulbPath = "{$installPath}/{$requiredBulb}.php";
		if (!is_readable($bulbPath)) {
			$fileException = new Exception($bulbPath);
			throw new Exception('Root Unavailable', 127, $fileException);
		}
		$root = require($bulbPath);
		if (!is_array($root)) {
			$fileException = new Exception($bulbPath);
			throw new Exception('Gateway Root Invalid', 127, $fileException);
		}
		return (require($tetherPath))($root);
	} catch (Error | Exception $e) {
		header('HTTP/1.0 500 Internal Server Error');
		header('Saf-Meditation-State: pylon');
		die($e->getMessage());
	}
})('..', 'local-instance.root');