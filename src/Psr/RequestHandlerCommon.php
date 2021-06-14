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
use Saf\Keys;
use Saf\Utils\UrlRewrite;

trait RequestHandlerCommon {

    // protected function rootRequest(ServerRequestInterface $request){
        
    // }

    public abstract static function defaultRequestSearchOrder() : string;

    public abstract static function stackAttributeField() : string;

    protected function allowed($resource, $user = null)
    {
        //#TODO patch in with configed routes
        //$accessList = Hash::deepMerge($this->accessList,$globalAccess);
        $keys = $user ? $user->getDetail('keys') : [];
        $roles = $user ? $user->getRoles() : [];
        if ($this->matchRoute($resource, 'open')) {
            return 'open-access';
        }
        if (
            count($keys) > 0 
            && $this->matchRoute($resource, 'key')
        ) {
            return 'key-access';
        }
        foreach($keys as $key) {
            $keyName = Keys::keyName($key);
            if ($this->matchRoute($resource, $keyName)) {
                return "{$keyName}-key-access";
            }
        }
        if ($user && in_array('sysAdmin', $user->getRoles())) {
            return 'sysAdmin-role-access';
        }
        foreach($roles as $role) {
            $roleName = "{$role}-role";
            if ($this->matchRoute($resource, $roleName)) {
                return "{$roleName}-role-access";
            }
        }
        return false;
    }

    protected function matchAcl($resource, $list)
    {
        foreach($list as $resourceToken) {
            if (
                '*' == $resourceToken
                || ('' == $resource && '.' == $resourceToken)
                || $resource == $resourceToken
                || (
                    strpos($resourceToken, '*') !== false
                    && self::matchToken($resource, $resourceToken)
                )
            ) {
                return true;
            }
        }
        return false;
    }

    protected static function matchToken($string, $match)
    {
        return false; //#TODO
    }

    protected function matchRoute($resource, $in)
    {
        return
            key_exists($in, $this->accessList) 
            && (
                $this->accessList[$in] == '*'
                || $this->accessList[$in] === $resource
                || (
                    is_array($this->accessList[$in]) 
                    && in_array($resource, $this->accessList[$in])
                )
            );
    }

    protected static function getForward(ServerRequestInterface $request)
    {
        $url = trim(self::extractFromRequest(['get' => 'forwardUrl'], $request, '')); //$request->getParam('forwardUrl'));
        $code = trim(self::extractFromRequest(['get' => 'forwardCode'], $request, '')); //trim($request->getParam('forwardCode')));   
        return $url ? UrlRewrite::decodeForward($url) : UrlRewrite::decodeForward($code);
    }

    public static function getResourceStack(ServerRequestInterface $request)
    {
        return explode('/', $request->getAttribute(self::stackAttributeField(), ''));
    }

    public static function successful($result)
    {
        return $result && is_array($result) && key_exists('success', $result) && $result['success'];
    }

	/**
	 * Auto extract a request param from one or more sources, 
     * substituting optional default if not present.
	 * $sources will be searched iteratively, and the first match returned.
	 * $sources can be an integer as a shortcut for array('stack' => <int>)
	 * $sources can be a string as a shortcut for array('request' => <string>)
	 * the 'request' facet searches attributes, post, and get in that order
     * alternatively any order of some or all of the letters: a p g
     * can be specified for a custom search order of the request
	 * @param mixed $sources string, int, array indicating one or more sources
	 * @param Psr\Http\Server\RequestHandlerInterface $request request object to use
	 * @param mixed $default value to return if no match is found, defaults to NULL
	 * #TODO #1.5.0 add option for each source to be an array so more than one value in each can be searched
	 */
	protected static function extractFromRequest($sources, ServerRequestInterface $request, $default = null)
	{
		$result = $default;
		if (!is_array($sources)) {
			$sources = is_int($sources) ? ['stack' => $sources] : ['request' => $sources];
		}
        //key_exists('phpinfo', $request->getQueryParams())
		foreach($sources as $source => $index) {
			if (is_int($source)) {
				$source = is_int($index) ? 'stack' : 'request';
			}
			switch ($source) {
				case 'stack' :
					$stack = self::getResourceStack($request);
					if (key_exists($index, $stack) && '' !== $stack[$index] ) {
						return $stack[$index];
					}
					break;
                case 'attribute' :
                    $result = self::requestSearch($request, 'a', $index);
                    break;
				case 'post' :
                    $result = self::requestSearch($request, 'p', $index);
					break;
				case 'get' :
                    $result = self::requestSearch($request, 'g', $index);
					break;
				// case 'session' : //#TODO #1.1.0 deep thought on if this should be allowed 
				// 	if (isset($_SESSION) && is_array($_SESSION) && array_key_exists($index, $_SESSION)) {
				// 		return $_SESSION[$index];
				// 	}
				// 	break;
				case 'request' :
                    $result = self::requestSearch($request, self::defaultRequestSearchOrder(), $index);
                    break;
					// if ($request->has($index)) {
					// 	return $request->getParam($index);
					// }
                default:
                    $result = self::requestSearch($request, $source, $index);
			}
            if (!is_null($result)) {
                return $result;
            }
		}
		return $default;
	}

    protected static function requestSearch(ServerRequestInterface $request, string $order, $index)
    {  
        $order = array_unique(str_split($order));
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
}