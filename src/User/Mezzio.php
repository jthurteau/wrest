<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Class for adapting Mezzio User Management
 */

namespace Saf\User;

use Mezzio\Authentication\UserRepositoryInterface;
use Mezzio\Authentication\UserInterface;

class Mezzio implements UserInterface, UserRepositoryInterface
{

    public function __construct($options)
    {
        
    }

    public function authenticate(string $credential, string $password = null) : ?UserInterface
    {
        return null;
    }

    /**
     * Get the unique user identity (id, username, email address, etc.).
     */
    public function getIdentity() : string
    {
        return '';
    }

    /**
     * Get all user roles.
     *
     * @return string[]
     */
    public function getRoles() : array
    {
        return [];
    }

    /**
     * Get the detail named $name if present; return $default otherwise.
     */
    public function getDetail(string $name, $default = null)
    {
        return $default;
    }

    /**
     * Get all additional user details, if any.
     */
    public function getDetails() : array
    {
        return [];
    }
}