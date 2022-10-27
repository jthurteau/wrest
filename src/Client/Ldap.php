<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base Client for LDAP Based Services
 */

namespace Saf\Client;

use Saf\Client\Ldap\Adapter;

class Ldap {
	
    protected ?Adapter $adapter = null;

	public function __construct($config = '')
	{
		$this->adapter = new Adapter($config);
	}

    public function connect()
    {
		$this->adapter->getConnection();
        $this->adapter->bind();
    }

    public function baseSearch(string $term, string|bool $context = false)
    {
        return $context ? $this->adapter->search($term, $context) : $this->adapter->search($term);
    }

    public function poll() : array
    {
        $return = $this->adapter->getErrors();
        $this->adapter->clearErrors();
        return $return;
    }

}
