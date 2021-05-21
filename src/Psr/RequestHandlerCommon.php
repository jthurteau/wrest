<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Trait for PSR RequestHandler implementations
 */

namespace Saf\Psr;

use Psr\Http\Message\ServerRequestInterface;

trait RequestHandlerCommon {

    protected function rootRequest(ServerRequestInterface $request){
        
    }

}