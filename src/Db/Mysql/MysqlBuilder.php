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
    protected function quoteTable($table)
    {
        return '`' . $table . '`';
    }


    protected function quoteField($field)
    {
        return '`' . $field . '`';
    }
}
