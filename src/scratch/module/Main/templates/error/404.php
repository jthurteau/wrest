<?php

use Saf\Debug;

$this->layout('layout::default', ['title' => '404 Not Found']);

if (class_exists('Saf\Agent', false)) {
    //die(Debug::stringR(Saf\Agent::getOptions()));
    // $options = Saf\Agent::getOptions();
    // Saf\Agent::reInstall($options);
    // return;
}

?>
<pre><?php
print(Debug::stringR(
    __FILE__, __LINE__, 
    array_keys(get_defined_vars()), 
    get_class(get_defined_vars()['request']),
    get_defined_vars()['request']->getRequestTarget(),
    get_defined_vars()['request']->getServerParams(),
    get_defined_vars()['request']->getAttributes(),
    get_defined_vars()['request']
));
?></pre>