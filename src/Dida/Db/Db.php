<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \PDO;
use \Exception;

/**
 * Db
 */
abstract class Db implements DbInterface
{
    /**
     * 版本号
     */
    const VERSION = '0.1.5';

    /**
     * 缺省设置
     *
     * @var array
     */
    protected $cfg = [
        /* pdo 相关参数 */
        'db.dsn'      => null,
        'db.username' => null,
        'db.password' => null,
        'db.options'  => [], // pdo连接参数

        /* 必填参数 */
        'db.driver_type' => null, // 数据库驱动类型,如“Mysql”

        /* 可选参数 */
        'db.name'        => null,
        'db.charset'     => 'utf8',
        'db.persistence' => false, // 是否用长连接
        'db.prefix'      => '', // 默认的表前缀
        'db.swap_prefix' => '###_', // 默认的形式表前缀
    ];

    /**
     * PDO实例
     *
     * @var \PDO
     */
    public $pdo = null;

    /**
     * 指明数据库类型
     *
     * @var string
     */
    public $dbtype = null;

    /**
     * SqlBuilder实例
     *
     * @var \Dida\Db\Builder
     */
    protected $builder = null;


    /**
     * 类构造函数
     */
    public function __construct(array $cfg = [])
    {
        $this->setConfig($cfg);
    }


    /**
     * 类析构函数
     */
    public function __destruct()
    {
        $this->pdo = null;
    }


    /**
     * 设置$cfg
     *
     * @param array $cfg
     */
    public function setConfig(array &$cfg)
    {
        foreach ($this->cfg as $key => $value) {
            if (array_key_exists($key, $cfg)) {
                $this->cfg[$key] = $cfg[$key];
            }
        }
    }


    /**
     * 返回实例的$cfg数组
     *
     * @return array
     */
    public function &getConfig()
    {
        return $this->cfg;
    }


    /**
     * 配置Builder实例
     *
     * @param \Dida\Db\Builder $builder
     *
     * @return $this
     */
    public function setBuilder(&$builder)
    {
        $this->builder = $builder;

        return $this;
    }


    /**
     * 获取配置的Builder实例
     *
     * @return \Dida\Db\Builder
     */
    public function &getBuilder()
    {
        return $this->builder;
    }


    /**
     * 连接数据库
     *
     * @return boolean 成功返回true，失败返回false
     */
    public function connect()
    {
        // If connection exists
        if ($this->pdo !== null) {
            return true;
        }

        // Try to make a connection
        try {
            $this->pdo = new PDO(
                $this->cfg['db.dsn'], $this->cfg['db.username'], $this->cfg['db.password'], $this->cfg['db.options']
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * 检查是否已经连接数据库
     */
    public function isConnected()
    {
        return ($this->pdo !== null);
    }


    /**
     * 连接是否还能正常工作。
     * 检查是否已经连接数据库，且尚未被数据库断开，并能正常执行sql语句。
     *
     * @return boolean
     */
    public function worksWell()
    {
        if ($this->pdo === null) {
            return false;
        }

        // Checks if a simple SQL could be executed successfully,
        try {
            if ($this->pdo->query('SELECT 1') === false) {
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * 断开数据库连接
     */
    public function disconnect()
    {
        $this->pdo = null;
    }


    /**
     * 直接执行一条sql语句，返回一个结果集
     *
     * @param string $statement
     * @param array $parameters
     *
     * @return DataSet
     */
    public function execute($statement, array $parameters = [])
    {
        $this->connect();

        try {
            $stmt = $this->pdo->prepare($statement);
            $success = $stmt->execute($parameters);
            return new DataSet($this, $stmt, $success);
        } catch (Exception $ex) {
            return false;
        }
    }


    /**
     * 创建一个新的SqlQuery实例对象
     *
     * @return SqlQuery
     */
    protected function newSqlQuery()
    {
        $sql = new SqlQuery($this);
        return $sql;
    }


    /**
     * 创建一个新的SqlQuery实例对象，然后设置主表
     *
     * @param string $table
     * @param string $alias
     * @param string $prefix
     *
     * @return \Dida\Db\SqlQuery
     */
    public function table($table, $alias = null, $prefix = null)
    {
        $sql = $this->newSqlQuery();

        $sql->table($table, $alias, $prefix);

        return $sql;
    }
}
