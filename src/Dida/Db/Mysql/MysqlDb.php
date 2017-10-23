<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Mysql;

/**
 * MysqlDb
 */
class MysqlDb extends \Dida\Db\Db
{
    /**
     * Class construct.
     *
     * @param array $cfg
     */
    public function __construct(array $cfg = array())
    {
        parent::__construct($cfg);

        // Set the dbtype
        $this->dbtype = 'Mysql';

        // 配置Builder，使用标准的Builder
        $this->builder = new \Dida\Db\Builder($this);

        // 配置SchemaInfo，使用MysqlSchemaInfo
        $this->schemaInfo = new MysqlSchemaInfo($this);
    }
}
