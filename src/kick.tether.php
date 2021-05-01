<?php

declare(strict_types=1);

require_once(dirname(__FILE__) . '/Kickstart.php');
return function (&$options = [])
{
    return Saf\Kickstart::go($options);
};