<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing Mezzio instances
 */

declare(strict_types=1);

namespace Saf\Framework\Mode;

use Saf\Framework\Manager;
use Saf\Legacy\Autoloader;
use Saf\Auto; #Saf\Framework\Manager requires Saf\Auto

require_once(dirname(__DIR__) . '/Manager.php');
require_once(dirname(dirname(__DIR__)) . '/Legacy/Autoloader.php');


class Mezzio extends Manager{

    public static function detect($instance, $options = null)
    {
        $installPath = self::installPath($options);
        $lookFor = [
            'config/pipeline.php<20' => 'use Mezzio\Application;'
        ];
        return Auto::scan($installPath, $lookFor);
    }
    
    public static function autoload($instance, $options = null)
    {
        $installPath = self::installPath($options);
        $srcPath = self::srcPath($options);
        $usesExternalComposer = self::option('composerVendor', $options);
        $path = $usesExternalComposer ?: "{$installPath}/vendor";
        if (!file_exists("{$path}/autoload.php")) { #NOTE PHP doesn't throw for require() errors
            throw new \Exception('Autoloading Unavailable', 127, new \Exception("{$path}/autoload.php"));
        }
        require("{$path}/autoload.php");
        if (false) {
            #TODO handle $options autoloading?
        } else {
            if (!array_key_exists('applicationRoot', $options)) {
               $options['applicationRoot'] = '/var/www/application'; //#NOTE this is a copy of $options
            }

            if (!array_key_exists('libraryPath', $options)) {
                $options['libraryPath'] = "{$options['applicationRoot']}/library"; //#NOTE this is a copy of $options
            }

            if (!array_key_exists('applicationPath', $options)) {
               $options['applicationPath'] = "{$installPath}/src/App"; //#NOTE this is a copy of $options
            }
            defined('Saf\APPLICATION_PATH') || define('Saf\APPLICATION_PATH', $options['applicationPath']);
            if (!array_key_exists('controllerPath', $options)) {
                $options['controllerPath'] = "{$installPath}/App/controller"; //#NOTE this is a copy of $options
            }
            $options['psrAutoloading'] = true; //#NOTE Mezzio really doesn't like non-psr autoloaders
            Autoloader::init($options);
            //Autoloader::addAutoloader('App\\', '[[APPLICATION_PATH]]/src');
            $appLoaderGenerator = function($path){
                return function($className) use ($path){
                    return Auto::classPathLookup($className, "{$path}/src/", 'App');
                };
            };
            $moduleLoaderGenerator = function($path){
                return function($className) use ($path){
                    $moduleName = explode('\\', $className)[0];
                    return Auto::classPathLookup($className, "{$path}/module/{$moduleName}/src/", $moduleName);
                };
            };
            Autoloader::addAutoloader(
                'App\\', 
                $appLoaderGenerator($options['applicationPath']), 
                Autoloader::POSITION_BEFORE
            );
            Autoloader::addAutoloader(
                '', 
                $moduleLoaderGenerator($installPath), 
                Autoloader::POSITION_BEFORE
            );
            //print_r([__FILE__,__LINE__,Autoloader::test('App\ConfigProvider')]); die;
        }

    }

    public static function run($instance, $options = null)
    {
        $installPath = self::installPath($options); #TODO this is srcPath?

        /** @var \Psr\Container\ContainerInterface $container */
        $container = require("{$installPath}/config/container.php");

        /** @var \Mezzio\Application $app */
        $app = $container->get(\Mezzio\Application::class);
        $factory = $container->get(\Mezzio\MiddlewareFactory::class);
        
        // Execute programmatic/declarative middleware pipeline and routing
        // configuration statements
        (require("{$installPath}/config/pipeline.php"))($app, $factory, $container);
        (require("{$installPath}/config/routes.php"))($app, $factory, $container);
//#TODO try/catch from gateway.tether
// if (class_exists('Whoops\Run', false)) { #TODO move this into Saf/Framework/Mode/Mezzio::run
//     $previousErrorHandler && $previousErrorHandler($e);
//     return;

        $app->run();

// }
    }

    public static function preboot($instance, $options = [], $prebooted = [])
    {

    }

}