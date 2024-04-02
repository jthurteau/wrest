<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base Driver for Disk Based Caching
 */

namespace Saf\Cache;

use Saf\Cache;
use Saf\Utils\Time;
use Saf\Util\File;
use Saf\Cache\Strategy;

class Disk implements Strategy{

    public const DEFAULT_MAX_AGE = 60;

    public const STAMP_MODE_REPLACE = 0;
    public const STAMP_MODE_AVG = 1;
    public const STAMP_MODE_KEEP = 2;

    public const DEFAULT_LOAD_SPEC = [
        Cache::CONFIG_DEFAULT => null, 
        Cache::CONFIG_MAX_AGE => self::DEFAULT_MAX_AGE
    ];
    public const DEFAULT_SAVE_SPEC = [
        Cache::CONFIG_STAMP_MODE => self::STAMP_MODE_REPLACE
    ];

    protected static string $defaultPath = '/var/www/storage/cache/tmp';
    protected static ?string $currentPath = null;
    protected static array $facetPaths = [];

    //#TODO protected static array $facetMaps = []; //avoid collisions

    public static function init(null|string|array $pathOrConfig):void
    {
        if (is_string($pathOrConfig)) {
            self::$defaultPath = $pathOrConfig;
        } else {
            if (key_exists('defaultPath', $pathOrConfig)) {
                self::$defaultPath = $pathOrConfig['defaultPath'];
            }
            if (
                key_exists('facetPaths', $pathOrConfig) 
                && is_array($pathOrConfig['facetPaths'])
            ) {
                foreach($pathOrConfig['facetPaths'] as $facet => $path) {
                    self::$facetPaths[$facet] = (string)$path;
                }
            }
        }
        self::$currentPath = self::$defaultPath;
    }

    public static function available(string $facet): bool
    {
        return self::fileAvailable(self::getFullPath($facet));
    }

    public static function load(string $facet, mixed $spec = self::DEFAULT_LOAD_SPEC): mixed
    {
        $facet = self::fileSafeFacet($facet);
        $default = 
            is_array($spec) && key_exists(Cache::CONFIG_DEFAULT, $spec) 
            ? $spec[Cache::CONFIG_DEFAULT]
            : null;
        $maxAge = 
            is_array($spec) && key_exists(Cache::CONFIG_MAX_AGE, $spec) 
            ? $spec[Cache::CONFIG_MAX_AGE]
            : self::DEFAULT_MAX_AGE;
        $fuzzy = 
            is_array($spec) && key_exists(Cache::CONFIG_AGE_FUZZY, $spec) 
            ? $spec[Cache::CONFIG_AGE_FUZZY]
            : false;
        // if fuzzy modify maxAge
        //\Saf\Util\Profile::ping(["loading disk cache {$facet} with timeout {$maxAge}"]);
        //if hash facet '/hash/perm' . Saf\Util\File::calcHashFile

        $payload = null;

        $minDate = !is_null($maxAge) ? Time::time() - $maxAge : null;
        //if fuzzy $maxDate = Cache::fuzz($maxAge)...

        $path = self::getFullPath($facet);
        self::ensurePath(dirname($path));
        if(!self::fileAvailable($path)) {
            //\Saf\Util\Profile::ping(["disk cache file {$path} for {$facet} does not exist"]);
            return $default;
        }
        $contents = File::getJson($path);
        if (
            $contents && is_array($contents) && key_exists('payload', $contents)
        ) {
            $payload = self::valid($contents, $minDate);
            //$payload || \Saf\Util\Profile::ping(["disk cache {$facet} expired timeout {$minDate}"]);
        }
        return $payload ?: $default;
    }

    /**
     * temporarily set the path elsewhere
     */
    public static function target(string $path)
    {
        self::$currentPath = $path;
    }

    /**
     * restore the path
     */
    public static function relenquish()
    {
        self::$currentPath = self::$defaultPath;
    }

    protected static function getPath(string $facet): ?string
    {
        return //#TODO support stemming? (x*)
            key_exists($facet, self::$facetPaths)
            ? self::$facetPaths[$facet]
            : self::$currentPath;
    }

    public static function getFullPath(?string $facet): ?string
    {
        return 
            !is_null($facet)
            ? (self::getPath($facet) . "/{$facet}.json")
            : null;
    }

    protected static function fileAvailable(string $file)
    {
        //\Saf\Util\Profile::ping(["cache file {$file} " . (is_readable($file) ? 'exists' : 'does not exist')]);
        return !is_null($file) && file_exists($file) && is_readable($file);
    }

    protected static function valid(mixed $payload, ?int $minDate = null):mixed
    {
        $valid = null;
        //\Saf\Util\Profile::ping(array('Cache', $minDate, array_key_exists('stamp', $contents) ? $contents['stamp'] : 'NONE' ));
        if (
            is_null($minDate)
            || (key_exists('stamp', $payload) && $payload['stamp'] >= $minDate)
        ) {
            //\Saf\Util\Profile::ping(["disk cache accepted with age timeout {$payload['stamp']} ({$minDate})"]);
            $valid = $payload['payload'];
            //$stamp = key_exists('stamp', $payload) ? $payload['stamp'] : null;
            // \Saf\Util\Profile::ping("loaded cached {$file} {$stamp}" . ($cache ? ', caching to memory' : ''));
        } //else {
            // $cacheDate = 
            //     array_key_exists('stamp', $contents)
            //     ? $contents['stamp']
            //     : null;
            // $now = time();
            // \Saf\Util\Profile::ping(array('expired cache', $file, 
            //     'now' . date(Ems::EMS_DATE_TIME_FORMAT ,$now), 
            //     'accept' . date(Ems::EMS_DATE_TIME_FORMAT ,$minDate), 
            //     'cached' . date(Ems::EMS_DATE_TIME_FORMAT ,$cacheDate)
            // ));
        // }
        return $valid;
    }


    public static function save(string $facet, mixed $data, mixed $spec = self::DEFAULT_SAVE_SPEC): bool
    {
        $timestampMode = 
            is_array($spec) && key_exists(Cache::CONFIG_STAMP_MODE, $spec) 
            ? $spec[Cache::CONFIG_STAMP_MODE]
            : self::STAMP_MODE_REPLACE;
        //\Saf\Util\Profile::ping(["disk cache saving with timestamp mode {$timestampMode}"]);
        //if hash facet

        if (is_null($data)) {
            //\Saf\Debug::out("saving null value to cache, {$facet}");
            return false;
        }
        $facet = self::fileSafeFacet($facet);
        $path = self::getFullPath($facet);
        $hold = File::hold($path);
        if ($hold) {
            $oldTime = 0;
            if ($timestampMode) {
                $size = filesize($path);
                $oldTime = self::getHashTimestamp(File::readHeldFile($hold, $size));
            }
            File::wipe($hold);
            $newTime = self::calcNewTimestamp($oldTime, $timestampMode);
            //\Saf\Util\Profile::ping(["disk cache {$facet} timestamp old {$timestampMode}, new {$newTime}, time " . Time::time()]);
            $newContents = ['stamp' => $newTime, 'payload' => $data];
            $newEncodedContents = File::toJson($newContents);
            File::commit($hold, $newEncodedContents);
            File::release($hold);
            return true;
        } else {
            // \Saf\Debug::out("unable to save {$facet}");
        }
        return false;
    }

    protected static function getHashTimestamp(?string $fileContents): ?int
    {
        $json = File::parseJson($fileContents);
        return
            $json && is_array($json) && key_exists('stamp', $json)
            ? $json['stamp']
            : null;
    }

    protected static function calcNewTimestamp(?int $time, int $mode): int
    {
        if (is_null($time)) {
            $time = 0;
        }
        return 
            $mode === self::STAMP_MODE_KEEP
            ? $time
            : (
                $mode === self::STAMP_MODE_AVG && $time > 0
                ? floor(floatval(Time::time() + $time) / 2)
                : Time::time()
            );
    }

    public static function fileSafeFacet(string $facet)
    {
        return str_replace(['\\','::'], '_', $facet);
    }

    protected static function ensurePath(string $path)
    {
        if (true && !file_exists($path)) {
            mkdir($path, 0744, true) || throw new \Exception("unable to ensure path for {$path}");
        }
    }

    public static function getHashed(string $facet, string $uname, int $minDate = null, bool $memorize = false)
    {
        $memory = Cache::getHashed($facet, $uname);
        if ($memory) {
            return $memory;
        }
        $payload = null;
        $contents = File::getRawJsonHash($file, $uname);
        //Saf_Debug::outData(array('from file', $file, $uname, $contents));
        if ($contents && is_array($contents) && key_exists('payload', $contents)) {
            if (
                is_null($minDate)
                || (
                    key_exists('stamp', $contents) && $contents['stamp'] >= $minDate
                )
            ) {
                $payload = $contents['payload'];
                $stamp = key_exists('stamp', $contents) ? $contents['stamp'] : null;
                //Saf_Debug::out("loaded cached hash {$file} {$uname} {$stamp}" . ($memorize ? ', caching to memory' : ''));
                $memorize && Cache::setHashed($facet, $uname, $payload);
            } else {
                $cacheDate =
                    key_exists('stamp', $contents)
                    ? $contents['stamp']
                    : null;
                $now = Time::time();
                // Saf_Debug::outData(array('expired cache', $file, 
                //     'now    ' . date(Ems::EMS_DATE_TIME_FORMAT ,$now), 
                //     'accept ' . date(Ems::EMS_DATE_TIME_FORMAT ,$minDate), 
                //     'cached ' . date(Ems::EMS_DATE_TIME_FORMAT ,$cacheDate)
                // ));
            }
        }
        return $payload;
    }

    //#TODO implement forget()
    public static function forget(string $facet): void
    {
        self::fileAvailable(self::getFullPath($facet));
    }

    public static function canStore(mixed $data): bool
    {
        return !is_callable($data);
    }

    public static function parseSpec(mixed $spec): mixed
    {
        return $spec;
    }

    public static function getDefaultLoadSpec(): mixed
    {
        return self::DEFAULT_LOAD_SPEC;
    }

    public static function getDefaultSaveSpec(): mixed
    {
        return self::DEFAULT_SAVE_SPEC;
    }

}