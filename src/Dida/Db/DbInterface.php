<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * Db Interface
 */
interface DbInterface
{
    /**
     * Class construct.
     */
    public function __construct(array $cfg = []);


    /**
     * Destructs this class.
     */
    public function __destruct();


    /**
     * Connects the database driver.
     *
     * @return boolean -- Returns TRUE on success or FALSE on failure.
     */
    public function connect();


    /**
     * Checks if the connection is already established.
     */
    public function isConnected();


    /**
     * Checks if the connection is already established and works well.
     *
     * @return boolean
     */
    public function worksWell();


    /**
     * Disconnects the connection.
     */
    public function disconnect();


    /**
     * Executes an SQL statement directly.
     *
     * @param string $statement
     * @param array $parameters
     *
     * @return DataSet
     */
    public function execute($statement, array $parameters = []);


    /**
     * Creates a <Query> object and sets it as the master table.
     *
     * @param string $table
     * @param string $alias
     * @param string $prefix
     *
     * @return \Dida\Db\Query
     */
    public function table($table, $alias = null, $prefix = null);
}
