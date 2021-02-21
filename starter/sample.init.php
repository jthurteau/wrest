<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * instance script, accepts an optional canister then
 * then starts the foundation script
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

return function ( #TODO #PHP8 allows throw as an expression
    array $canister = [],
    string $defaultApp = null,
    string $defaultFoundationPath = null
){
	try{
        $file = 
            array_key_exists('foundationFile', $canister) 
                ? $canister['foundationFile'] 
                : 'kick';
        $applicationRoot = 
            array_key_exists('applicationRoot', $canister) 
                ? $canister['applicationRoot'] 
                : '/var/www/application';
        if (is_null($defaultFoundationPath)) {
            $defaultFoundationPath = (
                file_exists(dirname(__FILE__) . "/vendor/Saf/src/{$file}.php")
                    ? dirname(__FILE__) . "/vendor/Saf/src/{$file}.php"
                    : "{$applicationRoot}/vendor/Saf/src"
            );
        }
        $path = 
            array_key_exists('founationPath', $canister) 
                ? $canister['founationPath'] 
                : $defaultFoundationPath;
        $foundation = "{$path}/{$file}.php";
        if (!file_exists($foundation)) {
            throw new Exception('Application foundation is missing.');
        }
        if (!is_readable($foundation)) {
            throw new Exception('Application foundation is inaccessible.');
        }
        $app = 
            array_key_exists('mainApp', $canister) 
                ? $canister['mainApp'] 
                : $defaultApp;
        $kickstart = require_once($foundation);
        if (is_callable($kickstart)) {
            $kickstart($app, $canister);
        }
	} catch (Exception $e) {
		header('HTTP/1.0 500 Internal Server Error');
		die($e->getMessage());
	}
};