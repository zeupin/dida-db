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
     * 初始化任务列表
     *
     * @return $this
     */
    public function init();


    /**
     * 设置默认操作的数据表
     *
     * @param string $name
     * @param string $alias
     * @param string $prefix
     *
     * @return $this
     */
    public function table($name, $alias = null, $prefix = null);


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
