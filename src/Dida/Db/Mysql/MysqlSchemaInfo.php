<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db\Mysql;

use \Dida\Db\SchemaInfo;

class MysqlSchemaInfo extends \Dida\Db\SchemaInfo\File
{
    const VERSION = '20171113';

    use MysqlSchemaInfoTrait;


    public function getBaseType($datatype)
    {
        switch ($datatype) {
            case 'varchar':
            case 'char':
            case 'text':
            case 'mediumtext':
            case 'longtext':
                return SchemaInfo::COLUMN_TYPE_STRING;

            case 'int':
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'bigint':
            case 'timestamp':
                return SchemaInfo::COLUMN_TYPE_INT;

            case 'float':
            case 'double':
            case 'decimal':
                return SchemaInfo::COLUMN_TYPE_FLOAT;

            case 'datetime':
            case 'date':
                return SchemaInfo::COLUMN_TYPE_TIME;

            case 'enum':
                return SchemaInfo::COLUMN_TYPE_ENUM;

            case 'set':
                return SchemaInfo::COLUMN_TYPE_SET;

            case 'varbinary':
                return SchemaInfo::COLUMN_TYPE_STREAM;

            default:
                return SchemaInfo::COLUMN_TYPE_UNKNOWN;
        }
    }


    public function cacheTable($table)
    {
        $tables = $this->queryTableInfo($table);
        $this->processTables($tables);

        $columns = $this->queryColumnInfo($table);
        $this->processColumns($columns);
    }


    public function cacheAllTables()
    {
        $this->clearDir($this->cacheDir);

        $tables = $this->queryAllTableInfo();
        $this->processTables($tables);

        $columns = $this->queryAllColumnInfo();
        $this->processColumns($columns);
    }


    protected function processTables(array $tables)
    {
        foreach ($tables as $table) {
            $path = $this->cacheDir . DIRECTORY_SEPARATOR . $table['TABLE_NAME'] . '.table.php';
            $content = "<?php\nreturn " . var_export($table, true) . ";\n";
            file_put_contents($path, $content);
        }
    }


    protected function processColumns(array $columns)
    {
        $info = [];

        foreach ($columns as $column) {
            $brief = $column;
            unset($brief['TABLE_NAME'], $brief['COLUMN_NAME']);
            $info[$column['TABLE_NAME']][$column['COLUMN_NAME']] = $brief;
        }

        foreach ($info as $table => $data) {
            $path = $this->cacheDir . DIRECTORY_SEPARATOR . $table . '.columns.php';
            $content = "<?php\nreturn " . var_export($data, true) . ";\n";
            file_put_contents($path, $content);
        }

        foreach ($info as $table => $data) {
            $path = $this->cacheDir . DIRECTORY_SEPARATOR . $table . '.php';

            $pri = null;
            $pris = [];
            $unis = [];
            $columnlist = [];
            $columns = [];

            foreach ($data as $col => $metas) {
                $columnlist[] = $col;

                if ($metas['COLUMN_KEY'] === 'PRI') {
                    $pris[] = $col;
                } elseif ($metas['COLUMN_KEY'] === 'UNI') {
                    $unis[] = $col;
                }

                $column = [
                    'datatype'  => $this->getBaseType($metas['DATA_TYPE']),
                    'nullable'  => ($metas['IS_NULLABLE'] === 'YES'),
                    'precision' => $metas['NUMERIC_PRECISION'],
                    'scale'     => $metas['NUMERIC_SCALE'],
                    'len'       => $metas['CHARACTER_MAXIMUM_LENGTH'],
                    'charset'   => $metas['CHARACTER_SET_NAME'],
                ];
                $columns[$col] = $column;
            }

            switch (count($pris)) {
                case 0:
                    $pri = null;
                    $pris = null;
                    break;
                case 1:
                    $pri = $pris[0];
                    $pris = null;
                    break;
                default:
                    $pri = null;
            }

            if (empty($unis)) {
                $unis = null;
            }

            $output = [
                'pri'        => $pri,
                'pris'       => $pris,
                'unis'       => $unis,
                'columnlist' => $columnlist,
                'columns'    => $columns,
            ];

            $content = "<?php\nreturn " . var_export($output, true) . ";\n";
            file_put_contents($path, $content);
        }
    }
}
