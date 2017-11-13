<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db;

use \Exception;

abstract class SchemaInfo
{
    const COLUMN_TYPE_UNKNOWN = 'unknown';
    const COLUMN_TYPE_INT = 'int';
    const COLUMN_TYPE_FLOAT = 'float';
    const COLUMN_TYPE_STRING = 'string';
    const COLUMN_TYPE_BOOL = 'bool';
    const COLUMN_TYPE_TIME = 'time';
    const COLUMN_TYPE_ENUM = 'enum';
    const COLUMN_TYPE_SET = 'set';
    const COLUMN_TYPE_RESOURCE = 'res';
    const COLUMN_TYPE_STREAM = 'stream';

    protected $db = null;

    protected $schema = null;

    protected $prefix = '';

    public $info = [];


    public function __construct(&$db)
    {
        $this->db = $db;

        $cfg = $this->db->getConfig();

        if (!isset($cfg['db.name'])) {
            throw new Exception('db.name 未配置');
        }
        $this->schema = $cfg['db.name'];

        if (!isset($cfg['db.prefix']) || !is_string($cfg['db.prefix'])) {
            $this->prefix = '';
        } else {
            $this->prefix = $cfg['db.prefix'];
        }
    }


    abstract public function getTableList();


    abstract public function getTable($table);


    abstract public function getTableInfo($table);


    abstract public function getColumnInfoList($table);


    abstract public function getColumnList($table);


    abstract public function getColumnInfo($table, $column);


    abstract public function getPrimaryKey($table);


    abstract public function getPrimaryKeys($table);


    abstract public function getUniqueColumns($table);


    abstract public function getBaseType($datatype);
}
