<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing SAF instances
 */

namespace Saf\Framework\Mode;

use Saf\Framework\Manager;
use Saf\Auto; #Saf\Framework\Manager requires Saf\Auto

require_once(dirname(dirname(__FILE__)) . '/Manager.php');

class Saf extends Manager{

    protected static $applicationDir = 'application';
    protected static $applicationMain = 'Bootstrap';
    protected static $applicationBaseClass = 'Saf_Bootstrap*';

    public static function detect($instance, $options = [])
    {
        $installPath = self::installPath($options);
        $applicationDir = self::$applicationDir;
        $applicationMain = self::$applicationMain;
        $mainPath = "{$installPath}/{$applicationDir}/{$applicationMain}.php";
        return(
            file_exists($mainPath)
            && Auto::parentClassIs($mainPath, self::$applicationBaseClass)
        ); //Zend_Application_Bootstrap_Bootstrap
    }
    
    public static function autoload($instance, $options = [])
    {

    }

    public static function preboot($instance, $options = [], $prebooted = [])
    {

    }

    public static function run($instance, $options = [])
    {
        // $application = \Saf_Application::load(APPLICATION_ID, APPLICATION_ENV, true);
        print_r(['running saf application', $instance, $options]); //die;
    }

}