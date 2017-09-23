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


    public function __construct(array $cfg = array())
    {
        parent::__construct($cfg);

        /* charset option */
        if (is_string($this->cfg['charset'])) {
            switch (strtolower($this->cfg['charset'])) {
                case 'utf8':
                case 'utf-8':
                case 'utf_8':
                    $this->cfg['options'][PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
                    break;
                default:
                    $this->cfg['options'][PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $this->cfg['charset'];
            }
        } else {
            unset($this->cfg['options'][PDO::MYSQL_ATTR_INIT_COMMAND]);
        }

        /* persistence option */
        if ($this->cfg['persistence']) {
            $this->cfg['options'][PDO::ATTR_PERSISTENT] = true;
        } else {
            unset($this->cfg['options'][PDO::ATTR_PERSISTENT]);
        }
    }


    public function table($table, $prefix = null, $formal_prefix = null)
    {
        if ($prefix === null) {
            $prefix = $this->cfg['prefix'];
        }
        if ($formal_prefix === null) {
            $formal_prefix = $this->cfg['formal_prefix'];
        }

        $t = new MysqlBuilder($this, $table, $prefix, $formal_prefix);
        return $t;
    }
}
