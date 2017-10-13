<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * DataSet Interface
 */
interface DataSetInterface
{
    /**
     * Class construct.
     *
     * @param \Dida\Db\Db $db
     * @param \PDOStatement $pdoStatement
     * @param boolean $success
     */
    public function __construct(&$db, \PDOStatement $pdoStatement = null, $success = true);


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
    public function setFetchMode();


    /**
     * Fetches the next row from a result set.
     *
     * @return mixed|false
     */
    public function fetch();


    /**
     * Returns an array containing all of the result set rows.
     *
     * @return array|false
     */
    public function fetchAll();


    /**
     * Represents PDOStatement::errorCode()
     *
     * @return string
     */
    public function errorCode();


    /**
     * Represents PDOStatement::errorInfo()
     *
     * @return array
     */
    public function errorInfo();


    /**
     * Represents PDO::lastInsertId()
     *
     * @param string $name
     */
    public function lastInsertId($name = null);


    /**
     * Represents PDOStatement::rowCount()
     */
    public function rowCount();


    /**
     * Represents PDOStatement::columnCount()
     */
    public function columnCount();


    /**
     * Represents PDOStatement::debugDumpParams()
     */
    public function debugDumpParams();
}
