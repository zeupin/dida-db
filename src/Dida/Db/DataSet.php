<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db;

use \PDO;

class DataSet
{
    const VERSION = '20171113';

    public $pdoStatement = null;

    public $columnCount = null;

    public $columnMetas = null;


    public function __construct(\PDOStatement $pdoStatement = null)
    {
        $this->pdoStatement = $pdoStatement;

        $this->columnCount = $pdoStatement->columnCount();

        $this->columnMetas = [];
        for ($i = 0; $i < $this->columnCount; $i++) {
            $this->columnMetas[$i] = $pdoStatement->getColumnMeta($i);
        }
    }


    public function setFetchMode()
    {
        return call_user_func_array([$this->pdoStatement, 'setFetchMode'], func_get_args());
    }


    public function fetch()
    {
        return call_user_func_array([$this->pdoStatement, 'fetch'], func_get_args());
    }


    public function fetchAll()
    {
        return call_user_func_array([$this->pdoStatement, 'fetchAll'], func_get_args());
    }


    public function fetchColumn($column_number = 0)
    {
        return $this->pdoStatement->fetchColumn($column_number);
    }


    public function errorCode()
    {
        return $this->pdoStatement->errorCode();
    }


    public function errorInfo()
    {
        return $this->pdoStatement->errorInfo();
    }


    public function rowCount()
    {
        return $this->pdoStatement->rowCount();
    }


    public function columnCount()
    {
        return $this->pdoStatement->columnCount();
    }


    public function debugDumpParams()
    {
        return $this->pdoStatement->debugDumpParams();
    }


    public function getRow()
    {
        return $this->pdoStatement->fetch();
    }


    public function getRows()
    {
        $array = $this->pdoStatement->fetchAll();
        return $array;
    }


    public function getRowsAssocBy($colN)
    {
        $array = $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        if (is_array($colN)) {
            return Util::arrayAssocBy($array, $colN);
        } else {
            return Util::arrayAssocBy($array, func_get_args());
        }
    }


    public function getRowsGroupBy($colN)
    {
        $array = $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        if (is_array($colN)) {
            return Util::arrayGroupBy($array, $colN);
        } else {
            return Util::arrayGroupBy($array, func_get_args());
        }
    }


    public function getColumnNumber($column)
    {
        if (is_string($column)) {
            for ($i = 0; $i < $this->columnCount; $i++) {
                $column_meta = $this->columnMetas[$i];
                if ($column === $column_meta['name']) {
                    return $i;
                }
            }

            return false;
        }

        if (is_int($column)) {
            if (($column < 0) || ($column >= $this->columnCount)) {
                return false;
            }

            return $column;
        }

        return false;
    }


    public function getColumn($column, $key = null)
    {
        $colnum = $this->getColumnNumber($column);
        if ($colnum === false) {
            return false;
        }

        if (!is_null($key)) {
            $key = $this->getColumnNumber($key);
            if ($key === false) {
                return false;
            }
        }

        if (is_null($key)) {
            return $this->pdoStatement->fetchAll(PDO::FETCH_COLUMN, $colnum);
        } else {
            $array = $this->pdoStatement->fetchAll(PDO::FETCH_NUM);
            return array_column($input, $colnum, $key);
        }
    }


    public function getColumnDistinct($column)
    {
        $colnum = $this->getColumnNumber($column);
        if ($colnum === false) {
            return false;
        }

        return $this->pdoStatement->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE, $colnum);
    }


    public function getValue($column = 0, $returnType = null)
    {
        $colnum = $this->getColumnNumber($column);
        if ($colnum === false) {
            return false;
        }

        $v = $this->pdoStatement->fetchColumn($colnum);

        if (is_null($v)) {
            return null;
        }

        switch ($returnType) {
            case 'int':
                return (is_numeric($v)) ? intval($v) : false;
            case 'float':
                return (is_numeric($v)) ? floatval($v) : false;
            default:
                return $v;
        }
    }
}
