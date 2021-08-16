<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * saf kickstart tether
 * @link saf.src:kick.tether.php
 */

declare(strict_types=1);

return function (&$options = []){
    require_once(__DIR__ . '/Kickstart.php');
    return Saf\Kickstart::go($options);
};