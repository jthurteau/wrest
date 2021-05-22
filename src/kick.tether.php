<?php

declare(strict_types=1);

require_once(__DIR__ . '/Kickstart.php');
return function (&$options = [])
{
    return Saf\Kickstart::go($options);
};