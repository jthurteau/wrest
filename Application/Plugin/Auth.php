<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Handler for managing the Auth class

*******************************************************************************/
/*
 $authusers = array(
 		'jthurtea'
 );

if (
		!array_key_exists('PHP_AUTH_USER', $_SERVER)
		|| !in_array($_SERVER['PHP_AUTH_USER'], $authusers)
) {
header("HTTP/1.0 401 Unauthorized");
die('You do not have access to this application. Logged in as: '
		. (
				array_key_exists('PHP_AUTH_USER', $_SERVER)
				&& $_SERVER['PHP_AUTH_USER']
				? $_SERVER['PHP_AUTH_USER']
				: '__ NOT LOGGED IN __'
		)
);
}
*/