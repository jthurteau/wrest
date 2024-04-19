<?php 

/**
 * Utility class for profile debug output
 */

declare(strict_types=1);

namespace Saf\Util;

use Saf\Debug;
use Saf\Utils\Debug\Ui as DebugUi;
use Saf\Utils\Time;

class Profile
{

    protected static $microStartTime = null;
    protected static $timeSource = null;

    public static function ping($data)
    {
        $preset =  self::init();
        $now = microtime(true);
        $gateTime = $preset ? ($now - self::$microStartTime) : null; 
        $notice = (is_null($gateTime) ? 'clock not started' : $gateTime) . " ({$now})"  . ' - ';
        $message =  $notice . Debug::introspectData($data);
        Debug::out($message, Debug::LEVEL_PROFILE);
    }

    protected static function init(): ?float
    {
        if (!is_null(self::$microStartTime)) {
            return self::$microStartTime;
        }
        if (defined('DEBUG_START_TIME')) {
            self::$microStartTime = DEBUG_START_TIME;
            self::$timeSource = 'debug';
            return self::$microStartTime;
        }
        if (defined('Saf\APPLICATION_START_TIME')) {
            self::$microStartTime = Saf\APPLICATION_START_TIME;
            self::$timeSource = 'saf_app';
            return self::$microStartTime;
        }
        self::$microStartTime = microtime(true);
        self::$timeSource = 'init';
        return null;
    }
}