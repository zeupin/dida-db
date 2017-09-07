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

/**
 * Mysql
 */
class Mysql extends Db implements SchemaInterface
{
    /* Mysql Schema Trait */
    use MysqlSchemaTrait;
}
