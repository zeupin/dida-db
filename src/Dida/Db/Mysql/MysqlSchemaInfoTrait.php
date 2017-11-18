<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db\Mysql;

use \PDO;

trait MysqlSchemaInfoTrait
{
    protected $sqlTables = <<<'EOT'
SELECT
    `TABLE_NAME`,
    `TABLE_TYPE`,
    `TABLE_CATALOG`,
    `ENGINE`,
    `TABLE_COLLATION`,
    `TABLE_COMMENT`
FROM
    `information_schema`.`TABLES`
WHERE
    (`TABLE_SCHEMA` LIKE :schema) AND (`TABLE_NAME` LIKE :table)
ORDER BY
    `TABLE_NAME`
EOT;

    protected $sqlColumns = <<<'EOT'
SELECT
    `TABLE_NAME`,
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
    `COLUMN_COMMENT`
FROM
    `information_schema`.`COLUMNS`
WHERE
    (`TABLE_SCHEMA` LIKE :schema) AND (`TABLE_NAME` LIKE :table)
ORDER BY
    `TABLE_NAME`, `ORDINAL_POSITION`
EOT;


    protected function queryTableInfo($table)
    {
        $stmt = $this->db->getPDO()->prepare($this->sqlTables);
        $stmt->execute([
            ':schema' => $this->schema,
            ':table'  => $table,
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }


    protected function queryColumnInfo($table)
    {
        $stmt = $this->db->getPDO()->prepare($this->sqlColumns);
        $stmt->execute([
            ':schema' => $this->schema,
            ':table'  => $table,
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }


    protected function queryAllTableInfo()
    {
        $stmt = $this->db->getPDO()->prepare($this->sqlTables);
        $stmt->execute([
            ':schema' => $this->schema,
            ':table'  => $this->prefix . '%',
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }


    protected function queryAllColumnInfo()
    {
        $stmt = $this->db->getPDO()->prepare($this->sqlColumns);
        $stmt->execute([
            ':schema' => $this->schema,
            ':table'  => $this->prefix . '%',
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
}
