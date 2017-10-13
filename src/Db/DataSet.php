<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \PDO;
use \PDOStatement;
use \Exception;

/**
 * DataSet
 */
class DataSet implements DataSetInterface
{
    /**
     * Version
     */
    const VERSION = '0.1.5';

    /**
     * Reference of a \Dida\Db\Db instance.
     *
     * @var \Dida\Db\Db
     */
    protected $db = null;

    /**
     * PDOStatement instance.
     *
     * @var \PDOStatement
     */
    public $pdoStatement = null;

    /**
     * Statement execution result.
     *
     * @var boolean
     */
    public $success = false;

    /**
     * Statement String.
     *
     * @var string
     */
    public $statement = '';

    /**
     * Statement parameters.
     *
     * @var array
     */
    public $parameters = [];


    /**
     * Class construct.
     *
     * @param \Dida\Db\Db $db
     * @param \PDOStatement $pdoStatement
     * @param boolean $success
     */
    public function __construct(&$db, \PDOStatement $pdoStatement = null, $success = true)
    {
        $this->db = $db;
        $this->pdoStatement = $pdoStatement;
        $this->success = $success;
    }


    /**
     * Call PDOStatement::setFetchMode()
     *
     * bool PDOStatement::setFetchMode ( int $mode )
     * bool PDOStatement::setFetchMode ( int $PDO::FETCH_COLUMN , int $colno )
     * bool PDOStatement::setFetchMode ( int $PDO::FETCH_CLASS , string $classname , array $ctorargs )
     * bool PDOStatement::setFetchMode ( int $PDO::FETCH_INTO , object $object )
     *
     * @param int $mode
     * @param int|string|object $arg1
     * @param array $arg2
     */
    public function setFetchMode()
    {
        switch (func_num_args()) {
            case 1:
            case 2:
            case 3:
                call_user_func_array([&$this->pdoStatement, 'setFetchMode'], func_get_args());
                return $this;
            default:
                throw new Exception('Invalid argument number. See PDOStatement::setFetchMode()');
        }
    }


    /**
     * Fetches the next row from a result set.
     *
     * @return mixed|false
     */
    public function fetch()
    {
        if (!$this->success) {
            return false;
        }

        return $this->pdoStatement->fetch();
    }


    /**
     * Returns an array containing all of the result set rows.
     *
     * @return array|false
     */
    public function fetchAll()
    {
        if (!$this->success) {
            return false;
        }

        return $this->pdoStatement->fetchAll();
    }


    /**
     * Returns the specified column value of the next row.
     *
     * @param int $column_number
     */
    public function fetchColumn($column_number = 0)
    {
        if (!$this->success) {
            return false;
        }

        return $this->pdoStatement->fetchColumn($column_number);
    }


    /**
     * Represents PDOStatement::errorCode()
     *
     * @return string
     */
    public function errorCode()
    {
        return $this->pdoStatement->errorCode();
    }


    /**
     * Represents PDOStatement::errorInfo()
     *
     * @return array
     */
    public function errorInfo()
    {
        return $this->pdoStatement->errorInfo();
    }


    /**
     * Represents PDO::lastInsertId()
     * Notice! The type returned is a string!
     *
     * @param string $name
     */
    public function lastInsertId($name = null)
    {
        return $this->db->pdo->lastInsertId($name);
    }


    /**
     * Represents PDOStatement::rowCount()
     */
    public function rowCount()
    {
        return $this->pdoStatement->rowCount();
    }


    /**
     * Represents PDOStatement::columnCount()
     */
    public function columnCount()
    {
        return $this->pdoStatement->columnCount();
    }


    /**
     * Represents PDOStatement::debugDumpParams()
     */
    public function debugDumpParams()
    {
        return $this->pdoStatement->debugDumpParams();
    }


    /**
     * Alias of fetch()
     *
     * @return array
     */
    public function getRecord()
    {
        if (!$this->success) {
            return false;
        }

        return $this->pdoStatement->fetch();
    }


    /**
     * Alias of fetchAll()
     *
     * @return array(array)
     */
    public function getRecords()
    {
        if (!$this->success) {
            return false;
        }

        return $this->pdoStatement->fetchAll();
    }


    /**
     * Returns all rows of the specified field.
     *
     * @param int $column
     * @return array
     */
    public function getFieldRows($column_number)
    {
        if (!$this->success) {
            return false;
        }

        return $this->pdoStatement->fetchAll(PDO::FETCH_COLUMN, $column_number);
    }
}
