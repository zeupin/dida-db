<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Mysql;

use \PDO;

/**
 * Mysql Schema Trait
 */
trait MysqlSchemaTrait
{
    /**
     * List all table names of the <schema>.
     */
    public function listTableNames($schema, $prefix = '')
    {
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
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table'  => $prefix . '%',
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $result;
    }


    /**
     * Get all meta information about the <schema.table>.
     */
    public function getTableInfo($schema, $table)
    {
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
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table'  => $table,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }


    /**
     * Get all column information about the <schema.table>.
     */
    public function getColumnsInfo($schema, $table)
    {
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
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table'  => $table,
        ]);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['COLUMN_NAME']] = $row;
        }
        return $result;
    }


    /**
     * Exports the specified schema to a schema directory.
     *
     * @param string $schema
     * @param string $prefix  Default table prefix.
     */
    public function exportSchema($schema, $prefix = '')
    {
        if ($this->pdo === null) {
            return false;
        }

        $target_dir = $this->cfg['workdir'] . '~SCHEMA' . DIRECTORY_SEPARATOR;
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0777)) {
                return false;
            }
        }

        $tablenames = $this->listTableNames($schema);
        $this->saveContents($target_dir . "$schema.~TABLENAMES.php", $this->exportVar($tablenames));

        foreach ($tablenames as $table) {
            $info = [
                'TABLE'   => $this->getTableInfo($schema, $table),
                "COLUMNS" => $this->getColumnsInfo($schema, $table),
            ];
            $this->saveContents($target_dir . "$schema.$table.php", $this->exportVar($info));
        }
    }


    /**
     * Exports a variable.
     *
     * @param mixed $var
     * @return string
     */
    private function exportVar($var)
    {
        return "<?php\n" . 'return ' . var_export($var, true) . ";\n";
    }


    /**
     * Save some contents to a file.
     *
     * @param string $file
     * @param mixed $data
     *
     * @return mixed  The number of bytes that were written to the file, on success.
     *                 FALSE, on failure.
     *                 TRUE, on the same value with before.
     */
    private function saveContents($file, $data)
    {
        if (file_exists($file) && is_file($file)) {
            $str = file_get_contents($file);
            if ($str === $data) {
                return true;
            }
        }
        return file_put_contents($file, $data);
    }


    /**
     * Delete all files and subfolders in the specified directory.
     *
     * @param string $dir
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function clearDir($dir)
    {
        if (!file_exists($dir) || !is_dir($dir)) {
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
                // If it is a subfolder
                if (is_dir($path)) {
                    if (!$this->clearSchemaDir($path)) {
                        return false;
                    }
                    rmdir($path);
                    continue;
                } else {
                    // unlink this file
                    unlink($path);
                }
            } catch (Exception $ex) {
                return false;
            }
        }

        return true;
    }
}
