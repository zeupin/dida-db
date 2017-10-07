<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * Statement
 */
class Result
{
    /**
     * Reference of a \Dida\Db\Db instance.
     *
     * @var \Dida\Db\Db
     */
    public $db = null;

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


    public function __construct(\Dida\Db\Db &$db, \PDOStatement $pdoStatement = null, $success = true)
    {
        $this->set($db, $pdoStatement, $success);
    }


    /**
     * Set $db, $pdoStatement, $success.
     *
     * @param \Dida\Db\Db $db
     * @param string $verb
     * @param \PDOStatement $pdoStatement
     * @param boolean $success
     *
     * @return $this
     */
    public function set(\Dida\Db\Db &$db, \PDOStatement $pdoStatement = null, $success = true)
    {
        $this->db = $db;
        $this->pdoStatement = $pdoStatement;
        $this->success = $success;

        return $this;
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
            default :
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
        if ($this->success) {
            return $this->pdoStatement->fetch();
        } else {
            return false;
        }
    }


    /**
     * Returns an array containing all of the result set rows.
     *
     * @return array|false
     */
    public function fetchAll()
    {
        if ($this->success) {
            return $this->pdoStatement->fetchAll();
        } else {
            return false;
        }
    }
}
