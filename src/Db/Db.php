<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \PDO;
use \Exception;

/**
 * Db
 */
abstract class Db implements DbInterface
{
    /**
     * Version
     */
    const VERSION = '0.1.5';

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
        'dbtype'  => null, // Set the DB type to use, 'Mysql',

        /* optional parameters */
        'dbname'      => null, // the database name
        'charset'     => 'utf8', // set the default connection charset.
        'persistence' => false, // set if a persistence connection is persistence.
        'prefix'      => '', // default table prefix
        'swap_prefix' => '###_', // default table prefix string.
    ];

    /**
     * Returns the PDO instance.
     *
     * @var \PDO
     */
    public $pdo = null;

    /**
     * Specifies a work directory. Workdir must exist and be writable.
     *
     * @var string
     */
    public $workdir = null;

    /**
     * Specifies the DB type
     *
     * @var string
     */
    public $dbtype = null;


    /**
     * Class construct.
     */
    public function __construct(array $cfg = [])
    {
        $this->cfg = array_merge($this->cfg, $cfg);

        // Checks if the work directory is valid.
        $workdir = $this->cfg['workdir'];
        if (!is_string($workdir) || !file_exists($workdir) || !is_dir($workdir) || !is_writeable($workdir)) {
            throw new Exception('Invalid $cfg["workdir"]! "' . $workdir . '" does not exists or cannot be written');
        }
        $this->workdir = $this->cfg['workdir'] = realpath($workdir) . DIRECTORY_SEPARATOR;
    }


    /**
     * Destructs this class.
     */
    public function __destruct()
    {
        $this->pdo = null;
    }


    /**
     * Connects the database driver.
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
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * Checks if the connection is already established.
     */
    public function isConnected()
    {
        if ($this->pdo === null) {
            return false;
        }
    }


    /**
     * Checks if the connection works well.
     *
     * @return boolean
     */
    public function worksWell()
    {
        if ($this->pdo === null) {
            return false;
        }

        // Checks if a simple SQL could be executed successfully,
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


    /**
     * Disconnects the connection.
     */
    public function disconnect()
    {
        $this->pdo = null;
    }


    /**
     * Executes an SQL statement directly.
     *
     * @param string $statement
     * @param array $parameters
     *
     * @return Result
     */
    public function execute($statement, array $parameters = [])
    {
        $this->connect();

        try {
            $stmt = $this->pdo->prepare($statement);
            $success = $stmt->execute($parameters);
            return new Result($this, $stmt, $success);
        } catch (Exception $ex) {
            return false;
        }
    }


    /**
     * Returns a new SQL class instance with necessary parameters.
     *
     * @return Query
     *
     * @todo This method should be overwritted.
     */
    protected function newQuery()
    {
        // should be replace with MysqlBuilder() etc.
        $builder = new Builder();

        $sql = new Query([
            'prefix'      => $this->cfg['prefix'],
            'swap_prefix' => $this->cfg['swap_prefix'],
            ], $this, $builder);
        return $sql;
    }


    /**
     * Creates an SQL Statement <Query> object and sets it as the master table.
     *
     * @param string $table
     * @param string $alias
     * @param string $prefix
     *
     * @return \Dida\Db\Query
     */
    public function table($table, $alias = null, $prefix = null)
    {
        $sql = $this->newQuery();

        $sql->table($table, $alias, $prefix);

        return $sql;
    }
}
