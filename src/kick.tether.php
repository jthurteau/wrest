<?php

declare(strict_types=1);

require_once(dirname(__FILE__) . '/Kickstart.php');
return function (?array &$options = [])
{
    Saf\Kickstart::go($options);
};