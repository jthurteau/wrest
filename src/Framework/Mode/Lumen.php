<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing Lumen instances
 */

namespace Saf\Framework\Mode;

use Saf\Framework\Manager;

require_once(dirname(dirname(__FILE__)) . '/Manager.php');

class Lumen extends Manager{

    public static function detect($instance, $options = null)
    {
        $srcPath = self::srcPath($options);
        $lookFor = [
            'bootstrap/app.php' => 'new Laravel\Lumen\Application'
        ];
        return self::scan($srcPath, $lookFor);
    }
    
    public static function autoload($instance, $options = null)
    {
        $vendorPath = self::vendorPath($options);
        require_once("{$vendorPath}/autoload.php");
    }

    public static function run($instance, $options = null)
    {
        $srcPath = self::srcPath($options);
        // if (key_exists('shell', $options)) {
        //     $options = $options['shell']();
        // }
        $app = require "{$srcPath}/bootstrap/app.php";
        $app->run();

    }

    public static function preboot($instance, $options = [], $prebooted = [])
    {

    }

}