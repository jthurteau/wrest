<?php $this->layout('layout::default', ['title' => '404 Not Found']) ?>

<?php

if (class_exists('Saf\Agent', false)) {
    //print_r(Saf\Agent::get); die;
    // $options = Saf\Agent::getOptions();
    // Saf\Agent::reInstall($options);
    // return;
}

// require_once();
print('<pre>');
print_r([
    __FILE__, __LINE__, 
    array_keys(get_defined_vars()), 
    get_class(get_defined_vars()['request']),
    get_defined_vars()['request']->getRequestTarget(),
    get_defined_vars()['request']->getServerParams(),
    get_defined_vars()['request']->getAttributes(),
    get_defined_vars()['request']
]);