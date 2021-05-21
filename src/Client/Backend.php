<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Class for Backending remote APIs
 */

namespace Saf\Client;

use Saf\Hash;

class Backend extends Http{

    public function __invoke(array $options) : Backend
    {
        $client = "Saf\\" . usfirst(Hash::extract('parser', $options, 'http'));
        return new $client($options);
    }
    

}