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

require_once(dirname(__DIR__) . '/Manager.php');

class ZendMvc extends Manager{

    protected static $applicationDir = 'application';
    protected static $applicationMain = 'Bootstrap';
    protected static $applicationBaseClass = 'Zend_Application_Bootstrap_Bootstrap';

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

    public static function run($agentId, $options = [])
    {
        // $application = \Saf_Application::load(APPLICATION_ID, APPLICATION_ENV, true);
        print(\Saf\Debug::stringR(('running saf application', $instance, $options)); //die;
    }

    public static function negotiate($instance, $mode, &$options)
    {
        $installPath = self::installPath($options);
        $applicationDir = self::$applicationDir;
        $configType = array_key_exists('configType', $options) ? $configType[$options] : 'xml';
        $options['applicationPath'] = "{$installPath}/{$applicationDir}";
        $options['controllerPath'] = "{$installPath}/{$applicationDir}/controllers";
        if (!array_key_exists('applicationRoot', $options)) {
            $scanApplicationRoot = realpath("{$installPath}/../library");
            $options['applicationRoot'] = $scanApplicationRoot ?: Manager::DEFAULT_APPLICATION_ROOT;
        }
        $options['legacyMode'] = 'zend-mvc';
        if (!array_key_exists('configFile', $options)) {
            $options['configFile'] = "zend_application.{$configType}";
        }
        return 'saf-legacy';
    }

}


// switch($mode) {
// 	case self::MODE_ZFMVC:
// 		$configFile = 'zend_application';
// 		break;
// 	case self::MODE_SAF:
// 		if (defined('APPLICATION_ID') && \APPLICATION_ID){
// 			$applicationFilePart = strtolower(\APPLICATION_ID); //#TODO #1.0.0 filter file names safely
// 			$configFile = "saf_application.{$applicationFilePart}";
// 			if (file_exists(\APPLICATION_PATH . "/configs/{$configFile}.xml")) {
// 				break;
// 			}
// 		}
// 		$configFile = 'saf_application';
// 		break;
// 	default:
// 		$configFile = 'application';
// 		break;
// }

// run() {
// $application = new \Zend_Application(APPLICATION_ENV, APPLICATION_CONFIG);
// 					$application->bootstrap()->run();
