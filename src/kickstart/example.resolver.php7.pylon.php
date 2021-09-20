<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * {$2 = sample multiviews resolver pylon, 
 * set resolver hint and forwards to main pylon or gateway script}
 * @link saf.src:kickstart/example.resolver.php7.pylon.php
 * @link install:public/{$1}.php
 */

return (require_once('../src/kickstart/pylon.tether.php'))([
    'gatewayResolver' =>  basename(__FILE__, '.php'),
]);