<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * sample multiviews resolver pylon, 
 * set resolver hint and forwards to main pylon or gateway script
 * @link saf.src:kickstart/resolver.php7.pylon.php
 */

define('Saf\RESOLVER_PYLON', basename(__FILE__, '.php'));
require_once('index.php');
// return (require_once('../src/kickstart/pylon.tether.php'))(['gatewayResolver' =>  basename(__FILE__, '.php')]);