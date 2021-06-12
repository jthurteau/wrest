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

trait RequestHandlerCommon {

    // protected function rootRequest(ServerRequestInterface $request){
        
    // }

    protected function allowed($resource, $user)
    {
        $keys = $user->getDetail('keys');
        if (
            in_array($resource, $this->accessList['open'])
        ) {
            return 'open-access';
        }
        if (count($keys) > 0 && in_array($resource, $this->accessList['key'])) {
            return 'key-access';
        }
        foreach($keys as $key) {
            $keyName = Keys::keyName($key);
            if (
                key_exists($keyName, $this->accessList) 
                && in_array($resource, $this->accessList[$keyName])
            ) {
                return "{$keyName}-key-access";
            }
        }
        if (in_array('sysAdmin', $user->getRoles())) {
            return 'sysAdmin-role-access';
        }
        foreach($user->getRoles() as $role) {
            $keyName = "{$role}-role";
            if (
                key_exists($keyName, $this->accessList) 
                && in_array($resource, $this->accessList[$keyName])
            ) {
                return "{$keyName}-role-access";
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

    public static function getResourceStack(ServerRequestInterface $request)
    {
        return explode('/', $request->getAttribute(self::STACK_ATTRIBUTE, ''));
    }
}