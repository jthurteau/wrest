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
use Saf\Psr\StandardRequestHandler;
use Saf\Keys;
use Saf\Auto;
use Saf\Utils\UrlRewrite;

use Saf\Utils\Time;

trait RequestHandlerCommon {

    // protected function rootRequest(ServerRequestInterface $request){
        
    // }

    public abstract static function defaultRequestSearchOrder() : string;

    public abstract static function stackAttributeField() : string;

    public function translate(string $message) : string
    {
        //#NOTE this SHOULD be defined in the using class or one of its base classes, but is considered optional
        return $message;
    }

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
            $this->matchRoute($resource, 'any-user')
        ) {
            if ($user->getIdentity()) {
                return 'authorized-access';
            }
        }
        $userName = $user?->getIdentity() ?: 'none';
        if ($user && $this->matchRoute($resource, "user-{$userName}")) {
            return 'authorized-access';
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
        foreach($roles as $role) {
            $roleName = "{$role}-role";
            if ($this->matchRoute($resource, $roleName)) {
                return "{$roleName}-role-access";
            }
        }
        return false;
    }

    /**
     * @param $resource
     * @param $user
     * @return array|string
     * deprecate in favor of accessOptions
     */
    protected function accessRecommendation($resource, $user = null)
    {
        $recommendation = [];
        //#TODO patch in with configed routes
        //$accessList = Hash::deepMerge($this->accessList,$globalAccess);
        $list = $this->accessList;
        $keys = $user ? $user->getDetail('keys') : [];
        $roles = $user ? $user->getRoles() : [];
        if ($this->matchRoute($resource, 'open')) {
            return 'open-access';
        }
        if ($this->matchRoute($resource, 'any-user')) {
            if ($user->getIdentity()) {
                return 'authorized-access';
            }
            $recommendation = 'login-required';
        }
        $anyKeyAccess = $this->matchRoute($resource, 'key');
        if (count($keys) > 0 && $anyKeyAccess) {
            return 'key-access';
        } elseif ($anyKeyAccess && !$recommendation) {
            $recommendation = 'key-required';
        }
        foreach($list as $criteria => $toss){
            $isKeyAccess = false;
            $isUserAccess = false;
            $isRoleAccess = false;
            $possibleRecommendation =
                $isKeyAccess
                ? 'key-required'
                : (
                    $isRoleAccess || $isUserAccess
                    ? 'login-required'
                    : false
                );
            if (!$recommendation && $recommendation) {

            }
        }
        return $recommendation;
    }

    protected function accessOptions($resource, $user = null): array
    {
        $recommendation = [];
        //#TODO patch in with configed routes
        //$accessList = Hash::deepMerge($this->accessList,$globalAccess);
        $list = $this->accessList;
        $keys = $user ? $user->getDetail('keys') : [];
        $roles = $user ? $user->getRoles() : [];
        if ($this->matchRoute($resource, 'open')) {
            return ['open-access'];
        }
        if ($this->matchRoute($resource, 'any-user')) {
            if ($user->getIdentity()) {
                return ['authorized-access'];
            }
            $recommendation[] = 'login-required';
        }
        $anyKeyAccess = $this->matchRoute($resource, 'key');
        if (count($keys) > 0 && $anyKeyAccess) {
            return ['key-access'];
        } elseif ($anyKeyAccess) {
            $recommendation[] = 'key-required';
        }
        foreach($list as $criteria => $toss){
            $isKeyAccess =
                str_ends_with($criteria, '-key')
                && $this->matchRoute($resource, $criteria);
            $isUserAccess =
                str_ends_with($criteria, '-user')
                && $this->matchRoute($resource, $criteria);
            $isRoleAccess =
                str_ends_with($criteria, '-role')
                && $this->matchRoute($resource, $criteria);

            $isKeyAccess && !in_array('key-required', $recommendation)
                && ($recommendation[] = 'key-required');
            ($isUserAccess || $isRoleAccess) && !in_array('login-required', $recommendation)
                && ($recommendation[] = 'login-required');
            //#TODO add an optional scoping param to allowed and return X-user-access, X-role-access, X-key-access
        }
        return $recommendation;
    }

    protected static function resourceMap(string $handlerNamespace, $resource)
    {
        $namespaceStack = explode('\\', $handlerNamespace);
        foreach ($resource as $index=>$part) {
            $resource[$index] = ucfirst($part);
        }
        if (count($namespaceStack) > 1 && $namespaceStack[count($namespaceStack) - 1] == 'Handler') {
            $resourceNamespaceStack = $namespaceStack;
            $resourceNamespaceStack[count($resourceNamespaceStack) - 1] = 'Resource';
            return array_merge($resourceNamespaceStack, $resource);
        }
        return array_merge($namespaceStack, $resource);
    }

    protected function route($resource, ServerRequestInterface $request)
    {
        if (count($resource) < 3) {
            return [
                'success' => false,
                'request' => $resource,
                'message' => 'resource routing underflow'
            ];
        }
        $base = implode('\\', array_slice($resource, 0, 3));
        $rest = array_slice($resource, 3);
        if (class_exists($base) && method_exists($base, 'handle')) {
            if (key_exists($base, $this->models)) {
                $match = $this->models[$base];
            } else {
                $match = new $base();
            }
            return $match->handle($request, $rest);
        }
        // // $match = $base; //#TODO think on this more
        // $test = $base;
        // foreach ($rest as $part) {
        //     $test = $test .= "\\{$part}";
        //     if (
        //         !Auto::validClassName($part) 
        //     ) {
        //         break;
        //     }
        //     if (
        //         class_exists($test) 
        //         && method_exists($test, 'handle')
        //     ) {
        //         $match = $test;
        //     }
        // }
        // if (
        //     $match != $base 
        //     || (class_exists($base) && method_exists($base, 'handle'))
        // ) {
        //     $match = new $base();
        //     return $match->handle($request, $rest);
        // }
        return [
            'success' => false,
            'request' => $resource,
            'message' => 'resource routing failure'
        ];
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
            && $this->searchRoutes($resource, $this->accessList[$in]);
    }

    protected function searchRoutes($resource, $list)
    {
        if ($list == '*') {
            return true;
        }
        is_array($resource) || ($resource = explode('/', $resource));
        is_array($list) || ($list = [$list]);
        foreach($list as $route) {
            is_array($route) || ($route = explode('/', $route));
            if (
                $route == $resource 
                || $this->matchResource($resource, $route)
            ) {
                return true;
            }
        }
        return false;
    }

    protected function matchResource(array $resource, array $route)
    {
        foreach($resource as $part) {
            if(
                !count($route)
                || (
                    '*' != $route[0]
                    && $part != $route[0] #TODO matchPart(string $part, string $route)
                )
            ) {
                return false;
            }
            $current = array_shift($route);
            if (!count($route) && $current == '*') {
                return true;
            }
        }
        return true;
    }

    protected static function getForward(ServerRequestInterface $request)
    {
        $url = trim(self::extractFromRequest(['get' => 'forwardUrl'], $request, '')); //$request->getParam('forwardUrl'));
        $code = trim(self::extractFromRequest(['get' => 'forwardCode'], $request, '')); //trim($request->getParam('forwardCode')));   
        return $url ? UrlRewrite::decodeForward($url) : UrlRewrite::decodeForward($code);
    }

    public static function getResourceStack(ServerRequestInterface $request, $field = StandardRequestHandler::STACK_ATTRIBUTE) : array
    {
        return explode('/', $request->getAttribute($field, ''));
    }

    public static function successful($result)
    {
        return $result && is_array($result) && key_exists('success', $result) && $result['success'];
    }

    /** #TODO deprecate in favor of Saf\Psr\Request\Common::extractParam
     * Auto extract a request param from one or more sources, 
     * substituting optional default if not present.
     * $sources will be searched iteratively, and the first match returned.
     * $sources can be an integer as a shortcut for array('stack' => <int>)
     * $sources can be a string as a shortcut for array('request' => <string>)
     * the 'request' facet searches attributes, post, and get in that order
     * alternatively any order of some or all of the letters: a p g
     * can be specified for a custom search order of the request
     * @param mixed $sources string, int, array indicating one or more sources
     * @param Psr\Http\Message\ServerRequestInterface $request request object to use
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
                    $result = self::requestSearchDep($request, 'a', $index);
                    break;
                case 'post' :
                    $result = self::requestSearchDep($request, 'p', $index);
                    break;
                case 'get' :
                    $result = self::requestSearchDep($request, 'g', $index);
                    break;
                // case 'session' : //#TODO #1.1.0 deep thought on if this should be allowed 
                //     if (isset($_SESSION) && is_array($_SESSION) && array_key_exists($index, $_SESSION)) {
                //         return $_SESSION[$index];
                //     }
                //     break;
                case 'request' :
                    $result = self::requestSearchDep($request, StandardRequestHandler::DEFAULT_REQUEST_SEARCH, $index);
                    break;
                    // if ($request->has($index)) {
                    //     return $request->getParam($index);
                    // }
                default:
                    $result = self::requestSearchDep($request, $source, $index);
            }
            if (!is_null($result)) {
                return $result;
            }
        }
        return $default;
    }

    protected static function updateRequestStack(ServerRequestInterface $request, array $resourceStack, string $field = StandardRequestHandler::STACK_ATTRIBUTE) : ServerRequestInterface
    {
        return $request->withAttribute($field, implode('/', $resourceStack));
    }

    protected static function requestSearchDep(ServerRequestInterface $request, string $order, $index)
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

    protected function &postProcess(array &$result, $request = null) : array
    {
        Time::getOffset() && ($result['safTimeOffset'] = Time::getOffset());
        if ($request) {
            // $compatMode = $_GET && array_key_exists('compat', $_GET);
            // $result['safCompatMode'] = true;
            // $lazyMode = $_GET && array_key_exists('lazy', $_GET);
            // $result['safLazyMode'] = true;
        }
        return $result;
    }
}