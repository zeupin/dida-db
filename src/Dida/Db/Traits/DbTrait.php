<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db\Traits;

trait DbTrait
{
    public function connect()
    {
        return $this->getConnection()->connect();
    }


    public function disconnect()
    {
        return $this->getConnection()->disconnect();
    }


    public function isConnected()
    {
        return $this->getConnection()->isConnected();
    }


    public function worksWell()
    {
        return $this->getConnection()->worksWell();
    }


    public function getPDO()
    {
        return $this->getConnection()->getPDO();
    }


    public function getPDOStatement()
    {
        return $this->getConnection()->getPDOStatement();
    }


    public function errorCode()
    {
        return $this->getConnection()->errorCode();
    }


    public function errorInfo()
    {
        return $this->getConnection()->errorInfo();
    }


    public function execute($statement, array $parameters = null, $replace_prefix = false)
    {
        return $this->getConnection()->execute($statement, $parameters, $replace_prefix);
    }


    public function executeRead($statement, array $parameters = null, $replace_prefix = false)
    {
        return $this->getConnection()->executeRead($statement, $parameters, $replace_prefix);
    }


    public function executeWrite($statement, array $parameters = null, $replace_prefix = false)
    {
        return $this->getConnection()->executeWrite($statement, $parameters, $replace_prefix);
    }


    public function lastInsertId()
    {
        return $this->getConnection()->getPDO()->lastInsertId();
    }
}
