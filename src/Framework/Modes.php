<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Traits for for Framework Modes
 */

namespace Saf\Framework;

use Saf\Auto;
use Saf\Cache;

require_once(dirname(dirname(__FILE__)) . '/Auto.php');
require_once(dirname(dirname(__FILE__)) . '/Cache.php');

trait Modes {

    #TODO move these into a new class
	// public const MODE_ZFMVC = 'zendmvc'; //NOTE deprecated in 2.0
	// public const MODE_ZFNONE = 'zendbare'; //NOTE deprecated in 2.0
	// public const MODE_SAF = 'saf';
	// public const MODE_SAF_LEGACY = 'saf-legacy';
	// public const MODE_MEZ = 'mezzio';
	// public const MODE_LAMMVC = 'laminas-mvc'; //#TODO #2.1.0 support Laravel
	// public const MODE_LF5 = 'laravel5'; //#TODO #2.1.0 support Laravel
	// public const MODE_SLIM = 'slim'; //#TODO #2.1.0 support Slim

    public static function scanFrameworkModes($useCache = null) 
    {
        $cacheTag = __CLASS__;
        if ($useCache && Cache::available("::{$cacheTag}", $useCache)){
            return Cache::get("::{$cacheTag}", $useCache);
        }
        $modes = [];
        $modePath = dirname(__FILE__) . '/Mode';
        foreach(scandir($modePath) as $filePath) {
            if (
                !in_array($filePath, array('.', '..'))
                && strrpos($filePath, '.php') == (strlen($filePath) - 4)
            ) {
                $className = basename($filePath, '.php');
                $modes[$className] = str_replace('_', '-', strtolower(Auto::optionToEnv($className)));
            }

        }
        Cache::store("::{$cacheTag}", $modes);
        return $modes;
    }

    public static function detectableFrameworkModes($useCache = null)
    {
        $cacheTag = __CLASS__ . '::detectable';
        if ($useCache && Cache::available("::{$cacheTag}", $useCache)){
            return Cache::get("::{$cacheTag}", $useCache);
        }
        $modes = self::scanFrameworkModes($useCache);
        $detectable = [];
        $modePath = dirname(__FILE__) . '/Mode';
        foreach($modes as $modeFile => $mode) {
            $detectable[] = $mode; #TODO reflect on the static autodetectable method
        }
        Cache::store("::{$cacheTag}", $detectable);
        return $detectable;
    }

}