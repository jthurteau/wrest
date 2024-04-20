<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Parent class for a cachable resources
 */

namespace Saf\Cache;

interface Cachable {

    /**
     * returns a reference to the object (self or other) that handles caching for this object.
     * the returned object should implement __call and delegate any request that doesn't return
     * cached data to $this, passing paremeters verbatim.
     */
    public function getCached(): ?object;

    /**
     * calculate the storage facet for a given __call
     */
    public function getCacheIndex(string $storageMethod, string $name, $arguments = null): ?string;

    /**
     * returns configuration data for the Cache Method supported by $cacheClassName
     * null means, use the default settings. e.g. for \Saf\Cache\Disk a string path is used.
     */
    public function getCacheSpec(string $cacheClassName): ?string;

    /**
     * indicates if the last __call (matching $name if provided, otherwise, any) loaded from cache, and which cache if so
     */
    public function lastCallCached(?string $name = null): null|string|bool;

    /**
     * returns a reference to the 
     */
    public function getProxy(): ?object;

    /**
     * optional support for self stored cache after load from disk
     */
    public function sideLoad(mixed $fromDisk, string $name, $arguments = null): self;

}