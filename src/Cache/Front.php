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

abstract class Front implements Cachable {

    public const DISK_CACHE_CLASS = Disk::class;
    public const MEMORY_CACHE_CLASS = Memory::class;
    //public const DB_CACHE_CLASS = Db::class;

    public const STRATEGY_SINGLETON = 2;
    public const STRATEGY_INDEX = 3;
    public const STRATEGY_MULTI = 4;
    public const STRATEGY = 'strategy';
    public const INDEX = 'index';
    public const HANDLER = 'handler';
    public const INDEX_LABEL = 'label';
    public const CONFIG_DEFAULT = '*';

    protected $proxy = null;
    protected ?Front $cache = null;

    protected null|string|bool $lastCached = null; //#TODO configurable reporting level for this
    protected ?string $diskPath = null;

    public function __construct(Cachable $proxy) {
        $this->proxy = $proxy;
        $this->cache = $this;
        $this->diskPath = $this->proxy->getCacheSpec(Disk::class);
    }

    public function __call($name, $arguments)
    {
        $this->lastCached = false;
        //#TODO figure out a uid strategy for proxy (e.g. if two objects of the same class need to use the same cache facet)
        if (method_exists($this->proxy, $name)){
            $profileTime = microtime(true);
            $memoryIndex = $this->proxy->getCacheIndex(self::MEMORY_CACHE_CLASS, $name, $arguments);
            $memory = $memoryIndex ? Memory::load($memoryIndex) : null;
            if (!is_null($memory)) {
                $this->lastCached = self::MEMORY_CACHE_CLASS . "::{$name}";
                return is_callable($memory) ? $memory(...$arguments) : $memory;
            } 
            $diskIndex = $this->proxy->getCacheIndex(self::DISK_CACHE_CLASS, $name, $arguments);
            \Saf\Util\Profile::ping(['verifying cache indexes', self::class, $name, $memoryIndex, $diskIndex, $arguments]);
            $diskAge = null; // $this->proxy->getCacheExpiration($diskIndex); // pack expiration into index?
            $disk =  $diskIndex ? Disk::load($diskIndex) : null;  //#TODO AGE, FUZZY
            if (!is_null($disk)) {
                $this->lastCached = self::DISK_CACHE_CLASS . "::{$name}";
                $memoryIndex ? Memory::save($memoryIndex, $disk) : $this->sideLoad($disk, $name, $arguments);
                return is_callable($disk) ? $disk(...$arguments) : $disk;
            }
            $profileTime2 = microtime(true);
            $remote = $this->proxy->$name(...$arguments);
            $profileTime3 = microtime(true);
            $pregate = number_format($profileTime2 - $profileTime, 6);
            $postgate = number_format($profileTime3 - $profileTime2, 6);
            $diskIndex && Disk::canStore($remote) && Disk::save($diskIndex, $remote, $this->diskPath);
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
        //return $this->proxy?->getCacheIndex($storageMethod, $name, $arguments);
    }

    /**
     * implementation for abstract Cachable::getCacheSpec()
     */
    public function getCacheSpec(string $cacheClassName): ?string
    {
        $proxy = $this->getProxy();
        return $proxy?->getCacheSpec($cacheClassName);
        //return $this->proxy?->getCacheSpec($cacheClassName);
    }

    /**
     * default sideLoad is noop
     */
    public function sideLoad(mixed $fromDisk, string $name, ?array $arguments = null): self
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

}