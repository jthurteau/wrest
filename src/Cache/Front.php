<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Parent class for a cachable resources
 */

namespace Saf\Cache;

use Saf\Cache;
use Saf\Cache\Disk;
use Saf\Cache\Memory;
// use Saf\Utils\Time;
use Saf\Hash;
use Saf\Util\Profile;
use Saf\Utils\UrlRewrite;

abstract class Front implements Cachable {

    public const DISK_CACHE_CLASS = Disk::class;
    public const MEMORY_CACHE_CLASS = Memory::class;
    //public const DB_CACHE_CLASS = Db::class;

    public const STRATEGY_NEVER = 0;
    public const STRATEGY_SINGLETON = 2;
    public const STRATEGY_INDEX = 3;
    public const STRATEGY_MULTI = 4;
    public const STRATEGY = 'strategy';
    public const INDEX = 'index';
    public const HANDLER = 'handler';
    public const DURATION = 'duration';
    public const STATIC = 'static';
    public const INDEX_LABEL = 'label';
    public const CONFIG_DEFAULT = '*';

    public const SETTINGS_DEFAULT = 'DEFAULT';
    public const SETTINGS_STABLE_SINGLETON = 'STABLE_SINGLETON';
    public const SETTINGS_NEVER = 'NEVER';

    protected $proxy = null;
    protected ?Front $cache = null;

    protected null|string|bool $lastCached = null; //#TODO configurable reporting level for this
    protected null|string|array $diskSpec = null;

    abstract public function getProxy(): ?object;

    public function __construct(Cachable $proxy) {
        $this->proxy = $proxy;
        $this->cache = $this;
        $this->diskSpec = $this->proxy->getCacheSpec(Disk::class);
    }

    public function __call($name, $arguments)
    {
        $this->lastCached = false;
        //#TODO figure out a uid strategy for proxy (e.g. if two objects of the same class need to use the same cache facet)
        if (method_exists($this->proxy, $name)){
            $profileTime = microtime(true);
            $forceLoadConfig = [
                Cache::CONFIG_DEFAULT => null,
                Cache::CONFIG_MAX_AGE => Disk::MAX_AGE_FORCE, //#TODO pass -1? and make a const?
            ];
            $memoryIndex = $this->proxy->getCacheIndex(self::MEMORY_CACHE_CLASS, $name, $arguments);
            $memory = $memoryIndex ? Memory::load(UrlRewrite::deQuery($memoryIndex)) : null; //#NOTE force deQuery from memory for now
            if (!is_null($memory)) {
                $this->lastCached = self::MEMORY_CACHE_CLASS . "::{$name}";
                \Saf\Util\Profile::ping(['cached call from memory', self::class, $name, $arguments]);
                return is_callable($memory) ? $memory(...$arguments) : $memory;
            }
            //#TODO make indexes an object
            $diskIndex = $this->proxy->getCacheIndex(self::DISK_CACHE_CLASS, $name, $arguments);
            $diskFacet = $diskIndex ? UrlRewrite::deQuery($diskIndex) : null;
            $diskQuery = $this->proxy->getCacheQuery(self::DISK_CACHE_CLASS, $name, $arguments); //UrlRewrite::getQuery($diskIndex ?: '');
            //\Saf\Util\Profile::ping(['verifying cache indexes', self::class, $name, $memoryIndex, $diskIndex, $arguments]);
            //$diskAge = null; // $this->proxy->getCacheExpiration($diskIndex); // pack expiration into index?
            if($diskQuery) {
                $diskSpec = Hash::fromQuery($diskQuery);
            } else {
                $defaultDiskSpec =
                    is_string($this->diskSpec)
                    ? ($this->diskSpec ? [ Cache::CONFIG_PATH=> $this->diskSpec] : [])
                    : $this->diskSpec;
                $diskSpec = $defaultDiskSpec ?: [];
            }
            \Saf\Util\Profile::ping(['cache status check', self::class, $name, $arguments, $this->cacheOnlyMode()]);
            $this->cacheOnlyMode() && (\Saf\Util\Profile::ping(['cache only', $name, self::class]));
            $diskLoadConfig = $this->cacheOnlyMode() ? $forceLoadConfig : $diskSpec; #TODO AGE, FUZZY
            $disk = $diskFacet ? Disk::load($diskFacet, $diskLoadConfig + Disk::DEFAULT_LOAD_SPEC) : null;
            if (!is_null($disk)) {
                $this->lastCached = self::DISK_CACHE_CLASS . "::{$name}";
                $memoryIndex ? Memory::save($memoryIndex, $disk) : $this->sideLoad($disk, $name, $arguments);
                \Saf\Util\Profile::ping(['cached call from disk', self::class, $name, $arguments]);
                return is_callable($disk) ? $disk(...$arguments) : $disk;
            } elseif ($this->cacheOnlyMode()) {
                \Saf\Util\Profile::ping(['forced to make uncached call because of no stored data', self::class, $name, $diskFacet, $arguments,$diskLoadConfig + Disk::DEFAULT_LOAD_SPEC]);
            }
            $profileTime2 = microtime(true);
            try {
                $class = $this::class;
                $this->registerUncachedCall("{$class}::{$name} -> {$diskFacet}");
                $remote = $this->proxy->$name(...$arguments);
            } catch (\Error | \Exception $e) {
                if ($this->allowCacheFailover($name, $arguments) && $diskFacet) {
                    $diskFailover = Disk::load($diskFacet, $forceLoadConfig); // #TODO this can be optimized buy letting the front accept expired data and informing it...
                    if ($diskFailover) {
                        \Saf\Util\Profile::ping(['failover call', self::class, $name, $arguments, $e::class,$e->getMessage(), $e->getFile(), $e->getLine(),]);
                        //Audit::save('api failover',[$this->proxy::class, $name]);
                        return $diskFailover;
                    }
                }
                throw $e;
            }
            $profileTime3 = microtime(true);
            $pregate = number_format($profileTime2 - $profileTime, 6);
            $postgate = number_format($profileTime3 - $profileTime2, 6);
            $diskSaveConfig = $diskSpec; #TODO AGE, FUZZY
            $diskFacet && Disk::canStore($remote)
                && Disk::save($diskFacet, $remote, $diskSaveConfig + Disk::DEFAULT_SAVE_SPEC);
            $memoryIndex && Memory::save($memoryIndex, $remote);
            \Saf\Util\Profile::ping(['uncached call', self::class, $name, $arguments, $pregate, $postgate]);
            return $remote;
        } else {
            $class = get_class($this->proxy);
            throw new \Error("Call to ::{$name} not supported in Cache proxy: {$class}");
        }
    }

    /**
     * returns a string that can be used as an array key
     */
    public static function indexSafe(mixed $data): string
    {
        if (is_array($data)) {
            //#TODO more checks that this is an array of stringables
            return implode('-', $data);
        }
        return (string)$data;
    }

    /**
     * indicates if the last __call loaded from cache, and which cache if so
     * implementation for abstract Cachable::lastCallCached()
     */
    public function lastCallCached(?string $name = null): null|string|bool
    {
        return $this->lastCached;
    }

    /**
     * implementation for abstract Cachable::getCached()
     */
    public function getCached(): ?object
    {
        return $this;
    }

    /**
     * implementation for abstract Cachable::getCacheIndex()
     */
    public function getCacheIndex(string $storageMethod, string $name, $arguments = null): ?string
    {
        $proxy = $this->getProxy();
        return $proxy?->getCacheIndex($storageMethod, $name, $arguments);
        //$baseIndex = $proxy?->getCacheIndex($storageMethod, $name, $arguments);
        //$indexQuery = $this->getCacheQuery($storageMethod, $name, $arguments);
        //return "{$baseIndex}{$indexQuery}";
    }

    /**
     * implementation for abstract Cachable::getCacheIndex()
     */
    public function getCacheStrategy(string $storageMethod, string $name, $arguments = null): null|int|array
    {
        $proxy = $this->getProxy();
        return $proxy?->getCacheStrategy($storageMethod, $name, $arguments);
    }

    /**
     * implementation for abstract Cachable::getCacheIndex()
     */
    public function getCacheQuery(string $storageMethod, string $name, $arguments = null): string
    {
        $proxy = $this->getProxy(); //#TODO on all of these test $proxy and if it is self, then return falsey
        return $proxy?->getCacheQuery($storageMethod, $name, $arguments);
    }

    /**
     * implementation for abstract Cachable::getCacheSpec()
     */
    public function getCacheSpec(string $cacheClassName): null|string|array
    {
        $proxy = $this->getProxy();
        return $proxy?->getCacheSpec($cacheClassName);
    }

    /**
     * implementation for abstract Cachable::autoThrottle()
     */
    public function cacheOnlyMode(?string $name = null, $arguments = null): bool
    {
        return false;
    }

    /**
     * default sideLoad is noop
     */
    public function sideLoad(mixed $fromDisk, string $name, $arguments = null): object
    {
        return $this;
    }

    // /** //#TODO figure out if this can be implemented in Front and not inheriters (there was a cyclical issue previously)
    //  * implementation for abstract Cachable::getProxy()
    //  */
    // public function getProxy(): ?object
    // {
    //     return $this->proxy;
    // }

    /**
     * implementation for abstract Cachable::autoThrottle()
     */
    public function autoThrottle(): bool
    {
        return false;
    }

    /**
     * default registerUncachedCall is noop
     */
    public function registerUncachedCall(?string $label): ?object
    {
        return $this;
    }

}