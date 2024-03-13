<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base Driver for Disk Based Caching
 */

namespace Saf\Cache;

class Disk {

    public const DEFAULT_MAX_AGE = 60;
    public const AGE_FUZZY = 'fuzzy';

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
            ? (self::getPath($facet) . $facet)
            : null;
    }

    public static function available(?string $facet = null) : bool
    {
        $file = self::getFullPath($facet);
        return !is_null($file) && file_exists($file) && is_readable($file);
    }

    public static function load(?string $facet, int|string $maxAge = self::DEFAULT_MAX_AGE, $default = null)
    {
        $facet = self::fileSafeFacet($facet);
        if ($maxAge == self::AGE_FUZZY) {
            $fuzzyAge = 'foo';
            return self::fuzzyLoad($facet, $fuzzyAge, $default);
        }

        print_r([__FILE__,__LINE__, $facet, $maxAge, $default]); die;
        #TODO if $maxAge = fuzzy return 
    }

    public static function save(?string $facet, $data, $maxAge = self::DEFAULT_MAX_AGE) : bool
    {
        return true;
    }

    public static function fileSafeFacet(string $facet)
    {
        return str_replace(['\\','::'], '_', $facet);
    }

}