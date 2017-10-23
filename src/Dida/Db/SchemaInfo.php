<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \Exception;

/**
 * SchemaInfo
 */
abstract class SchemaInfo implements SchemaInfoInterface
{
    /**
     * @var \Dida\Db\Db
     */
    protected $db = null;

    /**
     * 缓存目录
     *
     * @var string
     */
    protected $cacheDir = null;

    /**
     * 数据库名
     *
     * @var string
     */
    protected $schema = null;


    /**
     * 类的构造函数。
     *
     * @param \Dida\Db\Db $db
     */
    public function __construct(&$db)
    {
        $this->db = $db;

        $cfg = $this->db->getConfig();

        if (!isset($cfg['db.name'])) {
            throw new Exception('db.name 未配置');
        }
        $this->schema  = $cfg['db.name'];

        if (!isset($cfg['db.schemainfo_dir'])) {
            throw new Exception('db.schemainfo_dir 未配置');
        }

        if (!$this->setCacheDir($cfg['db.schemainfo_dir'])) {
            throw new Exception('db.schemainfo_dir 配置有误');
        }
    }


    public function setCacheDir($cacheDir)
    {
        if (file_exists($cacheDir) && is_dir($cacheDir)) {
            $this->cacheDir = realpath($cacheDir);
            return true;
        } else {
            $this->cacheDir = null;
            return false;
        }
    }


    /**
     * 缓存所有表的信息
     *
     * @param string $schema
     * @param string $prefix
     */
    public function cacheAllTableInfo($schema, $prefix)
    {
        // 先检查缓存目录是否已经设置，是否可写入
        $dir = $this->cacheDir;
        if (!is_string($dir) || !file_exists($dir) || !is_dir($dir) || !is_writeable($dir)) {
            return false;
        }

        // 清空缓存目录
        $this->clearDir($this->cacheDir);

        // 列出所有满足条件的数据表
        $tables = $this->listTableNames($schema, $prefix);

        // 依次把每个数据表资料都做一下缓存
        foreach ($tables as $table) {
            // 获取所有列的信息
            $allColumnInfo = $this->getAllColumnInfo($schema, $table);

            // 把列名作为数组的key
            $array = [];
            foreach ($allColumnInfo as $columnInfo) {
                $array[$columnInfo['COLUMN_NAME']] = $columnInfo;
            }

            // 把这个数组缓存起来，供以后数据库脱机使用
            $DS = DIRECTORY_SEPARATOR;
            $filename = $this->cacheDir . $DS . $schema . $DS . $table . ".info.php";
            $content = "<?php\nreturn " . var_export($array, true) . "\n";
            file_put_contents($filename, $content);
        }
    }


    /**
     * 清空指定目录，包括其下所有文件和所有子目录。
     *
     * @return boolean 成功返回true，失败返回false
     */
    protected function clearDir($dir)
    {
        // 如果非法，返回false
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


    /**
     * 从缓存中读取一个数据表的信息
     *
     * @param string $table
     *
     * @return array|false 成功返回表信息数组，失败返回false
     */
    public function readTableInfoFromCache($table)
    {
        // 先检查缓存目录是否已经设置
        $dir = $this->cacheDir;
        if (!is_string($dir) || !file_exists($dir) || !is_dir($dir) || !is_writeable($dir)) {
            return false;
        }

        // 从缓存中读取表信息
        $file = $this->cacheDir . DIRECTORY_SEPARATOR . "$table.info.php";
        if (file_exists($file)) {
            return include($file);
        }

        // 如果缓存不存在目标文件，则返回false
        return false;
    }
}
