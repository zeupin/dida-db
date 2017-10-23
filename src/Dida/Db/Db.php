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
        'db.dsn'      => null, // PDO DNS
        'db.username' => null, // The database username
        'db.password' => null, // The database password
        'db.options'  => [], // PDO driver options

        /* required parameters */
        'db.driver_type' => null, // Set the DB type to use, 'Mysql',

        /* optional parameters */
        'db.name'        => null, // the database name
        'db.charset'     => 'utf8', // set the default connection charset.
        'db.persistence' => false, // set if a persistence connection is persistence.
        'db.prefix'      => '', // default table prefix
        'db.swap_prefix' => '###_', // default table prefix string.
    ];

    /**
     * Returns the PDO instance.
     *
     * @var \PDO
     */
    public $pdo = null;

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
            $this->pdo = new PDO(
                $this->cfg['db.dsn'], $this->cfg['db.username'], $this->cfg['db.password'], $this->cfg['db.options']
            );
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
        return ($this->pdo !== null);
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
     * @return DataSet
     */
    public function execute($statement, array $parameters = [])
    {
        $this->connect();

        try {
            $stmt = $this->pdo->prepare($statement);
            $success = $stmt->execute($parameters);
            return new DataSet($this, $stmt, $success);
        } catch (Exception $ex) {
            return false;
        }
    }


    /**
     * Get a Builder instance.
     *
     * @param \Dida\Db\Builder $builder
     * @return $this
     */
    public function getBuilder()
    {
        return new Builder();
    }


    /**
     * Returns a new SQL class instance with necessary parameters.
     *
     * @return SqlQuery
     *
     * @todo This method should be overwritted.
     */
    protected function newQuery()
    {
        $builder = $this->getBuilder();

        $sql = new SqlQuery([
            'prefix'      => $this->cfg['db.prefix'],
            'swap_prefix' => $this->cfg['db.swap_prefix'],
            ], $this, $builder);
        return $sql;
    }


    /**
     * Creates an SQL Statement <SqlQuery> object and sets it as the master table.
     *
     * @param string $table
     * @param string $alias
     * @param string $prefix
     *
     * @return \Dida\Db\SqlQuery
     */
    public function table($table, $alias = null, $prefix = null)
    {
        $sql = $this->newQuery();

        $sql->table($table, $alias, $prefix);

        return $sql;
    }
}
