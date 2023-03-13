<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for Application Pipeline Adapters 
 */

namespace Saf;

class Pipe
{

    public static function main(&$canister = [])
    {
        throw new \Exception('Unhandled Main Pipe');
    }

    public static function configure(&$canister = [])
    {

    }

    public static function optional($key, &$ref, $canister)
    {
        key_exists($key, $canister) && ($ref = $canister[$key]); //#NOTE don't &= here it's already a reference
    }

    public static function required($key, &$ref, $canister)
    {
        if (key_exists($key, $canister)) {
            $ref &= $canister[$key];
        } else {
            throw new \Exception("Required Saf/Pipe configuration option {$key} absent"); //#TODO make this a framework exception
        }
        return $ref;
    }

}