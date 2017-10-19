<?php

use \PHPUnit\Framework\TestCase;
use \Dida\Debug\Debug;

/**
 * DbTest
 */
class DbTest extends TestCase
{
    public $db = null;


    /**
     * 初始化测试环境
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->db = new \Dida\Db\Mysql\MysqlDb([
            'db.dsn'         => 'mysql:host=localhost;port=3306;dbname=zeupin',
            'db.username'    => 'zeupin',
            'db.password'    => 'zeupin',
            'db.options'     => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => false
            ],
            'db.workdir'     => __DIR__ . '/cache',
            'db.type'        => 'mysql',
            'db.prefix'      => 'zp_',
            'db.swap_prefix' => '###_',
        ]);
    }


    /**
     * 测试phpunit是否正常工作
     */
    public function testPhpUnitWorksWell()
    {
        $value = 1;

        $this->assertEquals(1, $value);
    }


    public function test0Table()
    {
        $admin = $this->db->table('admin')
            ->build();
        $expected = <<<EOT
SELECT
    *
FROM
    zp_admin
EOT;
        $this->assertEquals($expected, $admin->statement);
        $this->assertEquals([], $admin->parameters);
    }


    public function test_getColumn()
    {
        $admin = $this->db->table('admin');

        $result1 = $admin->getColumn(2);
        echo Debug::varDump($result1);

        $result2 = $admin->getColumn('mobile');
        echo Debug::varDump($result2);

        // 期望$result1=$result2
        $this->assertEquals($result1, $result2);
    }


    public function test_getColumn_1()
    {
        // user是个空表
        $data = $this->db->table('user');

        $result1 = $data->getColumn(2);
        echo Debug::varDump($result1);

        $result2 = $data->getColumn('mobile');
        echo Debug::varDump($result2);

        // 期望$result1=$result2
        $this->assertEquals($result1, $result2);
    }
}
