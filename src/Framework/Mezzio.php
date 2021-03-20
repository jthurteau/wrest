<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing Mezzio instances
 */

declare(strict_types=1);

namespace Saf\Framework;

use Saf\Framework\Manager;
use Saf\Legacy\Autoloader;
use Saf\Auto;

require_once(dirname(dirname(__FILE__)) . '/Framework/Manager.php');

class Mezzio extends Manager{

    protected const DEFAULT_SCAN_LINES = 50;

    public static function detect($instance, $options = null)
    {
        $installPath = self::installPath($options);
        $lookFor = [
            'config/pipeline.php<20' => 'use Mezzio\Application;'
        ];
        foreach($lookFor as $file => $line) {
            $maxLinesIndex = strpos($file, '<'); #TODO #2.0.0 right now this it just implemented as an estimate (40*lines chars)
            $fileName = substr($file, 0, $maxLinesIndex === false ? null : $maxLinesIndex);
            $filePath = "{$installPath}/{$fileName}";
            if (file_exists($filePath) && is_readable($filePath)) {
                $maxLines = 
                    $maxLinesIndex 
                    ? substr($file, $maxLinesIndex + 1) 
                    : self::DEFAULT_SCAN_LINES;
                $fileScan = file_get_contents($filePath, false, null, 0, $maxLinesIndex * 40);
                if (strpos($fileScan, $line) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
    
    public static function autoload($instance, $options = null)
    {
        $installPath = self::installPath($options);
        $usesExternalComposer = self::option('composerVendor', $options);
        $path = $usesExternalComposer ?: "{$installPath}/vendor";
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
        $installPath = self::installPath($options);
        if (key_exists('shell', $options)) {
            $options = $options['shell']();
        }
        /** @var \Psr\Container\ContainerInterface $container */
        $container = require("{$installPath}/config/container.php");

        /** @var \Mezzio\Application $app */
        $app = $container->get(\Mezzio\Application::class);
        $factory = $container->get(\Mezzio\MiddlewareFactory::class);

        // Execute programmatic/declarative middleware pipeline and routing
        // configuration statements
        (require("{$installPath}/config/pipeline.php"))($app, $factory, $container);
        (require("{$installPath}/config/routes.php"))($app, $factory, $container);

        $app->run();
    }

    public static function preboot($instance, $options = [], $prebooted = [])
    {

    }

}