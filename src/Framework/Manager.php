<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for Framework Managers
 */

namespace Saf\Framework;

use Saf\Environment;
use Saf\Auto;

abstract class Manager{

    public const DEFAULT_APPLICATION_ROOT = '/var/www/application';

    abstract public static function detect($instance, $options = null);
    abstract public static function autoload($instance, $options = null);
    abstract public static function run($instance, $options = null);
	abstract public static function preboot($instance, $options, $prebooted = []);

    public static function negotiate($instance, $mode, &$options)
    {
        return $mode;
    }

    protected static function installPath($config)
    {
        if (!is_array($config)) {
            throw new \Exception('invalid config for install path lookup');
        }
        return
            array_key_exists('installPath', $config)
            ? $config['installPath']
            : Environment::DEFAULT_INSTALL_PATH;
    }

    protected static function insertPath(string $new, $after = null)
    {
        Auto::insertPath($new, $after);
    }

    protected static function dumpEnv(string $envConst, array $options, $strategy)
    {
        if (is_string($strategy)) {
            if (strpos($strategy, Environment::INTERPOLATE_START) !== false) {
                $parsed = Environment::parse($strategy, $options);
                if (!is_null($parsed)) {
                    define($envConst, $parsed);
                }
            } elseif (array_key_exists($strategy, $options)) {
                if (!defined($envConst)) {
                    define($envConst, $options[$strategy]);
                }
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