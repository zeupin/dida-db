<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \PDO;
use \PDOException;
use \Exception;

/**
 * Db
 */
abstract class Db
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
        'vprefix'     => '###_', // default table prefix string.

        /* driver relative */
        'table_quote_prefix'   => '',
        'table_quote_postfix'  => '',
        'column_quote_prefix'  => '',
        'column_quote_postfix' => '',
    ];

    /**
     * Returns the PDO instance.
     *
     * @var \PDO
     */
    public $pdo = null;

    /**
     * Specifies a work directory.
     *
     * @var string
     */
    public $workdir = null;


    /**
     * Constructs this class.
     */
    public function __construct(array $cfg = [])
    {
        $this->cfg = array_merge($this->cfg, $cfg);

        // Checks if the work directory is valid.
        $workdir = $this->cfg['workdir'];
        if (!is_string($workdir) || !file_exists($workdir) || !is_dir($workdir) || !is_writeable($workdir)) {
            throw new Exception('$cfg["workdir"] "' . $workdir . '" does not exists or cannot be written');
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
        } catch (PDOException $e) {
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
     * @param string $sql
     * @param array $sql_parameters
     *
     * @return mixed
     */
    public function execute($sql, $sql_parameters = [])
    {
        $this->connect();
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($sql_parameters);
        } catch (Exception $ex) {
            return false;
        }
    }


    /**
     * @return Statement
     */
    protected function newSQL()
    {
        $sql = new SQL($this, [
            'prefix'               => $this->cfg['prefix'],
            'vprefix'              => $this->cfg['vprefix'],
            'table_quote_prefix'   => $this->cfg['table_quote_prefix'],
            'table_quote_postfix'  => $this->cfg['table_quote_postfix'],
            'column_quote_prefix'  => $this->cfg['column_quote_prefix'],
            'column_quote_postfix' => $this->cfg['column_quote_postfix'],
        ]);
        return $sql;
    }


    /**
     * Creates a Statement object and sets statement and parameters directly.
     *
     * @param string $statement
     * @param array $parameters
     *
     * @return \Dida\Db\SQL
     */
    public function sql($statement, $parameters = [])
    {
        $sql = $this->newSQL();

        $sql->statement = $statement;
        $sql->parameters = $parameters;
        $sql->built = true;

        return $sql;
    }


    /**
     * Creates a Statement object and sets the master table.
     *
     * @param string $table
     * @param string $alias
     * @param string $prefix
     *
     * @return \Dida\Db\SQL
     */
    public function table($table, $alias = null, $prefix = null)
    {
        $sql = $this->newSQL();

        $sql->table($table, $alias, $prefix);

        return $sql;
    }
}
