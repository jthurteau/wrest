<?php

/**
 * Static Utility and Proficient Singleton class for managing HTTP status headers
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html for information on these statuses
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

namespace Saf\Util\Http;

class Cors
{

    const HEADER_ORIGIN = 'origin';
    const HEADER_HEADER = 'headers';
    const HEADER_XHEADER = 'xheaders';
    const HEADER_METHOD = 'methods';
    const HEADER_MAXAGE = 'maxage';
    const HEADER_CRED = 'credentials';

    protected static ?string $origin = null;
    protected static ?string $methods = null;
    protected static ?string $headers = null;
    protected static ?string $xheaders = null;
    protected static ?string $maxage = null;
    protected static ?bool $credentials = null;
    protected static $all = [
        self::HEADER_ORIGIN,
        self::HEADER_HEADER,
        self::HEADER_XHEADER,
        self::HEADER_METHOD,
        self::HEADER_MAXAGE,
        self::HEADER_CRED,
    ];
    protected static ?Cors $singleton = null;

    private function __construct()
    {

    }

    public static function instance(): Cors
    {
        is_null(self::$singleton) && (self::$singleton = new Cors());
        return self::$singleton;
    }

    public function withOrigin(string $origin): Cors
    {
        self::setOrigin($origin);
        return $this;
    }

    public static function setOrigin(string $origin): void
    {
        self::$origin = $origin;
    }

    public function withMaxAge(string $origin): Cors
    {
        self::setMaxAge($origin);
        return $this;
    }

    public static function setMaxAge(string $maxage): void
    {
        self::$maxage = $maxage;
    }


    public function withMethods(string $origin): Cors
    {
        self::setMaxAge($origin);
        return $this;
    }

    public static function setMethods(string|array $methods): void
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }
        self::$methods = implode(', ', $methods);
    }

    public function allowHeaders(string|array $headers): Cors
    {
        self::setHeaders($headers);
        return $this;
    }

    public static function setHeaders(string|array $headers): void
    {
        if (!is_array($headers)) {
            $headers = array($headers);
        }
        self::$headers = implode(', ', $headers);
    }

    public function exposeHeaders(string|array $headers): Cors
    {
        self::setExposeHeaders($headers);
        return $this;
    }

    public static function setExposeHeaders(string|array $headers): void
    {
        if (!is_array($headers)) {
            $headers = array($headers);
        }
        self::$xheaders = implode(', ', $headers);
    }

    public function allowCredentials(): Cors
    {
        self::setAllowCredentials();
        return $this;
    }

    public static function setAllowCredentials(bool $allow = true): void
    {
        self::$credentials = $allow;
    }

    public static function get(null|string|array $headers = null): array
    {
        $lines = [];
        foreach(self::headers($headers) as $field => $header) {
            $lines[] = "{$field}: {$header}";
        }
        return $lines;
    }

    public function emit(null|string|array $headers = null): Cors
    {
        self::out($headers);
        self::reset();
        return $this;
    }

    public static function out(null|string|array $headers = null): void
    {
        foreach(self::get($headers) as $header) {
//            if ('commandline' == APPLICATION_PROTOCOL) {
//                print("Cors: {$string}\r\n");
//            } else {
                header($header);
//            }
        }
    }

    /**
     * returns headers
     * @param string $string which header, defaults to all
     */
    protected static function headers(null|string|array $headers = null): array
    {
        $return = [];
        if(is_null($headers)) {
            $headers = self::$all;
        } elseif (is_string($headers)) {
            $headers = [$headers];
        }
        foreach($headers as $header) {
            switch($header){
                case self::HEADER_ORIGIN:
                    !is_null(self::$origin)
                        && ($return['Access-Control-Allow-Origin'] = self::$origin);
                    break;
                case self::HEADER_HEADER:
                    !is_null(self::$headers)
                        && ($return['Access-Control-Allow-Headers'] = self::$headers);
                    break;
                case self::HEADER_XHEADER:
                    !is_null(self::$xheaders)
                        && ($return['Access-Control-Expose-Headers'] = self::$xheaders);
                    break;
                case self::HEADER_METHOD:
                    !is_null(self::$methods)
                        && ($return['Access-Control-Allow-Methods'] = self::$methods);
                    break;
                case self::HEADER_MAXAGE:
                    !is_null(self::$maxage)
                        && ($return['Access-Control-Max-Age'] = self::$maxage);
                    break;
                case self::HEADER_CRED:
                    self::$credentials === 'true'
                        && ($return['Access-Control-Allow-Credentials'] = self::$credentials);
                    break;
            }
        }
        return $return;
    }

    public static function reset(): void
    {
        self::$origin = null;
        self::$methods = null;
        self::$headers = null;
        self::$xheaders = null;
        self::$maxage = null;
        self::$credentials = null;
    }
}