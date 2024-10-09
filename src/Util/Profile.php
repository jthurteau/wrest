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

    protected static $taggedSteps = [];

    public static function ping($data, null|string|array $tag = null): void
    {
        $notice = self::generateNotice();
        $message =  $notice . Debug::introspectData($data);
        Debug::out($message, Debug::LEVEL_PROFILE);
        $tag && self::tag($tag, 'ping');
    }

    public static function in(string|array $tag): void
    {
        $notice = self::generateNotice();
        $tags = is_array($tag) ? $tag : implode(', ', $tag);
        $message =  "{$notice} entry for: {$tags}";
        Debug::out($message, Debug::LEVEL_PROFILE);
        $tag && self::tag($tag, 'in');
    }

    public static function out(string|array $tag): void
    {
        $notice = self::generateNotice();
        $tags = is_array($tag) ? $tag : implode(', ', $tag);
        $message =  "{$notice} exit for: {$tags}";
        Debug::out($message, Debug::LEVEL_PROFILE);
        $tag && self::tag($tag, 'out');
    }

    protected static function tag(string|array $tags, string $type)
    {
        $tags = is_array($tags) ? $tags : [$tags];
        foreach($tags as $currentTag) {
            // #TODO store
        }
    }

    public static function getTags(): array
    {
        return self::$taggedSteps;
    }

    public static function commitTags(): bool
    {
        // #TODO commit to DB.
        return true;
    }

    protected static function generateNotice(): string
    {
        $preset =  self::init();
        $now = microtime(true);
        $gateTime = $preset ? ($now - self::$microStartTime) : null;
        $gateText = is_null($gateTime) ? 'clock not started' : $gateTime;
        return "{$gateText} ({$now}) - ";
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