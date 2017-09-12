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


    public function table($table, $prefix = null)
    {
        // Gets the real table name
        if (is_null($prefix)) {
            $prefix = $this->cfg['prefix'];
        }
        $realtable =  $prefix . $table;

        // the table defination file exists?
        $table_def_file = $this->workdir . '~SCHEMA' . DIRECTORY_SEPARATOR . $realtable . '.php';
        if (!file_exists($table_def_file)) {
            return false;
        }

        $t = new MysqlBuilder($this, $table, $prefix);
        return $t;
    }
}
