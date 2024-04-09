<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Front trait to allow Resources access to protected Front methods
 */

namespace Saf\Module;

trait UnionFront {

    protected function getProxy()//: object // i think
    {
        return function(null|array|string $request = null) {
            return is_null($request) ? $this : $this->proxy($request);
        };
    }

    /**
     * 
     */
    protected function proxy(array|string $request)
    {
        $method = is_array($request) ? array_shift($request) : $request;
        $params = is_array($request) ? array_shift($request) : null;
        if($method && is_string($method) && method_exists($this, $method)) {
            return is_array($params) ? $this->$method(...$params) : $this->$method();
        }
    }

}