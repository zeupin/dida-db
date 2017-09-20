<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Mysql;

use \Dida\Db\Builder;
use \PDO;

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
        return $this->db->pdo->quote($value, PDO::PARAM_STR);
    }


    protected function quoteTime($value)
    {
        return '"' . $value . '"';
    }
}
