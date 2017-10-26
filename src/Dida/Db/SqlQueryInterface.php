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
     * 设置要操作的数据表
     * 表名和别名用as或AS分隔，如：“products AS p”
     * 也可设置多个表，各个表之间以逗号分隔，如：“products AS p, orders AS o, users AS u”
     *
     * @param string $name_as_alias
     * @param string $prefix 如果不设置，则认为是$cfg["prefix"]的值。
     *
     * @return $this
     */
    public function table($name_as_alias, $prefix = null);


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
