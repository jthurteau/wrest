<?php

/**
 * Localizer Root (injects custom local environment)
 *
 * PHP version 8
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/example.php8.debug.root.php
 * @link   install:local-dev.debug.root.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

return (static function(){
    error_reporting(E_ALL | E_STRICT);

    $app = require(__DIR__ . '/app.root.php');

    $debug = [
        'applicationHandle' => 'ems-tools', // #NOTE(2)  basename(__DIR__) detection
        // 'applicationHandle' => basename(__DIR__), //# doesn't work in all local-dev deployment options
        'environmentName' => 'dev',
        'localDevEnabled' => true,
        'resolvableTools'=> ['log'],
        'applicationSuggestedPort' => '8080',
        'throwMeditations' => true,
        'psrAutoloading' => true,
        'applicationEnv' => 'local-dev',
        'vendorPath' => '/opt/application/vendor',
        //#COMMON
        'forceDebug' => true,
        'enableDoctor' => true,
        'snoopLog' => true, #boolean to enable/disable, or string to enable with specified log path
        #NOTE not used yet#'snoopLogPath' => '/var/www/storage/rooms/',
        'gatewayVent' => function($result, &$canister = null) {
            (require __DIR__ . '/local-dev.debug.vent.php')($result, $canister);
            return 1;
        }
    ] + $app;  //#NOTE former overrides latter

    $debugConstants = [
            'Saf\AUTH_SIMULATED_USER' => 'jthurtea',
    ];
    
    key_exists('stdInlets', $debug) || $debug['stdInlets'] = []; 
    key_exists('const', $debug['stdInlets']) 
        || $debug['stdInlets']['const'] = [];
    $debug['stdInlets']['const'] = $debugConstants + $debug['stdInlets']['const'];

    $debugTools = ['doctor'];
    $debug['inlineTools'] =
        key_exists('inlineTools', $debug)
        ? array_unique(array_merge($debugTools, $debug['inlineTools']))
        : $debugTools;
    return $debug;
})();