<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db\SchemaInfo;

use \Exception;

abstract class File extends \Dida\Db\SchemaInfo
{
    const VERSION = '20171113';

    protected $cacheDir = null;


    abstract public function cacheTable($table);


    abstract public function cacheAllTables();


    public function __construct(&$db)
    {
        parent::__construct($db);

        $cfg = $this->db->getConfig();

        if (!isset($cfg['db.schemainfo.cachedir'])) {
            throw new Exception('db.schemainfo.cachedir 未配置');
        }
        if (!$this->setCacheDir($cfg['db.schemainfo.cachedir'])) {
            throw new Exception('db.schemainfo.cachedir 配置有误');
        }
    }


    public function getTableList()
    {
        $path = $this->cacheDir . DIRECTORY_SEPARATOR . '.tablelist.php';
        if (!file_exists($path)) {
            return false;
        }

        $content = include($path);
        return $content;
    }


    public function &getTable($table)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        return $this->info[$table];
    }


    public function getTableInfo($table)
    {
        $path = $this->cacheDir . DIRECTORY_SEPARATOR . $table . '.table.php';
        if (!file_exists($path)) {
            return false;
        }

        $content = include($path);
        return $content;
    }


    public function getColumnInfoList($table)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        return $this->info[$table]['columns'];
    }


    public function getColumnList($table)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        return $this->info[$table]['columnlist'];
    }


    public function getColumnInfo($table, $column)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        return $this->info[$table]['columns'][$column];
    }


    public function getPrimaryKey($table)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        return $this->info[$table]['pri'];
    }


    public function getPrimaryKeys($table)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        return $this->info[$table]['pris'];
    }


    public function getUniqueColumns($table)
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        return $this->info[$table]['unis'];
    }


    public function tableExists($table)
    {
        if (array_key_exists($table, $this->info)) {
            return true;
        } else {
            return $this->loadTableFromCache($table);
        }
    }


    protected function loadTableFromCache($table)
    {
        $DS = DIRECTORY_SEPARATOR;
        $path = "{$this->cacheDir}{$DS}{$table}.php";

        if (!file_exists($path) || !is_file($path)) {
            return false;
        }

        $data = include($path);
        $this->info[$table] = $data;
        return true;
    }


    protected function setCacheDir($cacheDir)
    {
        if (!is_string($cacheDir)) {
            $this->cacheDir = null;
            return false;
        }

        if (!file_exists($cacheDir)) {
            $result = mkdir($cacheDir, 0777, true);
            if ($result === true) {
                $this->cacheDir = realpath($cacheDir);
                return true;
            } else {
                $this->cacheDir = null;
                return false;
            }
        }

        if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
            $this->cacheDir = null;
            return false;
        }

        $this->cacheDir = realpath($cacheDir);
        return true;
    }


    protected function clearDir($dir)
    {
        if (!is_string($dir) || !file_exists($dir) || !is_dir($dir)) {
            return false;
        }

        $dir = realpath($dir);
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $file;

            try {
                if (is_dir($path)) {
                    $this->clearDir($path);
                } else {
                    unlink($path);
                }
            } catch (Exception $ex) {
                return false;
            }
        }

        return true;
    }
}
