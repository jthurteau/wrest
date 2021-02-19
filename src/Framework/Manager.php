<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for framework Kickstart managers
 */

namespace Saf\Framework;

use Saf\Environment;
use Saf\Auto;

abstract class Manager{

    public const DEFAULT_APPLICATION_ROOT = '/var/www/application';

    /**
     * returns true if the manager can support the instance and its options
     * @param string $instance
     * @param array $options reference to options, which may be altered
     * @return bool supported 
     */
    abstract public static function detect(string $instance, array $options);

    /**
     * 
     */
    abstract public static function autoload(string $instance, array $options);

    /**
     * 
     */
    abstract public static function run(string $instance, array $options);

    /**
     * 
     */
	abstract public static function preboot(string $instance, array $options, array $prebooted = []);

    /**
     * allows managers to assess instances and suggest an alternative options or mode to 
     * better handle the setup and execution
     * @param string $instance
     * @param string $mode requested mode
     * @param array $options reference to options, which may be altered
     * @return string suggested mode
     */
    public static function negotiate(string $instance, string $mode, array &$options)
    {
        return $mode;
    }

    /**
     * #TODO #2.0.0 fold this back to Environment?
     */
    protected static function installPath(array $config)
    {
        return
            array_key_exists('installPath', $config)
            ? $config['installPath']
            : Environment::DEFAULT_INSTALL_PATH;
    }

    /**
     * Wrapper for Auto::insertPath
     */
    protected static function insertPath(string $new, $after = null)
    {
        Auto::insertPath($new, $after);
    }

    /**
     * Convenince wrapper for Environment::dump, 
     */
    protected static function dumpEnv(string $envConst, array $options, $strategy)
    {
        if (defined($envConst)) {
            return;
        }
        if (is_string($strategy)) {
            if (strpos($strategy, Environment::INTERPOLATE_START) !== false) {
                $parsed = Environment::parse($strategy, $options);
                if (!is_null($parsed)) {
                    Environment::dump([$envConst => $parsed]);
                }
            } elseif (array_key_exists($strategy, $options)) {
                    Environment::dump([$envConst => $options[$strategy]]);
            }
        } elseif(is_array($strategy)) {
            foreach($strategy as $currentStrategy) {
                self::dumpEnv($envConst, $options, $currentStrategy);
                if (defined($envConst)){
                    break;
                } 
            }
        }
    }
}