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
     * Default configurations.
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
        'dbname'      => null, // the database name
        'charset'     => 'utf8', // set the default connection charset.
        'persistence' => false, // set if a persistence connection is persistence.
        'prefix'      => '', // default table prefix
        'prefix_tpl'  => '###_', // default table prefix template placeholder.
        'prepared'    => true, // default
    ];

    /**
     * Returns the PDO instance.
     *
     * @var \PDO
     */
    public $pdo = null;

    /**
     * Returns the PDO Exception instance.
     *
     * @var \PDOException
     */
    public $pdoexception = null;

    /**
     * Specifies a work directory.
     *
     * @var string
     */
    public $workdir = null;

    /**
     * Returns if the connection is persistence.
     *
     * @var boolean
     */
    protected $persistence = false;

    /**
     * Returns the number of execute() affected rows.
     *
     * @var int
     */
    public $rowsAffected = null;


    /**
     * Constructs this class.
     */
    public function __construct(array $cfg = [])
    {
        // Checks if the work directory is valid.
        $workdir = $cfg['workdir'];
        if (!is_string($workdir) || !file_exists($workdir) || !is_dir($workdir) || !is_writeable($workdir)) {
            throw new Exception("You must specify the cfg['workdir'] to a valid writable directory");
        }
        $cfg['workdir'] = realpath($workdir) . DIRECTORY_SEPARATOR;
        $this->workdir = $this->cfg['workdir'];

        // $cfg['persistence']
        $cfg['persistence'] = ($cfg['persistence']) ? true : false;

        // Merges user $cfg into default $cfg.
        $this->cfg = array_merge($this->cfg, $cfg);
    }


    /**
     * Destructs this class.
     */
    public function __destruct()
    {
        $this->pdo = null;
        $this->pdoexception = null;
    }


    /**
     * Connects the specified database driver.
     *
     * @return boolean -- Returns TRUE on success or FALSE on failure.
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
     * Checks if the connection is already established.
     * If $strict_mode is true, further checks if the database connection works.
     *
     * @param boolean $strict_mode Strict mode
     */
    public function isConnected($strict_mode = false)
    {
        if ($this->pdo === null) {
            return false;
        }

        if (!$strict_mode) {
            return true;
        }
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
     * Disconnects the connection.
     */
    public function disconnect()
    {
        $this->pdo = null;
        $this->pdoexception = null;
    }


    /**
     * Executes an SQL statement that does not affect the data.
     *
     * @return \PDOStatement|FALSE -- Returns a result set as a PDOStatement object, or FALSE on failure.
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
     * Executes an SQL statement that might affect the data.
     * Returns TRUE on success, and puts $this->rowsAffected.
     * Returns FALSE on failure, and puts $this->rowsAffected null.
     *
     * @param string $sql
     * @param null|array $data
     *
     * @return boolean -- Returns TRUE on success or FALSE on failure.
     */
    public function execute($sql, $data = null)
    {
        if ($data === null) {
            $result = $this->pdo->exec($sql);

            if ($result === false) {
                $this->rowsAffected = null;
                return false;
            } else {
                $this->rowsAffected = $result;
                return true;
            }
        } elseif (is_array($data)) {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($data);

            if ($result === false) {
                $this->rowsAffected = null;
                return false;
            } else {
                $this->rowsAffected = $stmt->rowCount();
                return true;
            }
        } else {
            throw new Exception('Invalid parameter type "$data".');
        }
    }
}
