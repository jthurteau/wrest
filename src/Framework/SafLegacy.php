<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing older SAF instances
 */

namespace Saf\Framework;

use Saf\Framework\Manager;

require_once(dirname(dirname(__FILE__)) . '/Framework/Manager.php');

class SafLegacy extends Manager{

    public static function detect($instance, $options = null)
    {
        return false;
    }
    
    public static function autoload($instance, $options = null)
    {
        if (array_key_exists('legacyMode', $options) && $options['legacyMode'] == 'zend-mvc') {
            $path = 
                array_key_exists('zendPath', $options)
                ? $options['zendPath']
                : self::getFrameworkPath($options);
        }
        self::insertPath($path,'.') || self::insertPath($path);
        require_once("{$path}/Zend/Loader/Autoloader.php");
		\Zend_Loader_Autoloader::getInstance()->setFallbackAutoloader(TRUE);
    }

    public static function run($instance, $options = null)
    {
        if (array_key_exists('legacyMode', $options) && $options['legacyMode'] == 'zend-mvc') {
			$application = new \Zend_Application(\APPLICATION_ENV, \APPLICATION_CONFIG);
			$application->bootstrap()->run();
        } else {
            $application = Saf\Legacy\Application::load(\APPLICATION_ENV, \APPLICATION_CONFIG, TRUE);
        }
        // $application = \Saf_Application::load(APPLICATION_ID, APPLICATION_ENV, true);
        print_r(['running saf application', $instance, $options]); die;
    }

    public static function prep($instance, $options = null)
    {

    }

    public static function preboot($instance, $options = [], $prebooted = [])
    {
        // define('\APPLICATION_START_TIME', microtime(TRUE));
        // define('\APPLICATION_VERSION', '1.12.2102'); //#TODO #RELEASE
        // define('\APPLICATION_ID', 'ROOMRES'); //#NOTE Set this once per instance and NEVER change it
        // define('\APPLICATION_INSTANCE', '_LIB_NCSU_EDU'); //#NOTE Set this once per instance and NEVER change it
        // define('\APPLICATION_TZ', 'EST5EDT');
        // if(file_exists('localize.php')){
        //     require_once('localize.php');
        // }
        // defined('\INSTALL_PATH') || define('\INSTALL_PATH', realpath(dirname(__FILE__) . '/..'));
        // defined('\LIBRARY_PATH') || define('\LIBRARY_PATH', realpath(\INSTALL_PATH . '/../library'));
        // \APPLICATION_ENV 
        // \APPLICATION_CONFIG
    }

    protected static function getFrameworkPath($options){
        $srcPath = 'Zend/library/Zend';
        $path = self::installPath($options) . "/vendor/{$srcPath}";
        if (array_key_exists('vendorRoot', $options)) {
            $path = "{$options['vendorRoot']}/{$srcPath}";
        } elseif (array_key_exists('applicationRoot', $options)) {
            $path = "{$options['applicationRoot']}/{$srcPath}";
        }
        return $path;
    }

}