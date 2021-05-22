<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * sample multiviews pylon, 
 * set resolver hint and forwards to main pylon or gateway script
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */
define('Saf\RESOLVER_PYLON', basename(__FILE__, '.php'));
require_once('index.php');