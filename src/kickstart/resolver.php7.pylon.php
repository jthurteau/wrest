<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * sample multiviews resolver pylon, 
 * set resolver hint and forwards to main pylon or gateway script
 */

define('Saf\RESOLVER_PYLON', basename(__FILE__, '.php'));
require_once('index.php');