<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Mysql;

use \PDO;
use \Dida\Db;
use \Dida\Db\SchemaInterface;
use \Exception;
use \Dida\Db\Mysql\MysqlBuilder;

/**
 * Mysql
 */
class Mysql extends Db implements SchemaInterface
{
    use MysqlSchemaTrait;


    public function table($table, $with_prefix = true)
    {
        // Gets the real table name
        $realtable = ($with_prefix) ? $this->cfg['prefix'] . $table : $table;

        // get the table information
        $table_info = $this->workdir . '~SCHEMA' . DIRECTORY_SEPARATOR . $realtable . '.php';
        if (!file_exists($table_info)) {
            return false;
        }

        $t = new MysqlBuilder($this, $realtable);
        return $t;
    }
}
