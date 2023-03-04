<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Baseclass for PSR RequestHandler implementations
 */

namespace Saf\Psr;

class StandardRequestHandler {

    public const DEFAULT_REQUEST_SEARCH = 'apg';

    public const STACK_ATTRIBUTE = 'resourceStack';

    public const URI_PATH_DELIM = '/';

    public static function defaultRequestSearchOrder() : string
    {
        return self::DEFAULT_REQUEST_SEARCH;
    }

    public static function stackAttributeField() : string
    {
        return self::STACK_ATTRIBUTE;
    }

}