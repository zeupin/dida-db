<?php

use \PHPUnit\Framework\TestCase;
use \Dida\Debug\Debug;

/**
 * DbTest
 */
class DbTest extends TestCase
{
    public $db = null;


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
}
