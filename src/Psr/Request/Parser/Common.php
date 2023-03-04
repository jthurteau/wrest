<?php

declare(strict_types=1);

namespace Saf\Psr\Request\Parser;

use Psr\Http\Message\ServerRequestInterface;
use Saf\Psr\StandardRequestHandler;

trait Common
{
    //abstract function getRequest() : ServerRequestInterface; //#TODO probbly deprecated, resourceHandlers would not be assigned a resource?

    /**
     * Auto extract a request param from one or more sources, 
     * substituting optional default if not present.
     * $map of sources will be searched iteratively, and the first match returned.
     * $map can be a single integer or string (auto converted to single-item array)
     * integer $map values are a shortcut for ['stack' => <int>]
     * string $map values are shortcut for ['request' => <string>]
     * the 'request' facet searches attributes, post, and get in that order
     * alternatively any order of some or all of the letters: a p g
     * can be specified for a custom search order of the request
     * @param mixed $sources <string>, <int>, array indicating one or more sources
     * @param Psr\Http\Message\ServerRequestInterface $request request object to use
     * @param mixed $default value to return if no match is found, defaults to <null>
     * #TODO #2.1.0 add option for each source to be an array so more than one value in each can be searched
     */
    protected function extractParam($map, ServerRequestInterface $request, $default = null)
    {
        //is_null($request) && ($request = $this->getRequest());
        if (is_null($request)) {
            return $default;
        }
        if (!is_array($map)) {
            $map = is_int($map) ? ['stack' => $map] : ['request' => $map];
        }

        foreach($map as $source => $index) {
            $branchResult = null;
            //#TODO handle index as array (multiple searches)
            if (is_int($source)) {
                $source = is_int($index) ? 'stack' : 'request';
            }
            $stringIndex = (string)$index;
            switch ($source) {
                case 'stack' :
                    $stack = self::getResourceStack($request);
                    if (key_exists($index, $stack) && '' !== $stack[$index] ) {
                        $branchResult =  $stack[$index];
                    }
                    break;
                case 'attribute' :
                    $branchResult = self::requestSearch($request, "a:{$stringIndex}");
                    break;
                case 'post' :
                    $branchResult = self::requestSearch($request, "p:{$stringIndex}");
                    break;
                case 'get' :
                    $branchResult = self::requestSearch($request, "g:{$stringIndex}");
                    break;
                // case 'session' : //#TODO #2.1.0 deep thought on if this should be allowed 
                //     if (isset($_SESSION) && is_array($_SESSION) && array_key_exists($index, $_SESSION)) {
                //         return $_SESSION[$index];
                //     }
                //     break;
                case 'request' :
                    $defaultSearch = StandardRequestHandler::DEFAULT_REQUEST_SEARCH;
                    $branchResult = self::requestSearch($request, "{$defaultSearch}:{$stringIndex}");
                    break;
                default:
                    $branchResult = self::requestSearch($request, "{$source}:{$stringIndex}");
            }
            if (!is_null($branchResult)) {
                return $branchResult;
            }
        }
        return $default;
    }

    public static function requestSearch(ServerRequestInterface $request, string $search)
    {
        $sourceParts = explode(':', $search, 2);
        $source = count($sourceParts) ? $sourceParts[0] : StandardRequestHandler::DEFAULT_REQUEST_SEARCH;
        $index = count($sourceParts) ? $sourceParts[1] : $sourceParts[0];
        $order = array_unique(str_split($source));
        foreach($order as $facet) {
            switch ($facet) {
                case 'a':
                    $attribute = $request->getAttribute($index, null);
                    if (!is_null($attribute)) {
                        return $attribute;
                    }
                    break;
                case 'p':
                    $post = $request->getParsedBody();
                    if (is_array($post) && key_exists($index, $post)) {
                        return $post[$index];
                    } //#TODO parsedBody can also be an object?
                    break;
                case 'g':
                    $get = $request->getQueryParams();
                    if (key_exists($index, $get)) {
                        return $get[$index];
                    }
                    break;
            }
        }
        return null;
    }

    public static function getResourceStack(ServerRequestInterface $request, $field = null) : array
    {
        //$request = $this->getRequest();
        return 
            !is_null($request) 
            ? self::parseResourceUri(
                $request->getAttribute(self::getResourceStackAttribute())
            ) : [];
    }

    public static function getResourceStackAttribute() : string
    {
        return StandardRequestHandler::STACK_ATTRIBUTE;
    }

    public static function parseResourceUri(?string $uri) : array
    {
        return 
            !is_null($uri) 
            ? explode(StandardRequestHandler::URI_PATH_DELIM, $uri)
            : [];
    } 

}
