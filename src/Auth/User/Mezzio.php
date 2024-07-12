<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Class for adapting Mezzio User Management
 */

namespace Saf\Auth\User;

use Mezzio\Authentication\UserRepositoryInterface;
use Mezzio\Authentication\UserInterface;
use Saf\Hash;
use Saf\Auth\Roles;

class Mezzio implements UserInterface, UserRepositoryInterface
{

    protected $username = null;
    protected $details = [];

    public function __construct($options)
    {
        $this->username = Hash::extract('username', $options, '');
        if (key_exists('keys', $options)) {
            $this->details['keys'] = Hash::extract('keys', $options, '');
        }
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
        return !is_null($this->username) ? $this->username : '';
    }

    /**
     * Get all user roles.
     *
     * @return string[]
     */
    public function getRoles() : array
    {
        if (is_null($this->username)) {
            return [];
        }
        return Roles::getRoles($this->username, Roles::DEREFERENCE);
    }

    /**
     * Get the detail named $name if present; return $default otherwise.
     */
    public function getDetail(string $name, $default = null)
    {
        return key_exists($name,$this->details) ? $this->details[$name] : $default;
    }

    /**
     * Get all additional user details, if any.
     */
    public function getDetails() : array
    {
        return $this->details;
    }

    public function addDetails(string $name, $newDetails): Mezzio
    {
        if (key_exists($name, $this->details)) {
            if (is_array($this->details[$name]) && is_array($newDetails)) {
                $this->details[$name] = array_merge($this->details[$name], $newDetails);
            } elseif (is_array($this->details[$name])) {
                $this->details[$name][] = $newDetails;
            } elseif (is_array($newDetails)) {
                $this->details[$name] = array_merge([$this->details[$name]],$newDetails);
            } else {
                $this->details[$name] = [$this->details[$name], $newDetails];
            }
        } else {
            $this->details[$name] = $newDetails;
        }
        return $this;
    }
}