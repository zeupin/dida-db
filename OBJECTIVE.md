# 目标

[TOC]

## 1. 架构基于PDO，支持常见的关系数据库。

## 2. 暴露出PDO，方便高级查询。

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

## 4. 具体用时，必须直接使用特定的Db类型。

```php
class Mysql extends Db
{
...
};

$db = new \Dida\Db\Mysql($cfg);
```

## 5. Db类的参数配置

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

    // 懒连接配置
    'lazy_driver_name'  => 'mysql',
    'lazy_quote_table'  => ['`', '`'],
    'lazy_quote_column' => ['`', '`'],
];
```

## 6. 懒连接。实际要做查询时，才会真正连接数据库。