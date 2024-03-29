<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base Driver for Disk Based Caching
 */

namespace Saf\Cache;

use Saf\Utils\Time;

class Disk {

    public const DEFAULT_MAX_AGE = 60;
    public const AGE_FUZZY = 'fuzzy';

    public const STAMP_MODE_REPLACE = 0;
	public const STAMP_MODE_AVG = 1;
	public const STAMP_MODE_KEEP = 2;

    protected static ?string $defaultPath = '/var/www/storage/cache/tmp';

    protected static array $facetPaths = [];

    //#TODO protected static array $facetMaps = []; //avoid collisions

    public static function init($pathOrConfig) //#TODO PHP8 string|array
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
    }

    public static function fuzzyLoad(?string $facet, $fuzzyAge, $default = null)
    {
        
       // print_r([__FILE__,__LINE__, $facet, $maxAge, $default]); die;
        return $default;
    }

    protected static function getPath(string $facet) : string
    {
        return //#TODO support stemming? (x*)
            key_exists($facet, self::$facetPaths)
            ? self::$facetPaths[$facet]
            : self::$defaultPath;
    }

    public static function getFullPath(?string $facet) : string
    {
        return 
            !is_null($facet)
            ? (self::getPath($facet) . "/{$facet}.json")
            : null;
    }

    public static function available(string $facet) : bool
    {
        return self::fileAvailable(self::getFullPath($facet));
    }

    protected static function fileAvailable(string $file)
    {
        return !is_null($file) && file_exists($file) && is_readable($file);
    }

    public static function load(?string $facet, null|int|string $maxAge = self::DEFAULT_MAX_AGE, $default = null)
    {
        $facet = self::fileSafeFacet($facet);
        $payload = null;
        if ($maxAge == self::AGE_FUZZY) {
            $fuzzyAge = 'foo';
            return self::fuzzyLoad($facet, $fuzzyAge, $default);
        }
        $maxDate = !is_null($maxAge) ? Time::time() + $maxAge : null;

        //if (true){
        $path = self::getFullPath($facet);
        self::ensurePath(dirname($path));
        if(!self::fileAvailable($path)) {
            return $default;
        }
        $contents = self::getJson($path);
        //}
        if ( //#TODO consolidate this block with getHash
            $contents
            && is_array($contents)
            && key_exists('payload', $contents)
        ) {
//Saf_Debug::outData(array('Cache', $minDate, array_key_exists('stamp', $contents) ? $contents['stamp'] : 'NONE' ), 'PROFILE');
            if (
                is_null($maxDate)
                || (
                    key_exists('stamp', $contents)
                    && $contents['stamp'] <= $maxDate
                )
            ) {
                $payload = $contents['payload'];
                $stamp = key_exists('stamp', $contents) ? $contents['stamp'] : null;
//    Saf_Debug::out("loaded cached {$file} {$stamp}" . ($cache ? ', caching to memory' : ''));
            } //else {
                // $cacheDate = 
                //     array_key_exists('stamp', $contents)
                //     ? $contents['stamp']
                //     : null;
                // $now = time();
// Saf_Debug::outData(array('expired cache', $file, 
//     'now' . date(Ems::EMS_DATE_TIME_FORMAT ,$now), 
//     'accept' . date(Ems::EMS_DATE_TIME_FORMAT ,$minDate), 
//     'cached' . date(Ems::EMS_DATE_TIME_FORMAT ,$cacheDate)
// ));
//            }
        }
        return $payload ? $payload : $default;
    }

    public static function save(?string $facet, mixed $data, int $mode = self::STAMP_MODE_REPLACE) : bool
    {
        if (is_null($data)) {
            //\Saf\Debug::out("saving null value to cache, {$facet}");
            return false;
        }
        $facet = self::fileSafeFacet($facet);
        $path = self::getFullPath($facet);
        //print_r([__FILE__,__LINE__,$facet, $data, $maxAge]); die;
		$pointer = fopen($path, 'c+');
		$fileLock = flock($pointer, LOCK_EX);
		if (!$fileLock) {
            //\Saf\Debug::out("write blocking {$facet}");
			$fileLock = flock($pointer, LOCK_EX | LOCK_NB);
		}
		if ($fileLock) {
			$oldTime = 0;
			if ($mode) {
				$size = filesize($path);
                //getJson
				$contents = $size ? fread($pointer, $size) : '';
				$oldValue = json_decode($contents, JSON_OBJECT_AS_ARRAY);
				if ($oldValue && is_array($oldValue) && key_exists('stamp', $oldValue)) {
					$oldTime = $oldValue['stamp'];
				}
			}
			ftruncate($pointer, 0);
			rewind($pointer);
			$time = 
				$mode === self::STAMP_MODE_KEEP
				? $oldTime
				: (
					$mode === self::STAMP_MODE_AVG && $oldTime > 0
					? floor(floatval(Time::time() + $oldTime) / 2)
					: Time::time()
				);
            $newContents = ['stamp' => $time, 'payload' => $data];
            $newEncodedContents = json_encode($newContents, JSON_FORCE_OBJECT);
			fwrite($pointer, $newEncodedContents);
            //	\Saf\Debug::out("cached {$facet}");
		} //else {
            // \Saf\Debug::out("unable to save {$facet}");
		//}
		flock($pointer, LOCK_UN);
		fclose($pointer);
        return true;
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

    public static function getJson($file): mixed
    {
        $contents = self::getRaw($file);
        $value = json_decode($contents, JSON_OBJECT_AS_ARRAY);
        return $value;        
    }

    public static function getRaw($file): ?string
    {
        $value = null;
        if (file_exists($file)) {
            $pointer = fopen($file, 'r');
            $fileLock = flock($pointer, LOCK_SH);
            if (!$fileLock) {
                // \Saf\Debug::out("read blocking {$file}");
                $fileLock = flock($pointer, LOCK_SH | LOCK_NB);
            }
            if ($fileLock) {
                $size = filesize($file);
                $contents = $size ? fread($pointer, $size) : '';
            } //else {
            //    \Saf\Debug::out("unable to read {$file}");
            //}
            flock($pointer, LOCK_UN);
            fclose($pointer);
        }
        return $contents;        
    }

}