<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db\Mysql;

class MysqlDb extends \Dida\Db\Db
{
    const VERSION = '20171113';

    public function __construct(array $cfg = array())
    {
        parent::__construct($cfg);

        $this->cfg['db.driver'] = 'Mysql';

        $conn = new \Dida\Db\Connection($this->getConfig());
        $this->connection = &$conn;

        $schemainfo = new MysqlSchemaInfo($this);
        $this->schemainfo = &$schemainfo;

        $builder = new \Dida\Db\Builder($this);
        $this->builder = &$builder;
    }
}
