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

abstract class Manager{

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

    protected static function env(string $string)
    {
        return Environment::get($string);
    }
}