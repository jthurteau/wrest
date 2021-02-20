<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * sample localizer for SAF, performs low-level environment customization
 * and optionally returns a "canister" of seed data for the instance
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', TRUE);

return [
    'forceDebug' => true,
    'applicationSuggestedPort' => '8080',
    //'applicationRoot' => '/opt/application/',
    //'foundationPath' => '/var/www/application/vendor/Saf/src',
    //'foundationScript' => 'kick',
    //'mainScript' => 'main',
];