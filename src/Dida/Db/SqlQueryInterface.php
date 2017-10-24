<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * SqlQuery Interface
 */
interface SqlQueryInterface
{
    /**
     * Constructor.
     *
     * @param array $options
     * @param \Dida\Db\Db $db
     */
    public function __construct(&$db);


    /**
     * Enable Pull-Execution feature.
     *
     * @return $this
     */
    public function setPullExec($flag = true);


    /**
     * Clears the $built flag, and prepares to build() again.
     *
     * @return $this
     */
    public function changed();


    /**
     * Resets all tasklist data.
     *
     * @return $this
     */
    public function resetAll();


    /**
     * Resets the COUNT(...) relative data.
     * See count()
     *
     * @return $this
     */
    public function resetCount();


    /**
     * Changes the master table.
     *
     * @param string $name
     * @param string $alias
     * @param string $prefix
     *
     * @return $this
     */
    public function table($name, $alias = null, $prefix = null);


    /**
     * SELECT COUNT(...)
     *
     * @param array $columns
     * @param string $alias
     *
     * @return $this
     */
    public function count(array $columns = null, $alias = null);


    /**
     * SELECT
     *
     * @return $this
     */
    public function select(array $arrayColumnAsAlias = []);


    /**
     * DELETE
     *
     * @return $this
     */
    public function delete();


    /**
     * INSERT
     *
     * @return $this
     */
    public function insert(array $record);


    /**
     * UPDATE
     *
     * @return $this
     */
    public function update();


    /**
     * TRUNCATE
     *
     * @return $this
     */
    public function truncate();


    /**
     * Builds the statement.
     *
     * @return $this
     */
    public function build();


    /**
     * Executes the SQL statement built and returns a DataSet object.
     *
     * @param string $sql
     * @param array $sql_parameters
     *
     * @return DataSet
     */
    public function execute();
}
