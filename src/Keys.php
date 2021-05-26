<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for authentication
 */

namespace Saf;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
use Saf\Hash;
use Saf\Utils\Filter\Truthy;
use Saf\Auto;
use Saf\Auth\Plugin\Local;
use Saf\Layout;
use Saf\Audit;
use Saf\Session;
// use Saf\Environment\Define;

class Keys
{
    public const DEFAULT_KEY_FIELD = 'key';
    public const KEYRING_FIELD = 'key';

    protected static $serviceKeys = [];
    protected static $keyring = [];
    protected static $keyField = self::DEFAULT_KEY_FIELD;

    public static function setServiceKeys($keyArray)
    {
        if (!is_array($keyArray)) {
            $keyArray = array($keyArray);
        }
        self::$serviceKeys = $keyArray;
    }

    public static function detect($includeSession = true)
    {
        return
            key_exists(self::$keyField, $_GET)
            ? $_GET[self::$keyField]
            : (
                key_exists(self::$keyField, $_POST)
                ? $_POST[self::$keyField]
                : (
                    $includeSession 
                        && isset($_SESSION) 
                        && key_exists(self::$keyField, $_SESSION)
                    ? $_SESSION[self::$keyField]
                    : null
                )
            );
    }

    public static function storeKey($key = null, $persist = false)
    {
        if (is_null($key)) {
            $key = self::detect(false);
        }
        if ($key){
            self::$keyring[] = $key;
            if( $persist && isset($_SESSION)) {
                if (!key_exists(self::KEYRING_FIELD, $_SESSION)) {
                    $_SESSION[self::KEYRING_FIELD] = [];
                }
                if (!in_array($key, $_SESSION[self::KEYRING_FIELD])) {
                    $_SESSION[self::KEYRING_FIELD][] = $key;
                }
            }
        }
    }

    public static function getKeyring()
    {
        return self::$keyring;
    }

    public static function setKeyField(string $field)
    {
        self::$keyField = $field;
    }

    public static function validKey($keyValue, $name = null)
    {
        $keyValue = trim((string)$keyValue);
        if (!is_null($name)) {
            return key_exists($name,self::$serviceKeys)
                && trim(self::$serviceKeys[$name]) === $keyValue;
        }
        foreach(self::$serviceKeys as $key) {
            if ($keyValue === trim($key)) {
                return true;
            }
        }
        return false;
    }

    public static function keyName($keyValue)
    {
        foreach(self::$serviceKeys as $keyName => $key) {
            if ($keyValue === trim($key)) {
                return $keyName;
            }
        }
        return null;
    }

}