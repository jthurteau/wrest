<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Main entry point for the application.

*******************************************************************************/
defined('APPLICATION_VERSION') || define(
	'APPLICATION_VERSION', '0.9.0'
); //#TODO #RELEASE update this each release, identifies running version

//defined('APPLICATION_INSTANCE') || define( 
//	'APPLICATION_INSTANCE', 'SAMPLE_X'
//); //#NOTE along with application_id, uniquely identifies the instance

defined('APPLICATION_ID') || define( 
	'APPLICATION_ID', 'Sample'
); //#NOTE identifies what application to load

//defined('APPLICATION_TZ') || define( 
//	'APPLICATION_TZ', 'EST5EDT'
//); //#NOTE needed if your server/php.ini isn't explicitly set

/*******************************************************************************
Ideally there is nothing to edit in this file past this point.
*******************************************************************************/
define('APPLICATION_START_TIME', microtime(TRUE));
defined('APPLICATION_LOCALIZE_TOKEN') || define( 
	'APPLICATION_LOCALIZE_TOKEN', 'localize' // or 'local-dev'
);
if(file_exists('../' . APPLICATION_LOCALIZE_TOKEN . '.php')){
	if (!is_readable('../' . APPLICATION_LOCALIZE_TOKEN . '.php')) {
		header('HTTP/1.0 500 Internal Server Error');
		die('Unable to access local initialization script.');
	}
	require_once('../' . APPLICATION_LOCALIZE_TOKEN . '.php');
}
defined('INSTALL_PATH') || define('INSTALL_PATH', realpath(dirname(__FILE__) . '/..'));
defined('LIBRARY_PATH') || define('LIBRARY_PATH', realpath(INSTALL_PATH . '/../../library'));
if ('' == trim(LIBRARY_PATH)) {
	header('HTTP/1.0 500 Internal Server Error');
	die('Unable to find the application libraries.');
} else if (!file_exists(LIBRARY_PATH . '/Saf/autoload.php')) {
	header('HTTP/1.0 500 Internal Server Error');
	die('Unable to find the application framework.');
} else if (!is_readable(LIBRARY_PATH . '/Saf/autoload.php')) {
	header('HTTP/1.0 500 Internal Server Error');
	die('Unable to access the application framework.');
}
require_once(LIBRARY_PATH . '/Saf/autoload.php');

Saf\Kickstart::kick(Saf\Kickstart::go());