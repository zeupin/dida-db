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
     * 数据表前缀
     *
     * @var string
     */
    protected $prefix = '';


    /**
     * 类的构造函数。
     *
     * @param \Dida\Db\Db $db
     */
    public function __construct(&$db)
    {
        $this->db = $db;

        $cfg = $this->db->getConfig();

        // 数据库名
        if (!isset($cfg['db.name'])) {
            throw new Exception('db.name 未配置');
        }
        $this->schema = $cfg['db.name'];

        // 默认的数据表前缀
        if (!isset($cfg['db.prefix']) || !is_string($cfg['db.prefix'])) {
            $this->prefix = '';
        } else {
            $this->prefix = $cfg['db.prefix'];
        }

        // SchemaInfo的缓存目录
        if (!isset($cfg['db.schemainfo_dir'])) {
            throw new Exception('db.schemainfo_dir 未配置');
        }
        if (!$this->setCacheDir($cfg['db.schemainfo_dir'])) {
            throw new Exception('db.schemainfo_dir 配置有误');
        }
    }


    /**
     * 设置缓存目录
     *
     * @param string $cacheDir
     *
     * @return boolean 成功返回true，失败返回false
     */
    public function setCacheDir($cacheDir)
    {
        // 检查参数是否合法
        if (!is_string($cacheDir)) {
            $this->cacheDir = null;
            return false;
        }

        // 如果目录不存在，先尝试创建目录
        if (!file_exists($cacheDir)) {
            $result = mkdir($cacheDir, 0777, true);
            if ($result === true) {
                // 如果创建成功
                $this->cacheDir = realpath($cacheDir);
                return true;
            } else {
                // 如果目录创建失败
                $this->cacheDir = null;
                return false;
            }
        }

        // 如果不是目录，或者目录不可写，也返回失败
        if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
            $this->cacheDir = null;
            return false;
        }

        // 如果一切正常，返回成功
        $this->cacheDir = realpath($cacheDir);
        return true;
    }


    /**
     * 缓存所有表的信息
     *
     * @param string $schema
     * @param string $prefix
     */
    public function cacheAllTableColumnInfo()
    {
        $schema = $this->schema;
        $prefix = $this->prefix;

        // 先检查缓存目录是否已经设置，是否可写入
        $dir = $this->cacheDir;
        if (!is_string($dir) || !file_exists($dir) || !is_dir($dir) || !is_writeable($dir)) {
            return false;
        }

        // 清空缓存目录
        $this->clearDir($this->cacheDir);

        // 列出所有满足条件的数据表
        $tables = $this->listTableNames();

        // 准备好缓存目录
        $this->prepareColumnInfoCacheDir();

        // 依次把每个数据表资料都做一下缓存
        foreach ($tables as $table) {
            // 获取所有列的信息
            $allColumnMetas = $this->getAllColumnInfo($table);

            // 把列名作为数组的key
            $array = [];
            foreach ($allColumnMetas as $column) {
                $array[$column['COLUMN_NAME']] = $column;
            }

            // 准备写入文件的内容
            $content = "<?php\nreturn " . var_export($array, true) . ";\n";

            // 文件路径
            $filename = $this->getColumnInfoCachePath($table);

            // 保存文件
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
     * 从缓存中读取一个数据表的表元信息和列元信息
     *
     * @param string $table
     *
     * @return array|false 成功返回表信息数组，失败返回false
     */
    public function readColumnInfoFromCache($table)
    {
        $schema = $this->schema;

        // 先检查缓存目录是否已经设置
        $dir = $this->cacheDir;
        if (!is_string($dir) || !file_exists($dir) || !is_dir($dir) || !is_writeable($dir)) {
            return false;
        }

        // 从缓存中读取表信息
        $file = $this->getColumnInfoCachePath($table);
        if (file_exists($file)) {
            return include($file);
        }

        // 如果缓存不存在目标文件，则返回false
        return false;
    }

    /**
     *
     * @param string $table
     */
    protected function getColumnInfoCachePath($table)
    {
        $DS = DIRECTORY_SEPARATOR;
        $schema = $this->schema;
        $file = $this->cacheDir . "{$DS}{$schema}{$DS}{$table}.columninfo.php";
        return $file;
    }

    protected function prepareColumnInfoCacheDir()
    {
        $DS = DIRECTORY_SEPARATOR;
        $schema = $this->schema;
        $dir = $this->cacheDir . "{$DS}{$schema}";
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
