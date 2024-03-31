<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Interface for Caching Strategies
 */

namespace Saf\Cache;

interface Strategy{

    /**
     * return if the $facet is stored, whether or not it is still valid
     */
    public static function available(string $facet): bool;

    /**
     * load $facet according to $spec
     */
    public static function load(string $facet, mixed $spec = null): mixed;

    /**
     * save $data in $facet according to $spec
     */
    public static function save(string $facet, mixed $data, mixed $spec = null): bool;

    // return if the stored data in $faced is available and valid to $spec
    // #TODO abstract public static function valid(string $facet, mixed $spec = null): bool;

    /**
     * clear data from facet
     */
    public static function forget(string $facet): void;

    /**
     * answers whether the data can be stored with this stragety (e.g. objects can't be disk stored)
     */
    public static function canStore(mixed $data): bool;

    /**
     * filters spec data. must be implemented but may return null or $spec
     */
    public static function parseSpec(mixed $spec): mixed;

    /**
     * retuns the Strategy's default spec for loading data
     */
    public static function getDefaultLoadSpec(): mixed;

    /**
     * retuns the Strategy's default spec for loading data
     */
    public static function getDefaultSaveSpec(): mixed;

}