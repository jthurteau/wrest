<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for managing service modules
 */

namespace Saf\Module;

//use Psr\Http\Message\ServerRequestInterface;

interface ResourceInterface
{

    //public function handle(ServerRequestInterface $resource, $stack = []);
    public function handle($resource, $stack = []);

}