<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for authorization roles
 */

namespace Saf\Auth;

use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
use Saf\Hash;
use Saf\Utils\Filter\Truthy;

class Roles
{
    public const ALL = null;
    public const DEREFERENCE = true;

    protected static $registry = [
        'roles' => [],
        'users' => [],
    ];

    public function __invoke(ContainerInterface $container, string $name, callable $callback) : Object
    {
		$roleRegistry = Container::getOptional($container, ['config','acl','roles'], []);
        self::init($roleRegistry);
        return $callback();
    }

    public static function init($config = [])
    {
        foreach($config as $roleName => $roleConfig) {
            if (key_exists('roles', $roleConfig) && is_array($roleConfig['roles'])) {
                self::$registry['roles'] = $roleConfig['roles'];
                #TODO detect and prevent cyclical references
                //self::cyclicalCheck();
            }
            if (key_exists('users', $roleConfig) && is_array($roleConfig['users'])) {
                foreach($roleConfig['users'] as $user) {
                    self::addUserRole($user, $roleName);
                }
            } elseif(key_exists('users', $roleConfig)) {
                self::addUserRole($roleConfig['users'], $roleName);
            }
        }
    }

    public static function addUserRole($user, $role)
    {
        if(key_exists($user, self::$registry['users'])){
            self::$registry['users'][$user][] = $role;
        } else {
            self::$registry['users'][$user] = [$role];
        }
        if (!key_exists($role, self::$registry['roles'])) {
            self::$registry['roles'][$role] = [];
        }
        if (!key_exists('users', self::$registry['roles'][$role])) {
            self::$registry['roles'][$role]['users'] = [$user];
        } else {
            self::$registry['roles'][$role]['users'][] = $user;
        }
    }

    public static function getRoles($user = '', $dereference = true)
    {
        if ($user === self::ALL) {
            $rootRoles = array_keys(self::$registry['roles']);
        } elseif (key_exists($user, self::$registry['users'])) { //#TODO handle array of users?
            $rootRoles = self::$registry['users'][$user];
        } else {
            return [];
        }
        return $dereference ? self::dereferenceRole($rootRoles) : $rootRoles; 
    }

    public static function getUsers($role = '', $dereference = true)
    {
        if ($role == self::ALL) {
            return array_keys(self::$registry['users']);
        } elseif (key_exists($role, self::$registry['roles'])) {
            $searchRoles = $dereference ? self::dereferenceRole($role) : [$role];
        } else {
            return [];
        }
        $users = [];
        foreach($searchRoles as $r) {
            $members = key_exists($r, self::$registry['roles']) ? self::$registry['roles'][$r] : [];
            if (key_exists('users', $members)) {
                $users = array_merge($users, $members['users']);
            }
        }
        return array_unique($users);
    }

    public static function dereferenceRole($role)
    {
        if (!is_array($role)) {
            return self::dereferenceRole([$role]);
        } elseif (is_array($role) && count($role) > 0) {
            $list = $role;
            foreach($role as $parentRole) {
                $childRoles = 
                    key_exists($parentRole, self::$registry['roles'])
                        && is_array(self::$registry['roles'][$parentRole])
                        && key_exists('roles', self::$registry['roles'][$parentRole])
                    ? Hash::coerce(self::$registry['roles'][$parentRole]['roles'])
                    : [];
                $list += self::dereferenceRole($childRoles);
            }
            return array_unique($list);
        }
        return [];        
    }
}