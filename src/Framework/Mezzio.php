<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing Mezzio instances
 */

namespace Saf\Framework;

use Saf\Framework\Manager;

require_once(dirname(dirname(__FILE__)) . '/Framework/Manager.php');

class Mezzio extends Manager{

    public static function detect($instance, $options = null)
    {
        return false;
        print_r(['detect mezzio', $instance, $options]); die;
    }
    
    public static function autoload($instance, $options = null)
    {

    }

    public static function run($instance, $options = null)
    {
        // $application = \Saf_Application::load(APPLICATION_ID, APPLICATION_ENV, true);
        print_r(['running mezzio application', $instance, $options]); //die;

        // /** @var \Psr\Container\ContainerInterface $container */
        // $container = require 'config/container.php';

        // /** @var \Mezzio\Application $app */
        // $app = $container->get(\Mezzio\Application::class);
        // $factory = $container->get(\Mezzio\MiddlewareFactory::class);

        // // Execute programmatic/declarative middleware pipeline and routing
        // // configuration statements
        // (require 'config/pipeline.php')($app, $factory, $container);
        // (require 'config/routes.php')($app, $factory, $container);

        // $app->run();
    }

    public static function preboot($instance, $options = [], $prebooted = [])
    {

    }

}