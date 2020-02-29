<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Main entry point for the application.

*******************************************************************************/
define('APPLICATION_START_TIME', microtime(true));
defined('APPLICATION_LOCALIZE_TOKEN') || define( 
	'APPLICATION_LOCALIZE_TOKEN', 'local-dev'
);
if(file_exists('../' . APPLICATION_LOCALIZE_TOKEN . '.php')){
	if (!is_readable('../' . APPLICATION_LOCALIZE_TOKEN . '.php')) {
		header('HTTP/1.0 500 Internal Server Error');
		die('Local initialization script present, but inaccessible.');
	}
	require_once('../' . APPLICATION_LOCALIZE_TOKEN . '.php');
}
/*******************************************************************************

Define core aspects of your application below if not handled via deployment

*******************************************************************************/

//#TODO #RELEASE update this each release, identifies running version
defined('APPLICATION_VERSION') || define(
	'APPLICATION_VERSION', '0.0.0'
); 

//#NOTE set APPLICATION_ID on initial build/deploy, avoid changing after
defined('APPLICATION_ID') || define( 
	'APPLICATION_ID', 'Sample'
);

//#NOTE set APPLICATION_INSTANCE on initial provision/deploy, avoid changing
defined('APPLICATION_INSTANCE') || define( 
	'APPLICATION_INSTANCE', 'Prime'
);

//#NOTE needed if your server/php.ini isn't explicitly set
defined('APPLICATION_TZ') || define( 
	'APPLICATION_TZ', 'GMT'
); 

/*******************************************************************************

Ideally there is nothing to edit in this file past this point.

*******************************************************************************/

defined('INSTALL_PATH') || define('INSTALL_PATH', realpath(dirname(__FILE__) . '/..'));
defined('LIBRARY_PATH') || define('LIBRARY_PATH', realpath(INSTALL_PATH . '/../../library'));
defined('VENDOR_PATH') || define('VENDOR_PATH', realpath(INSTALL_PATH . '/vendor'));

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