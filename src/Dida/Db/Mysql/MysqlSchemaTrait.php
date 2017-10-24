<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Mysql;

use \PDO;
use \Exception;

/**
 * Schema generation/reflection features for MySQL
 */
trait MysqlSchemaTrait
{
  


    /**
     * 导出指定的数据库
     *
     * @param string $schema
     * @param string $prefix  Default table prefix.
     */
    public function exportSchema($schema, $prefix = '')
    {
        if ($this->pdo === null) {
            return false;
        }

        $target_dir = $this->workdir . '~SCHEMA' . DIRECTORY_SEPARATOR;
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0777)) {
                return false;
            }
        }

        $tablenames = $this->listTableNames($schema);
        $this->saveContents($target_dir . "~TABLENAMES.php", $this->exportVar($tablenames));

        foreach ($tablenames as $table) {
            $info = [
                'TABLE'   => $this->getTableInfo($schema, $table),
                "COLUMNS" => $this->getAllColumnInfo($schema, $table),
            ];
            $this->saveContents($target_dir . "$table.php", $this->exportVar($info));
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
}
