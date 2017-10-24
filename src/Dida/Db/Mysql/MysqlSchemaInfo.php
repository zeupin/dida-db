<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Mysql;

use \PDO;
use \Exception;

/**
 * MysqlSchemaInfo
 */
class MysqlSchemaInfo extends \Dida\Db\SchemaInfo
{
    /**
     * 列出指定数据库中的所有数据表的表名.
     */
    public function listTableNames($prefix = null, $schema = null)
    {
        if ($prefix === null) {
            $prefix = $this->prefix;
        }
        if ($schema === null) {
            $schema = $this->schema;
        }

        $sql = <<<'EOT'
SELECT
    `TABLE_NAME`
FROM
    `information_schema`.`TABLES`
WHERE
    (`TABLE_SCHEMA` LIKE :schema) AND (`TABLE_NAME` LIKE :table)
ORDER BY
    `TABLE_SCHEMA`, `TABLE_NAME`
EOT;
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table'  => $prefix . '%',
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $result;
    }


    /**
     * 获取<schema.table>的表信息
     */
    public function getTableInfo($table, $schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        $sql = <<<'EOT'
SELECT
    `TABLE_SCHEMA`,
    `TABLE_NAME`,
    `TABLE_TYPE`,
    `TABLE_CATALOG`,
    `ENGINE`,
    `TABLE_COLLATION`,
    `TABLE_COMMENT`
FROM
    information_schema.TABLES
WHERE
    (`TABLE_SCHEMA` LIKE :schema) AND (`TABLE_NAME` LIKE :table)
EOT;
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table'  => $table,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }


    /**
     * 获取<schema.table>所有列的信息
     */
    public function getAllColumnInfo($table, $schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        $sql = <<<'EOT'
SELECT
    `COLUMN_NAME`,
    `ORDINAL_POSITION`,
    `COLUMN_DEFAULT`,
    `IS_NULLABLE`,
    `DATA_TYPE`,
    `CHARACTER_MAXIMUM_LENGTH`,
    `NUMERIC_PRECISION`,
    `NUMERIC_SCALE`,
    `DATETIME_PRECISION`,
    `CHARACTER_SET_NAME`,
    `COLLATION_NAME`,
    `COLUMN_TYPE`,
    `COLUMN_KEY`,
    `EXTRA`,
    `PRIVILEGES`,
    `COLUMN_COMMENT`
FROM
    `information_schema`.`COLUMNS`
WHERE
    (`TABLE_SCHEMA` LIKE :schema) AND (`TABLE_NAME` LIKE :table)
ORDER BY
    `ORDINAL_POSITION`
EOT;
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table'  => $table,
        ]);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['BASE_TYPE'] = $this->getBaseType($row['DATA_TYPE']);
            $result[$row['COLUMN_NAME']] = $row;
        }
        return $result;
    }


    /**
     * 把驱动相关的数据类型转换为驱动无关的通用类型
     */
    public function getBaseType($datatype)
    {
        switch ($datatype) {
            /* string type */
            case 'varchar':
            case 'char':
            case 'text':
            case 'mediumtext':
            case 'longtext':
                return 'string';

            /* numeric type */
            case 'int':
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'bigint':
            case 'float':
            case 'double':
            case 'decimal':
            case 'timestamp':
                return 'numeric';

            /* time type */
            case 'datetime':
            case 'date':
                return 'time';

            /* enum */
            case 'enum':
                return 'enum';

            /* set */
            case 'set':
                return 'set';

            /* binary */
            case 'varbinary':
                return 'stream';

            /* unknown type */
            default:
                return '';
        }
    }

    /**
     * 获取<schema.table>的主键列名
     *
     * @return string|null
     */
    public function getPrimaryKey($table, $schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        $sql = <<<'EOT'
SELECT
    `COLUMN_NAME`
FROM
    `information_schema`.`COLUMNS`
WHERE
    (`TABLE_SCHEMA` LIKE :schema) AND (`TABLE_NAME` LIKE :table) AND (`COLUMN_KEY` LIKE 'PRI')
ORDER BY
    `ORDINAL_POSITION`
EOT;
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table'  => $table,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row['COLUMN_NAME'];
        } else {
            return null;
        }
    }


    /**
     * 获取<schema.table>的所有UNIQUE约束的列名数组
     *
     * @return array
     */
    public function getUniqueColumns($table, $schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        $sql = <<<'EOT'
SELECT
    `COLUMN_NAME`
FROM
    `information_schema`.`COLUMNS`
WHERE
    (`TABLE_SCHEMA` LIKE :schema) AND (`TABLE_NAME` LIKE :table) AND (`COLUMN_KEY` LIKE 'UNI')
ORDER BY
    `ORDINAL_POSITION`
EOT;
        $stmt = $this->db->getPDO()->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table'  => $table,
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN,0);
        if ($result) {
            return $result;
        } else {
            return [];
        }
    }
}
