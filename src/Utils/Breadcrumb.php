<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility functions for Breadcrumb Navigation
 */

namespace Saf\Utils;

use Saf\Hash;

class Breadcrumb
{
    protected static $crumbs = ['Home' => '/'];

    protected static $baseUri = '/';
    protected static $baseUriMatches = [
        '{$baseUri}',
        '[baseUrl]' //#TODO PHP5 version backwards compatability
    ];

    public static function init($config = [], $baseUri = '/')
    {
        self::$baseUri = $baseUri;
        if (is_null($config)) {
            $config = [];
        } else if (is_object($config) && method_exists($config, 'toArray')) {
            $config = $config->toArray();
        }
        $crumbs = [];
        if (count($config) == 1) {
            reset($config);
            $sample = current($config);
            if (Hash::isNumericArray($sample)) {
                $config = $sample;
            }
        } else if (array_key_exists('url', $config) && array_key_exists('label', $config)) {
            $config = [['url' => $config['url'], 'label' => $config['label']]];
        }
        foreach($config as $fallBackLabel => $linkConfig) {
            if (is_object($linkConfig) && method_exists($linkConfig, 'toArray')) {
                $linkConfig = $linkConfig->toArray();
            }
            
            if (is_array($linkConfig)) {
                $label = array_key_exists('label', $linkConfig) ? $linkConfig['label'] : $fallBackLabel;
                if (array_key_exists('url', $linkConfig)) {
                    $url = str_replace(self::$baseUriMatches, self::$baseUri, $linkConfig['url']);
                    
                    $crumbs[$label] = ['url' => $url];
                } else {
                    $crumbs[$label] = ['status' => 'current'];
                }
            } else {
                $crumbs[$linkConfig] = ['status' => 'current'];
            }
        }
        self::set($crumbs);
    }

    public static function set($crumbs = [])
    {
        if (is_null($crumbs)) {
            $crumbs = [];
        } else if (is_object($crumbs) && method_exists($crumbs, 'toArray')) {
            $crumbs = $crumbs->toArray();
        }
        foreach($crumbs as $label => $crumb) {
            $crumbs[$label] = self::translateCrumb($crumb);
        }
        self::$crumbs = $crumbs;
    }
    
    public static function pushCrumb($label, $crumb = ['status' => 'current'])
    {
        if (!$crumb) {
            $crumb == ['status' => 'current'];
        }
        self::$crumbs[$label] = self::translateCrumb($crumb);
    }
    
    public static function removeCrumb($label)
    {
        if (array_key_exists($label, self::$crumbs)) {
            unset(self::$crumbs[$label]);
        }
    }
    
    public static function updateCrumb($label, $crumb = ['status' => 'current'], $newLabel = '')
    {
        if (key_exists($label, self::$crumbs)) {
            if ($newLabel == '' ) {
                self::$crumbs[$label] = $crumb;
            } else {
                $newCrumbs = [];
                foreach(self::$crumbs as $oldLabel => $oldCrumb) {
                    if ($oldLabel == $label) {
                        $newCrumbs[$newLabel] = self::translateCrumb($crumb);
                    } else {
                        $newCrumbs[$oldLabel] = $oldCrumb;
                    }
                }
                self::$crumbs = $newCrumbs;
            }
        } else {
            self::pushCrumb($newLabel !== '' ? $newLabel : $label , self::translateCrumb($crumb));
        }
    }
    
    public static function get()
    {
        return self::$crumbs;
    }

    public static function link()
    {
        return function() {
            return self::get();
        };
    }

    public static function translateCrumb($crumb)
    {
        if (is_array($crumb)) {
            if (key_exists('url', $crumb)) {
                $crumb['url'] = self::translateCrumb($crumb['url']);
            }
            return $crumb;
        } else {
            return str_replace('{$baseUri}', self::$baseUri, $crumb);
        }
    }

    public static function getBaseUriMatches()
    {
        return self::$baseUriMatches;
    }

}
