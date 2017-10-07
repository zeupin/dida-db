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
        $this->set($db, $pdoStatement, $success);
    }


    /**
     * Class destruct
     */
    public function __destruct()
    {
        $this->pdoStatement = null;
    }


    /**
     * Set $db, $pdoStatement, $success.
     *
     * @param \PDOStatement $pdoStatement
     */
    public function set(\Dida\Db\Db &$db, \PDOStatement $pdoStatement = null, $success = true)
    {
        $this->db = $db;
        $this->pdoStatement = $pdoStatement;
        $this->success = $success;

        return $this;
    }


    /**
     * Set $db.
     *
     * @param \Dida\Db\Db $db
     * @return $this
     */
    public function setDb(\Dida\Db\Db &$db)
    {
        $this->db = $db;

        return $this;
    }


    /**
     * Set $pdoStatement.
     *
     * @param \PDOStatement $pdoStatement
     * @return $this
     */
    public function setPDOStatement(\PDOStatement $pdoStatement)
    {
        $this->pdoStatement = $pdoStatement;

        return $this;
    }


    /**
     * Set $success.
     *
     * @param boolean $success
     * @return $this
     */
    public function setSuccess($success)
    {
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
                throw new Exception('Invalid arguments number. See PDOStatement::setFetchMode()');
        }
    }
}
