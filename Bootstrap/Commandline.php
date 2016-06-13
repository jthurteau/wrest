<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Bootstrap handler for commandline applications

*******************************************************************************/
require_once(LIBRARY_PATH . '/Saf/Bootstrap.php');

class Saf_Bootstrap_Commandline extends Saf_Bootstrap
{
    public function __construct($application, $config = array())
    {
        parent::__construct($application, $config);
        /* //#TODO #2.0.0 didn't seem to help stop output to stderr
         if (APPLICATION_PROTOCOL == 'commandline') {
        ini_set('html_errors', 0);
        }
        */        
        $this->_postConstruct();
    }

}
