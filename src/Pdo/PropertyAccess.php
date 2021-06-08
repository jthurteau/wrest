<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Common property manipulation for Pdo\Db Objects
 */

namespace Saf\Pdo;

trait PropertyAccess
{
    
    public function getConnection()
    {
        return $this->connection;
    }

    public function getSchemaName()
    {
        return $this->schemaName;
    }

    public function setSchemaName(string $dbName)
    {
        //#TODO disconnect
        $this->schemaName = $dbName;
        return $this;
    }

    public function getHostName()
    {
        return $this->hostName;
    }

    public function setHostName(string $host)
    {
        //#TODO disconnect
        $this->hostName = $host;
        return $this;
    }

    public function getHostPort()
    {
        return $this->hostPort;
    }

    public function setHostPort($port)
    {
        $this->hostPort = $port;
        return $this;
    }

    public function getUser()
    {
        return $this->userName;
    }

    public function setUser(string $user)
    {
        //#TODO disconnect
        $this->userName = $user;
        return $this;
    }

    public function getDriverName()
    {
        return $this->driverName;
    }

    public function getAdditionalDsn()
    {
        return $this->additionalDsn;
    }

    public function setAdditionalDsn($additional)
    {
        $this->additionalDsn = $additional;
        return $this;
    }
}