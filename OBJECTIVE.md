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
    /* PDO driver 配置 */
    'dsn'      => 'mysql:host=localhost;port=3306;dbname=数据库',
    'username' => '数据库用户',
    'password' => '数据库密码',
    'options'  => [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
    ],

    // 和驱动相关的配置
    'table_quote_prefix'   => '`',
    'table_quote_postfix'  => '`',
    'column_quote_prefix'  => '`',
    'column_quote_postfix' => '`',

    // 必填参数
    'workdir'  => __DIR__ . '/zeupin',

    // 选填参数
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

## 8. 执行时，统一使用预处理模式（Prepare）。

* 更安全。
* 一致化处理，减少代码量。

## ~~9. Builder分为Builder和BuilderLite两个版本。~~

* `Builder` 是全功能版本，会Quote表名/列名的。
* `BuilderLite` 则不quote表名/列名，大幅简化了处理流程，加快了处理速度。参见 `#10 数据库命名建议`。

**此条已经废弃，参见#15直接改为Lite版的做法，不在框架中去quote表名/列名**

## 10. 数据库的表名和列名的命名建议。

最佳实践：数据库的表名和列名以某些规则来限定，使之不会和数据库关键字相同，即可无需quote处理，从而可以简化SQL生成，加快程序执行，比如如下规则：

1. 数据库的数据表用`prefix_`开头。
2. 列名中的每个单词都用`_`结束，（`id_`, `name_`, `modified_at_`）。

## 11. 从Db类生成SQL类

通过 `Db` 类的如下方法，生成 `Statement` 类实例

* $db->sql($statement, $parameters=[])
* $db->table(表名, 别名=null, prefix=null)

## 12. $db->sql($statement, $parameters=[])

直接设置sql

## 13. $db->table(pure_表名, 别名=null, 不要prefix)

设置主表的表名和别名。

```
$db->table('user', 'u');
```

## 14. SQL类只负责往设置各种指令，具体building工作全部给Builder去干。

## 15. 去除自动转义表名和列名的功能。

感觉这个功能非常鸡肋，如果觉得会和SQL关键字有冲突的话，完全可以自己去quote表名/列名。参见 #9

因为不同数据库的转义处理不一样，仅仅为了转义的要求，而将Db拆分成MysqlDb，SqliteDb，SqlsrvDb等等，增加了复杂度不说，生成的SQL代码看上去也很紊乱，完全脱离了框架初衷，有过度编程的感觉。

Dida框架的主要目标是**快**，适度编码，加快运行速度，绝对是考虑的要点。

## 16. WHERE条件

用 `where` 新增一个标准where条件。 `where([列表达式，运算，数据])`，参见 #17。

可以链式调用where，生成多个条件。`->where(条件1)->where(条件2)->where(条件3)`

可以直接设置字符串格式的条件。`where(字符串，参数数组)`

## 17. where条件(Condition)的数据格式

where条件的标准格式是： `[列表达式，运算符，数据]`，如：`['id', '=', 2]`。

### 17.1 条件中的列表达式

对列表达式，会进行vsql处理，替换掉其中的 `###_` 表前缀。

注意：列表式不会自动转义，需要自己进行，参见 #15。
```
比如对Mysql，一般列名用 "name"，特殊情况下如果需要的话，你可以自己转义成 "`name`"。
```

### 17.2 条件中的数据个数

有些运算符可没有上述的"数据"，比如 `ISNULL`运算，如：`['name', 'isnull']`。

还有些运算符可以支持2个数据，比如`BETWEEN`运算，如：`['age', 'between', 20, 40]`。

### 17.3 支持的运算符

```php
/*
 * All supported operater set.
 */
protected static $opertor_set = [
    /* Raw SQL */
    'RAW'         => 'RAW',
    /* equal */
    'EQ'          => 'EQ',
    '='           => 'EQ',
    '=='          => 'EQ',
    /* not equal */
    'NEQ'         => 'NEQ',
    '<>'          => 'NEQ',
    '!='          => 'NEQ',
    /* <,>,<=,>= */
    'GT'          => 'GT',
    '>'           => 'GT',
    'EGT'         => 'EGT',
    '>='          => 'EGT',
    'LT'          => 'LT',
    '<'           => 'LT',
    'ELT'         => 'ELT',
    '<='          => 'ELT',
    /* LIKE */
    'LIKE'        => 'LIKE',
    'NOT LIKE'    => 'NOTLIKE',
    'NOTLIKE'     => 'NOTLIKE',
    /* IN */
    'IN'          => 'IN',
    'NOT IN'      => 'NOTIN',
    'NOTIN'       => 'NOTIN',
    /* BETWEEN */
    'BETWEEN'     => 'BETWEEN',
    'NOT BETWEEN' => 'NOTBETWEEN',
    'NOTBETWEEN'  => 'NOTBETWEEN',
    /* EXISTS */
    'EXISTS'      => 'EXISTS',
    'NOT EXISTS'  => 'NOTEXISTS',
    'NOTEXISTS'   => 'NOTEXISTS',
    /* NULL */
    'ISNULL'      => 'ISNULL',
    'NULL'        => 'ISNULL',
    'ISNOTNULL'   => 'ISNOTNULL',
    'IS NOT NULL' => 'ISNOTNULL',
    'NOTNULL'     => 'ISNOTNULL',
    'NOT NULL'    => 'ISNOTNULL',
];
```

### 17.4 RAW

`[列表达式，'RAW'，数据]`

如果有一个复杂的条件运算，可以用`RAW`运算符来处理，框架会把条件中的`列表达式`部分原样保留(相当于statement部分)，同时把`数据`作为表达式的参数数组（相当于parameters部分）。

注意：第三个参数`数据`必须是一个**参数数组**。

## 18. whereLogic()

设置各个where条件间的逻辑关系

## 19. whereMany()

一次设置很多条件，`whereMany( 多个条件，逻辑 )`

```php
$db->table('user', 'u')->whereMany([
    ['列表达式', '运算', '数据'],
    ['列表达式', '运算', '数据'],
    ['列表达式', '运算', '数据'],
], 'AND')->...
```

## 20. find(数组)

一个常见的使用场景是根据一个数组为条件，直接搜索出符合的记录，为此，设计了这个函数。

```php
->find([
    'color' => 'red',
    'brand' => 'Zeupin'
])->...                    // (`color`='red') AND (`brand`='Zeupin')
```
