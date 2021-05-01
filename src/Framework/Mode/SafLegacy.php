<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing older SAF instances
 */

namespace Saf\Framework\Mode;

use Saf\Framework\Manager;
use Saf\Legacy\Autoloader;

require_once(dirname(dirname(__FILE__)) . '/Manager.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/Legacy/Autoloader.php');

class SafLegacy extends Manager{

    public static function detect($instance, $options = null)
    {
        return false;
    }
    
    public static function autoload($instance, $options = null)
    {
        if (!array_key_exists('applicationRoot', $options)) {
            throw new \Exception('applicationRoot not defined by the negotiating manager.');
        }
        if (!array_key_exists('libraryPath', $options)) {
            $options['libraryPath'] = "{$options['applicationRoot']}/library";
        }
        if (!array_key_exists('zendPath', $options)) {
            $options['zendPath'] = self::getFrameworkPath($options);
        }
        // if (array_key_exists('legacyMode', $options) && $options['legacyMode'] == 'zend-mvc') {
        //     $path = 
        //         array_key_exists('zendPath', $options)
        //         ? $options['zendPath']
        //         : self::getFrameworkPath($options);
        // }
        self::insertPath($options['zendPath'], '.') || self::insertPath($options['zendPath']);
        // require_once("{$path}/Zend/Loader/Autoloader.php");
		// \Zend_Loader_Autoloader::getInstance()->setFallbackAutoloader(TRUE);
        Autoloader::init($options);
    }

    /**
     * 
     */
    public static function run(string $instance, ?array $options = null)
    {
        if (array_key_exists('legacyMode', $options) && $options['legacyMode'] == 'zend-mvc') {
			$application = new \Zend_Application(\APPLICATION_ENV, \APPLICATION_CONFIG);
            spl_autoload_unregister(array('Zend_Loader_Autoloader','autoload'));
			$application->bootstrap()->run();
        } else {
            $application = Saf\Legacy\Application::load(\APPLICATION_ENV, \APPLICATION_CONFIG, TRUE);
        }
        // $application = \Saf_Application::load(APPLICATION_ID, APPLICATION_ENV, true);
        print_r(['running saf application', $instance, $options]); die;
    }

    public static function preboot($instance, $options = [], $prebooted = [])
    {
        $configPath = array_key_exists('configPath', $options) ? $options['configPath'] : 'configs';
        $required = [
            'APPLICATION_START_TIME' => 'startTime',
            'INSTALL_PATH' => 'installPath',
            'LIBRARY_PATH' => [
                'libraryPath',
                '{$applicationRoot}/library'
            ],
            'APPLICATION_PATH' => 'applicationPath',
            'APPLICATION_CONFIG' => [
                'applicationConfig',
                "{\$applicationPath}/{$configPath}/{\$configFile}",
            ],
        ];
        foreach($required as $requiredConst => $requiredKey) {
            if (!defined($requiredConst)) {
                self::dumpEnv($requiredConst, $options, $requiredKey);
            }
            if (!defined($requiredConst)) {
                throw new \Exception("Required framework constant missing {$requiredConst}");
            }
        }
        $optional = [
            'APPLICATION_ENV' => 'applicationEnv',
        ];
        foreach($optional as $optionalConst => $optionalKey) {
            if (!defined($optionalConst)) {
                self::dumpEnv($optionalConst, $options, $optionalKey, false);
            }            
        }
        // define('\APPLICATION_VERSION', '1.12.2102'); //#TODO #RELEASE
        // define('\APPLICATION_ID', 'ROOMRES'); //#NOTE Set this once per instance and NEVER change it
        // define('\APPLICATION_INSTANCE', '_LIB_NCSU_EDU'); //#NOTE Set this once per instance and NEVER change it
        // define('\APPLICATION_TZ', 'EST5EDT');

    }

    protected static function getFrameworkPath($options){
        $srcPath = 'Zend/library/';
        $path = self::installPath($options) . "/vendor/{$srcPath}";
        if (realpath($path)) {
            return $path;
        }
        if (array_key_exists('vendorRoot', $options)) {
            $vendorPath = "{$options['vendorRoot']}/{$srcPath}";
            if (realpath($vendorPath)) {
                return $vendorPath;
            }
        } elseif (array_key_exists('applicationRoot', $options)) {
            $libraryPath = "{$options['applicationRoot']}/library/";
            if (realpath($libraryPath)) {
                return $libraryPath;
            }
        }
        return $path;
    }

}