<?php

declare(strict_types=1);

namespace Saf\Legacy\Zend;

use Saf\Psr\RequestHandlerCommon;
use Psr\Http\Message\ServerRequestInterface;

trait Controller {

    /**
     * Auto extract a request param from one or more sources, substituting
     * optional default if not present.
     * $sources will be searched iteratively, and the first match returned.
     * $sources can be an integer as a shortcut for array('stack' => <int>)
     * $sources can be a string as a shortcut for array('request' => <string>)
     * the 'request' facet is anything in the controller's "request" object.
     * #NOTE the session is intentionally excluded as an option
     * @param mixed $sources string, int, array indicating one or more sources
     * @param mixed $default value to return if no match is found, defaults to NULL
     * @param Psr\Http\Message\ServerRequestInterface|Zend_Controller_Request_Abstract $request optional alternate request object to use
     * #TODO #1.5.0 add option for each source to be an array so more than one value in each can be searched
     */
    protected function extractRequestParam($sources, $default = null, $request = null)
    {
        $result = $default;
        if (is_null($request)) {
            //$request = $this->getRequest();
            $request = $this->currentRequest;
        }
        if (!is_array($sources)) {
            $sources = 
                is_int($sources) 
                ? array('stack' => $sources)
                : array('request' => $sources);
        }
        foreach($sources as $source => $index) {
            if (is_int($source)) {
                $source = is_int($index)
                    ? 'stack'
                    : 'request';
            }
            switch ($source) {
                case 'stack' :
                    $stack = 
                        is_a($request, ServerRequestInterface::class, false) 
                        ? $this->currentParent->getResourceStack($request) 
                        : $request->getParam('resourceStack');
                    if (key_exists($index, $stack) && '' !== $stack[$index] ) {
                        return $stack[$index];
                    }
                    break;
                case 'get' :
                    #TODO handle for ServerRequestInterface
                    $get = $request->getQuery();
                    if (array_key_exists($index, $get)) {
                        return $get[$index];
                    }
                    break;
                case 'post' :
                    #TODO handle for ServerRequestInterface
                    $post = $request->getPost();
                    if (array_key_exists($index, $post)) {
                        return $post[$index];
                    }
                    break;
                case 'session' : //#TODO #1.1.0 deep thought on if this should be allowed 
                    if (isset($_SESSION) && is_array($_SESSION) && key_exists($index, $_SESSION)) {
                        return $_SESSION[$index];
                    }
                    break;
                case 'request' :
                    #TODO handle for ServerRequestInterface
                    if ($request->has($index)) {
                        return $request->getParam($index);
                    }
            }
        }
        return $default;
    }
    
    protected function extractMultiIdString($idString, $separator = '_'){
        if (!is_array($idString)) {
            $idString = explode($separator, $idString);
        }
        foreach($idString as $index => $id) {
            $idString[$index] = (int)$id;
            if (!$idString[$index]) {
                unset($idString[$index]);
            }
        }
        return $idString;
    }

}