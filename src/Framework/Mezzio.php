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
        require("{$installPath}/vendor/autoload.php");
    }

    public static function run($instance, $options = null)
    {
        $installPath = self::installPath($options);

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