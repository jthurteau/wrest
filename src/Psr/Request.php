<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility Class for PSR7
 */

namespace Saf\Psr;

use Psr\Http\Message\ServerRequestInterface;

class Request {

    public static function isJson(ServerRequestInterface $request){
        return false;
    }

}