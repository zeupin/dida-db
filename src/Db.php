<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \PDO;
use \PDOException;

/**
 * Db
 */
class Db
{
    /* initial configurations */
    private $cfg = [
        /* required parameters */
        'dsn'      => null, // PDO DNS
        'username' => null, // The database username
        'password' => null, // The database password
        'options'  => [], // PDO driver options

        /* optional parameters */
        'dbname'      => null, // The database name
        'charset'     => null, // Set the connection charset
        'persistence' => false, // persistence connection
        'prefix'      => '', // default table prefix
    ];

    /* PDO instance */
    public $pdo = null;

    /* PDO Exception instance */
    public $pdoexception = null;


    /**
     * Construct
     *
     * @param array $cfg
     */
    public function __construct($cfg = [])
    {
        $this->cfg = array_merge($this->cfg, $cfg);
    }


    /**
     * Connects the database
     *
     * @return bool true if success, false if failed.
     */
    public function connect()
    {
        // If connection exists
        if ($this->pdo !== null) {
            return true;
        }

        // Try to make a connection
        try {
            $this->pdo = new PDO($this->cfg['dsn'], $this->cfg['username'], $this->cfg['password'], $this->cfg['options']);
            return true;
        } catch (PDOException $e) {
            $this->pdoexception = $e;
            return false;
        }
    }


    /**
     * Checks the connection
     *
     * @param bool $strict_mode Strict mode
     */
    public function isConnected($strict_mode = false)
    {
        if ($this->pdo === null) {
            return false;
        }

        if (!$strict_mode) {
            return true;
        }

        /* if strict mode is enabled */
        try {
            $result = $this->pdo->query('SELECT 1');
            if ($result === false) {
                $this->pdo = null;
                return false;
            } else {
                return true;
            }
        } catch (PDOException $e) {
            $this->pdo = null;
            $this->pdoexception = $e;
            return false;
        }
    }


    /**
     * Disconnect
     */
    public function disconnect()
    {
        $this->pdo = null;
    }
}
