<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db;

use \PDO;
use \Exception;

class Connection
{
    const VERSION = '20171113';

    protected $db = null;

    protected $pdo = null;

    protected $pdoStatement = null;

    protected $cfg = [];


    public function __construct($cfg)
    {
        $this->cfg = [
            'db.driver'      => $cfg['db.driver'],
            'db.dsn'         => $cfg['db.dsn'],
            'db.username'    => $cfg['db.username'],
            'db.password'    => $cfg['db.password'],
            'db.options'     => $cfg['db.options'],
            'db.prefix'      => $cfg['db.prefix'],
            'db.swap_prefix' => $cfg['db.swap_prefix'],
        ];
    }


    public function connect()
    {
        if ($this->pdo !== null) {
            return true;
        }

        try {
            $this->pdo = new PDO(
                $this->cfg['db.dsn'], $this->cfg['db.username'], $this->cfg['db.password'], $this->cfg['db.options']
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    public function disconnect()
    {
        $this->pdo = null;
    }


    public function isConnected()
    {
        return ($this->pdo !== null);
    }


    public function worksWell()
    {
        if ($this->pdo === null) {
            return false;
        }

        try {
            if ($this->pdo->query('SELECT 1') === false) {
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }


    public function getPDO()
    {
        if ($this->connect()) {
            return $this->pdo;
        } else {
            return false;
        }
    }


    public function getPDOStatement()
    {
        return $this->pdoStatement;
    }


    public function errorCode()
    {
        if ($this->pdoStatement === null) {
            return null;
        } else {
            return $this->pdoStatement->errorCode();
        }
    }


    public function errorInfo()
    {
        if ($this->pdoStatement === null) {
            return null;
        } else {
            return $this->pdoStatement->errorInfo();
        }
    }


    public function execute($statement, array $parameters = null, $replace_prefix = false)
    {
        if ($replace_prefix) {
            $statement = $this->replacePrefix($statement);
        }

        try {
            $this->pdo = $this->getPDO();
            $this->pdoStatement = $this->pdo->prepare($statement);
            $result = $this->pdoStatement->execute($parameters);
            return $result;
        } catch (Exception $ex) {
            $this->pdoStatement = null;
            return false;
        }
    }


    public function executeRead($statement, array $parameters = null, $replace_prefix = false)
    {
        if ($replace_prefix) {
            $statement = $this->replacePrefix($statement);
        }

        $result = $this->execute($statement, $parameters);

        if ($result) {
            $dataset = new DataSet($this->pdoStatement);
            return $dataset;
        } else {
            return false;
        }
    }


    public function executeWrite($statement, array $parameters = null, $replace_prefix = false)
    {
        if ($replace_prefix) {
            $statement = $this->replacePrefix($statement);
        }

        $result = $this->execute($statement, $parameters);

        if ($result) {
            return $this->pdoStatement->rowCount();
        } else {
            return false;
        }
    }


    protected function replacePrefix($statement)
    {
        $prefix = $this->cfg['db.prefix'];
        $swap_prefix = $this->cfg['db.swap_prefix'];

        if ($swap_prefix) {
            return str_replace($swap_prefix, $prefix, $statement);
        } else {
            return $statement;
        }
    }
}
