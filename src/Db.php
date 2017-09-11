<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida;

use \PDO;
use \PDOException;
use \Exception;

/**
 * Db
 */
class Db
{
    /**
     * Initial configurations
     *
     * @var array
     */
    protected $cfg = [
        /* pdo parameters */
        'dsn'      => null, // PDO DNS
        'username' => null, // The database username
        'password' => null, // The database password
        'options'  => [], // PDO driver options

        /* required parameters */
        'workdir' => null, // Set the work directory.

        /* optional parameters */
        'dbname'      => null, // The database name
        'charset'     => null, // Set the connection charset
        'persistence' => false, // persistence connection
        'prefix'      => '', // default table prefix
    ];

    /**
     * PDO instance
     *
     * @var \PDO
     */
    public $pdo = null;

    /**
     * PDO Exception instance
     *
     * @var \PDOException
     */
    public $pdoexception = null;

    /**
     * work directory
     * @var string
     */
    public $workdir = null;


    /**
     * Construct
     *
     * @param array $cfg
     */
    public function __construct($cfg = [])
    {
        $this->cfg = array_merge($this->cfg, $cfg);

        // Work directory.
        $workdir = $this->cfg['workdir'];
        if (!is_string($workdir) || !file_exists($workdir) || !is_dir($workdir) || !is_writeable($workdir)) {
            throw new Exception("You must specify the cfg['workdir'] to a valid writable directory");
        }
        $this->cfg['workdir'] = realpath($workdir) . DIRECTORY_SEPARATOR;
        $this->workdir = $this->cfg['workdir'];
    }


    /**
     * Connects the database
     *
     * @return bool TRUE on success.
     *               FALSE on failure.
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
     * Check the connection.
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


    /**
     * Query a SQL.
     * The operation will not change the data.
     */
    public function query($sql, $data = null)
    {
        if ($data === null) {
            return $this->pdo->query($sql);
        } elseif (is_array($data)) {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($data);
        } else {
            return false;
        }
    }


    /**
     * Execute a SQL.
     * The operation might change the data.
     */
    public function execute($sql, $data = null)
    {
        if ($data === null) {
            return $this->pdo->exec($sql);
        } elseif (is_array($data)) {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($data);
        } else {
            return false;
        }
    }
}
