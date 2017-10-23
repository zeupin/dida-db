<?php

use \PHPUnit\Framework\TestCase;
use \Dida\Debug\Debug;

/**
 * SchemaInfo Test
 */
class SchemaInfoTest extends TestCase
{
    /**
     * @var \Dida\Db\Mysql\MysqlDb
     */
    public $db = null;


    /**
     * 初始化测试环境
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->db = new \Dida\Db\Mysql\MysqlDb([
            'db.dsn'            => 'mysql:host=localhost;port=3306;dbname=zeupin',
            'db.username'       => 'zeupin',
            'db.password'       => 'zeupin',
            'db.options'        => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => false
            ],
            'db.schemainfo_dir' => __DIR__ . '/cache',
            'db.name'           => 'zeupin',
            'db.type'           => 'mysql',
            'db.prefix'         => 'zp_',
            'db.swap_prefix'    => '###_',
        ]);
    }


    /**
     * 执行一个SQL文件
     */
    public function resetMock($sql_file)
    {
        $sql = file_get_contents($sql_file);
        $this->db->connect();
        $this->db->pdo->exec($sql);
    }


    /**
     * 测试phpunit是否正常工作
     */
    public function testPhpUnitWorksWell()
    {
        $value = 1;

        $this->assertEquals(1, $value);
    }


    /**
     * 测试数据库是否可以连接
     */
    public function testConnectDb()
    {
        $this->db->connect();
        $this->assertEquals(true, $this->db->isConnected());
    }


    /**
     * 测试数据库是否可以正常工作
     */
    public function testDbWorkWell()
    {
        $this->db->connect();
        $this->assertEquals(true, $this->db->worksWell());
    }


    /**
     * 测试模拟数据能否正常使用
     */
    public function testResetMock()
    {
        $this->resetMock(__DIR__ . '/zp_test.sql');

        $this->db->connect();
        $sql = $this->db->table('test', null, 'zp_');
        $result = $sql->select(["count(*)"])->execute()->getRow();
        $this->assertEquals(1, $result['id']);
    }

    public function testCacheAllTableInfo()
    {
        $this->db->getSchemaInfo()->cacheAllTableInfo('zeupin', 'zp_');
    }
}
