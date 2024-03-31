<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for filesystem operations
 */

namespace Saf\Util;

class File
{

    public const DIR_MODE_DIRECT_ONLY = 0; // #NOTE no recursion
    public const DIR_MODE_RECURSIVE_FLAT = 1; // #NOTE recurse, but return as a flat list (full paths)
    public const DIR_MODE_RECURSIZE_NESTED = 2; // #NOTE recurse, and return tree structure (path segments)

    public const FILE_MODE_READ = 'r';
    public const FILE_MODE_EDIT = 'c+';

    public static function dir(string $path, ?int $recursive = self::DIR_MODE_DIRECT_ONLY): array
    {
        $return = [];
        $currentPath = /*self::$_path . '/' .*/ $path;
        if (is_dir($currentPath)) {
            foreach(scandir($currentPath) as $filepath) {
                if (!in_array($filepath, array('.', '..'))) {
                    $currentFullPath = $currentPath . '/' . $filepath;
                    if (
                        is_dir($currentFullPath) 
                        && $recursive != self::DIR_MODE_DIRECT_ONLY
                    ) {
                        $sub = self::dir($path . '/' . $filepath, $recursive);
                        if ($recursive == self::DIR_MODE_RECURSIVE_FLAT) {
                            foreach($sub as $filename) {
                                $return[] = $filename;
                            }
                        } else {
                            $return[$path] = $sub;
                        }
                    } else if (!is_dir($currentFullPath)) {
                        $return[] = $path . '/' . $filepath;
                    }
                }

            }
        }
        return $return;
    }

    public static function calcHashFile(string $file, string $uname): string
    {
        $hash = md5("{$file}{$uname}");
        $prefix = substr($hash, 0, 2); //#TODO configurable prefix configurations? e.g. lengths and number of parts (/ad/ad34g3, /ad3/ad34g3 or /ad/34/ad34g3)
        return "{$prefix}/{$hash}";
    }

    public static function getRawJsonHash(string $file, string $uname):mixed
    {
        $contents = self::getRaw($file);
        $value = null;
        if ($contents) {
            $hash = self::parseJson($contents);
            if (is_array($hash) && key_exists($uname, $hash)) {
                $value = $hash[$uname];
            } else {
//Saf_Debug::out("hash {$file} does not have {$uname}", 'notice');
            }
        }
        return $value;
    }

    public static function getJson(string $file):mixed
    {
        $contents = self::getRaw($file);
        $value = self::parseJson($contents);
        return $value;        
    }

    public static function parseJson(string $contents):mixed
    {
        return json_decode($contents, JSON_OBJECT_AS_ARRAY);
    }

    public static function toJson(mixed $data):?string
    {
        return json_encode($data, JSON_FORCE_OBJECT);
    }

    public static function getRaw($file): ?string
    {
        $contents = null;
        if (file_exists($file)) {
            $pointer = fopen($file, self::FILE_MODE_READ);
            $fileLock = flock($pointer, LOCK_SH);
            if (!$fileLock) {
// \Saf\Debug::out("read blocking {$file}");
                $fileLock = flock($pointer, LOCK_SH | LOCK_NB);
            }
            if ($fileLock) {
                $size = filesize($file);
                $contents = $size ? fread($pointer, $size) : '';
            } else {
//    \Saf\Debug::out("unable to read {$file}");
            }
            flock($pointer, LOCK_UN);
            fclose($pointer);
        }
        return $contents;        
    }

    public static function hold(string $file):mixed //?filepointer
    {
//print_r([__FILE__,__LINE__,$facet, $data, $maxAge]); die;
            $pointer = fopen($file, self::FILE_MODE_EDIT);
            $fileLock = flock($pointer, LOCK_EX);
            if (!$fileLock) {
//\Saf\Debug::out("write blocking {$facet}");
                $fileLock = flock($pointer, LOCK_EX | LOCK_NB);
            }
            $fileLock || self::release($pointer);
            return $fileLock ? $pointer : null;
    }

    public static function release(mixed $pointer):void
    {
        if (is_null($pointer)) {
            return;
        }
        flock($pointer, LOCK_UN);
        fclose($pointer);
    }

    public static function wipe(mixed $pointer):void
    {
        if (!is_null($pointer)) {
            ftruncate($pointer, 0);
            rewind($pointer);
        }
    }

    public static function readHeldFile(mixed $pointer, int $size):?string
    {
        return $size && $pointer ? fread($pointer, $size) : '';
    }
}

