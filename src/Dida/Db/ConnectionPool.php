<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files must retain the above copyright notice.
 */

namespace Dida\Db;

class ConnectionPool
{
    const VERSION = '20171122';

    const CONN_FOR_READ = 'CONN_FOR_READ';

    const CONN_FOR_WRITE = 'CONN_FOR_WRITE';

    protected $read_conn_pool = [];

    protected $write_conn_pool = [];


    public function addConnForRead($connection, $key = null)
    {
        if (is_null($key)) {
            $this->read_conn_pool[] = $connection;
        } else {
            $this->read_conn_pool[$key] = $connection;
        }
    }


    public function addConnForWrite($connection, $key = null)
    {
        if (is_null($key)) {
            $this->write_conn_pool[] = $connection;
        } else {
            $this->write_conn_pool[$key] = $connection;
        }
    }


    public function &getConnForRead()
    {
        if (empty($this->read_conn_pool)) {
            return null;
        }

        if (count($this->read_conn_pool) === 1) {
            return end($this->read_conn_pool);
        }

        $key = array_rand($this->read_conn_pool);
        return $this->read_conn_pool[$key];
    }


    public function getConnForWrite()
    {
        if (empty($this->write_conn_pool)) {
            return null;
        }

        if (count($this->write_conn_pool) === 1) {
            return end($this->write_conn_pool);
        }

        $key = array_rand($this->write_conn_pool);
        return $this->write_conn_pool[$key];
    }
}
