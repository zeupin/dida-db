<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * SqlExecutionInterface 接口
 */
interface SqlExecutionInterface
{
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
     * SELECT COUNT(...)
     *
     * @param array $columns
     * @param string $alias
     *
     * @return $this
     */
    public function count(array $columns = null, $alias = null);


    /**
     * TRUNCATE
     *
     * @return $this
     */
    public function truncate();
}
