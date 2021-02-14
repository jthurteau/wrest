<?php

declare(strict_types=1);

require_once(dirname(__FILE__) . '/Kickstart.php');

return function(?array $options = [])
{
    $app = array_key_exists('mainScript', $options) ? $options['mainScript'] : null;
    $tether = Saf\Kickstart::kick(Saf\Kickstart::lace($app, $options));
    if (is_callable($tether)) {
        $tether($options);
    }
};