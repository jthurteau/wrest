<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * rooting script to set instance identity based on web host
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

$defaultEnv = 'production';
$defaultStatus = 'online';
$sapi = defined('PHP_SAPI') ? constant('PHP_SAPI') : null;
$hostLookup = 'SERVER_NAME';
$source = isset($_SERVER) ? $_SERVER : null;
$copy = [
    'REQUEST_TIME_FLOAT' => 'startTime', #NOTE this wouldn't play well with cache
    'PHP_SELF' => 'uriMirror',
    'SCRIPT_NAME' => 'uriMirror',
    'SERVER_PORT' => 'applicationSuggestedPort',
    'PHP_APPLICATION_ENV' => 'applicationEnv',
    'PHP_APPLICATION_STATUS' => 'applicationStatus',
    'PHP_APPLICATION_ID' => 'applicationId',
    'PHP_BASE_URI' => 'baseUri',
    'PHP_RESOLVER_PYLON' => 'resolverPylon',
    'PHP_RESOLVER_FORWARD' => 'resolverForward',
];

$host = [
    'hostSapi' => $sapi,
    'hostEnvSource' => is_null($source) ? null : 'server',
];

if ($source){
    foreach($copy as $key => $target){
        key_exists($key, $source) && !key_exists($target, $host) && ($host[$target] = $source[$key]);
    }
}
key_exists('applicationEnv', $host) || ($host['applicationEnv'] = $defaultEnv);
key_exists('applicationStatus', $host) || ($host['applicationStatus'] = $defaultStatus);

return $host;