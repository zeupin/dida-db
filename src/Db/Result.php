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
     * SQL statement verb.
     *
     * @var string
     */
    public $verb = null;

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
     * Set $db, $pdoStatement, $success.
     *
     * @param \Dida\Db\Db $db
     * @param string $verb
     * @param \PDOStatement $pdoStatement
     * @param boolean $success
     *
     * @return $this
     */
    public function set(\Dida\Db\Db &$db, $verb, \PDOStatement $pdoStatement = null, $success = true)
    {
        $this->db = $db;
        $this->verb = $verb;
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
                return call_user_func_array([$this->pdoStatement, 'setFetchMode'], func_get_args());
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
        return $this->pdoStatement->fetch();
    }


    /**
     * Returns an array containing all of the result set rows.
     *
     * @return array|false
     */
    public function fetchAll()
    {
        return $this->pdoStatement->fetchAll();
    }
}
