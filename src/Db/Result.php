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
     *
     * @var \Dida\Db\Db
     */
    protected $db = null;

    /**
     * PDOStatement instance.
     *
     * @var \PDOStatement
     */
    protected $pdoStatement = null;

    /**
     * Statement execution result.
     *
     * @var boolean
     */
    protected $success = false;

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
     * Class construct
     *
     * @param \Dida\Db\Db $db
     * @param \PDOStatement $pdoStatement
     * @param boolean $success
     */
    public function __construct(\Dida\Db\Db &$db, \PDOStatement $pdoStatement = null, $success = true)
    {
        $this->db = $db;
        $this->pdoStatement = $pdoStatement;
        $this->success = $success;
    }


    /**
     * Class destruct
     */
    public function __destruct()
    {
        $this->pdoStatement = null;
    }


    /**
     * Set $pdoStatement.
     *
     * @param \PDOStatement $pdoStatement
     */
    public function setStatement(\PDOStatement $pdoStatement, $success = true)
    {
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
    public function setFetchMode($mode, $arg1 = null, array $arg2 = null)
    {
        if (!is_null($arg2)) {
            return call_user_func_array([$this->pdoStatement, 'setFetchMode'], [$mode, $arg1, $arg2]);
        }

        if (!is_null($arg1)) {
            return call_user_func_array([$this->pdoStatement, 'setFetchMode'], [$mode, $arg1]);
        }

        return call_user_func_array([$this->pdoStatement, 'setFetchMode'], [$mode]);
    }
}
