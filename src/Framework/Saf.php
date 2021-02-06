<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing SAF instances
 */

namespace Saf\Framework;

use Saf\Framework\Manager;

require_once(dirname(dirname(__FILE__)) . '/Framework/Manager.php');

class Saf extends Manager{

    public static function detect($instance)
    {
        return true;
    }

    public static function run($instance, $options = null)
    {
        // $application = \Saf_Application::load(APPLICATION_ID, APPLICATION_ENV, true);

    }

}