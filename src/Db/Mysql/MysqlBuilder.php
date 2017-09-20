<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Mysql;

use \Dida\Db\Builder;

/**
 * MysqlBuilder
 */
class MysqlBuilder extends Builder
{
    protected function quoteTableName($table)
    {
        return '`' . $table . '`';
    }


    protected function quoteColumnName($column)
    {
        return '`' . $column . '`';
    }


    protected function quoteString($value)
    {
        return $this->db->pdo->quote($value);
    }


    protected function quoteTime($value)
    {
        return '"' . $value . '"';
    }
}
