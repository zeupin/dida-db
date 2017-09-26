# 目标

[TOC]

## 1. 架构基于PDO，支持常见的关系数据库。

* Mysql
* SQL Server
* Sqlite v3

## 2. 在Db中暴露出PDO，方便做高级查询。

```php
$db->pdo->...
```

## 3. Db是个抽象类。

```php
abstract class Db
{
...
}
```

## 4. 具体用时，必须先继承Db，再使用特定的Db类型。

```php
class Mysql extends Db
{
...
};

$db = new \Dida\Db\Mysql\MysqlDb($cfg);
```

## 5. Db类的参数配置。

```php
$cfg = [
    /* 若干 pdo driver 配置 */
    'dsn'      => 'mysql:host=localhost;port=3306;dbname=数据库',
    'username' => '数据库用户',
    'password' => '数据库密码',
    'options'  => [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
    ],

    // 若干必填参数
    'workdir'  => __DIR__ . '/zeupin',

    // 若干选填参数
    'prefix'   => 'zp_',
    'vprefix'  => '###_',

    // 懒连接配置（这个是一次性操作，一般在安装系统时初始化）
    'lazy_mode'         => true,
    'lazy_driver_name'  => 'mysql',
    'lazy_quote_table'  => ['`', '`'],
    'lazy_quote_column' => ['`', '`'],
];
```

## 6. 懒连接。实际要做查询时，才会真正连接数据库。

## 7. 类的调用层次。

```
Db -> Statement -> Builder -> ResultSet
```

## 8. 一律使用Prepare模式。

* 更安全。
* 一致化处理，减少代码量。

## 9. Builder分为Builder和BuilderLite两个版本。

* `Builder` 是全功能版本，会Quote表名/列名的。
* `BuilderLite` 则不quote表名/列名，大幅简化了处理流程，加快了处理速度，但是只建议用于标准的数据库。

## 10. 数据库的表名和列名的命名建议。

最佳实践：数据库的表名和列名以某些规则来限定，使之不会和数据库关键字相同，即可无需quote处理，从而可以简化SQL生成，加快程序执行，比如如下规则：

1. 数据库的数据表用`prefix_`开头。
2. 列名中的每个单词都用`_`结束，（`id_`, `name_`, `modified_at_`）。
