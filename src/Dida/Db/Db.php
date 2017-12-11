<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db;

class Db
{
    const VERSION = '20171127';

    protected $connection = null;

    protected $schemainfo = null;

    protected $builder = null;

    protected $cfg = [
        'db.name'   => null,
        'db.driver' => null,

        'db.dsn'      => null,
        'db.username' => null,
        'db.password' => null,
        'db.options'  => [],

        'db.charset'     => 'utf8',
        'db.persistence' => false,
        'db.prefix'      => '',
        'db.swap_prefix' => '###_',
    ];


    use Traits\DbTrait;


    public function __construct(array $cfg)
    {
        $this->setConfig($cfg);
    }


    protected function setConfig(array &$cfg)
    {
        foreach ($cfg as $key => $value) {
            if (substr($key, 0, 3) === 'db.') {
                $this->cfg[$key] = $value;
            }
        }

        if (!array_key_exists('db.options', $this->cfg)) {
            $this->cfg['db.options'] = [];
        }
        if (!array_key_exists(\PDO::ATTR_DEFAULT_FETCH_MODE, $this->cfg['db.options'])) {
            $this->cfg['db.options'][\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;
        }

        return $this;
    }


    public function getConfig()
    {
        return $this->cfg;
    }


    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }


    public function &getConnection()
    {
        return $this->connection;
    }


    public function setBuilder($builder)
    {
        $this->builder = $builder;

        return $this;
    }


    public function &getBuilder()
    {
        return $this->builder;
    }


    public function setSchemaInfo($schemainfo)
    {
        $this->schemainfo = $schemainfo;

        return $this;
    }


    public function &getSchemaInfo()
    {
        return $this->schemainfo;
    }


    protected function newQuery()
    {
        $query = new Query($this);

        return $query;
    }


    public function table($table, $prefix = null)
    {
        $query = $this->newQuery();

        $query->table($table, $prefix);

        return $query;
    }
}
