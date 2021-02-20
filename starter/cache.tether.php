<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * cache script, accepts an optional canister to load/set data
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

return function(
    array &$canister = []
){
    if (!key_exists('installPath', $canister)) {
        throw new Exception("Application configuration missing pre-requisite for cache.");
    }
    $fileSafe = 
        key_exists('fileSafeFilter', $canister) 
            && is_callable($canister['fileSafeFilter'])
        ? $canister['fileSafeFilter']
        : function($string)
        {
            return strtolower(
                preg_replace('/[^A-Za-z0-9-]/', '', str_replace(['_', ' '],'-', $string))
            );
        };
    $canister['cacheLockout'] = [];
    $applicationHandle = 
        key_exists('applicationHandle', $canister) 
        ? $canister['applicationHandle']
        : (
            key_exists('applicationId', $canister)
            ? $fileSafe(key_exists('applicationId', $canister))
            : 'local-instance'
        );
    $storageRoot = 
        key_exists('storageRoot', $canister) 
        ? $canister['storageRoot']
        : '/var/www/storage';
    $storagePath =
        key_exists('storagePath', $canister) 
        ? $canister['storagePath'] 
        : "{$storageRoot}/{$applicationHandle}";
    $cacheFile = 
        key_exists('cacheFile', $canister) 
        ? $canister['cacheFile'] 
        : "{$applicationHandle}.cache.root.json";
    $cacheMaxAge = 
        key_exists('cacheMaxAge', $canister) 
        ? $canister['cacheMaxAge'] 
        : null;
    #TODO #2.0.0 $cacheFormat = 'json'
    $cacheOptions = 
        key_exists('cacheOptions', $canister) 
        ? $canister['cacheOptions'] 
        : JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY;
    $cacheDepth = 
        key_exists('cacheDepth', $canister) 
        ? $canister['cachcacheDeptheOptions'] 
        : 512;
    $cacheWriteOptions = 
        key_exists('cacheWriteOptions', $canister) 
        ? $canister['cacheWriteOptions'] 
        : LOCK_EX;
    $canisterFifo = 
        key_exists('canisterFifo', $canister) 
        ? $canister['canisterFifo']
        : true;
    $roots = key_exists('roots', $canister) 
    ? $canister['roots'] #TODO #2.0.0 do we need to support callable option
    : [
        '?composer',
        "?{$applicationHandle}",
        '?local-instance',
    ]; 
    $cachePath = "{$storagePath}/{$cacheFile}";
    if(file_exists($cachePath) && is_readable($cachePath)) {
        $read = true;
        try{
            $cache = json_decode(file_get_contents($cachePath), null, $cacheDepth, $cacheOptions);
        } catch (Exception $e) {
            $code = $e->getCode();
            $canister['cacheLockout']['r'] = "decode:{$code}";
        }
        if (
            $cache 
            && is_array($cache)
            && (
                is_null($cacheMaxAge)
                || (
                    key_exists('cacheMicrostamp', $cache)
                    && microtime(true) - $cacheMaxAge < $cache['cacheMicrostamp']
                )
            )
        ) {
            foreach($cache as $key => $value) {
                key_exists($key, $canister) || ($canister[$key] = $value);
            }
            return;
        }
        $canister['cacheLockout']['r'] = 'age';
    } 
    
    $cache = null;
    $parsedFiles = [];
    foreach($roots as $index => $root) {
        $optional = is_string($root) && strpos($root, '?') === 0;
        $rootSource = $optional ? substr($root, 1) : $root;
        if (is_string($rootSource)) {
            $rootFile = "{$canister['installPath']}/{$rootSource}.root.php";
            if (key_exists($rootFile, $parsedFiles)) {
                continue;
            }
            if (file_exists($rootFile)){
                if (!is_readable($rootFile)) {
                    throw new Exception("Application configuration source ({$rootSource}) inaccessible.");
                }
                $root = require($rootFile);
                $parsedFiles[$rootFile] = $root;
            } elseif (!$optional) {
                throw new Exception("Application configuration source ({$rootSource}) missing.");
            } else {
                $parsedFiles[$rootFile] = $root;
                $root = null;
            }
        }
        #TODO #2.0.0 support callables?
        if(is_array($root)) {
            foreach($root as $key => $value) {
                key_exists($key, $canister) || ($canister[$key] = $value);
            }
        } else if(!is_null($root)) {
            //print_r(['check loop',$root, $rootSource, $rootFile]);
            $rootId = is_string($rootSource) ? $rootSource : $index;
            throw new Exception("Application configuration source ({$rootId}) invalid.");
        }
    }
    if (!key_exists('cachedRoots',$canister)) {
        $canister['cachedRoots'] = array_keys($parsedFiles);
    } else {
        foreach(array_keys($parsedFiles) as $newRoot) {
            in_array($newRoot, $canister['cachedRoots']) 
                || ($canister['cachedRoots'][] = $newRoot);
        }
    }
    
    if (!is_array($cache) && is_writable(dirname($cachePath))) {
        if (file_exists($cachePath) && !is_writable($cachePath)) {
            $canister['cacheLockout']['w'] = ['file'];
            return;
        }
        $canister['cacheMicrostamp'] = microtime(true);
        try{
            $cacheString = json_encode($canister, $cacheOptions, $cacheDepth);
            $canister['cacheLockout']['w'] = 
                file_put_contents($cachePath,$cacheString, $cacheWriteOptions)
                ? false
                : true;
        } catch (Exception $e) {
            $code = $e->getCode();
            $canister['cacheLockout']['w'] = "encode:{$code}";
        }
    } else {
        $canister['cacheLockout']['w'] = 'dir';
    }
    return;
};