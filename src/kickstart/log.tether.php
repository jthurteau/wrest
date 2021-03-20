<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * log read script
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

return function ( #TODO #PHP8 allows throw as an expression
    array &$canister = []
){
    $request = key_exists('resolverRest', $canister) ? explode('/', $canister['resolverRest']) : [];
    key_exists('logModules', $canister) 
        || ($canister['logModules'] = [
            'snoop' => 'Snoop Log', //#TODO make this not hard coded.
        ]);
    $logFile = $request && count($request) > 0 ? $request[0] : 'index';
    $logHandler = strpos($logFile, '.') === 0 ? substr($logFile, 1) : 'log';
    $rest = $logHandler == 'log' ? $request : array_slice($request, 1);
    return $canister['modulate']($logHandler, $rest); 
};