<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing Mezzio instances
 */

namespace Saf\Framework\Mode;

use Saf\Framework\Manager;

require_once(dirname(__DIR__) . '/Manager.php');

class LaminasMvc extends Manager{

    public static function detect($instance, $options = null)
    {
        return false;
        print_r(['detect lainas-mvc', $instance, $options]); die;
    }
    
    public static function autoload($instance, $options = null)
    {

    }

    public static function run($agentId, $options = null)
    {
        // $application = \Saf_Application::load(APPLICATION_ID, APPLICATION_ENV, true);
        print_r(['running laminas-mvc application', $instance, $options]); die;

        // if (! class_exists(Application::class)) {
        //     throw new RuntimeException(
        //         "Unable to load application.\n"
        //         . "- Type `composer install` if you are developing locally.\n"
        //         . "- Type `vagrant ssh -c 'composer install'` if you are using Vagrant.\n"
        //         . "- Type `docker-compose run laminas composer install` if you are using Docker.\n"
        //     );
        // }
        
        // // Retrieve configuration
        // $appConfig = require __DIR__ . '/../config/application.config.php';
        // if (file_exists(__DIR__ . '/../config/development.config.php')) {
        //     $appConfig = ArrayUtils::merge($appConfig, require __DIR__ . '/../config/development.config.php');
        // }
        
        // // Run the application!
        // Application::init($appConfig)->run();
    }

    public static function preboot($instance, $options = [], $prebooted = [])
    {

    }

}