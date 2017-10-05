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

## 20. whereMatch(数组)

一个常见的使用场景是根据一个数组为条件，直接搜索出符合的记录，为此，设计了这个函数。

```php
->whereMatch([
    'color' => 'red',
    'brand' => 'Zeupin'
])->...                    // (`color`='red') AND (`brand`='Zeupin')
```

## 21. whereMatch(数据， 逻辑)

```php
$admin = $db->table('admin')
    ->whereMatch([
        'id'      => [1, 2, 3],
        'company' => 'zeupin',
    ], 'OR')
    ->build();
echo var_export($admin->statement, true) . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
SELECT * FROM zp_admin WHERE ((id IN (?, ?, ?)) OR (company = ?))

array (
  0 => 1,
  1 => 2,
  2 => 3,
  3 => 'zeupin',
)
```

## 22. select($columnlist = [])

### 22.1 columnlist 参数

标准的columnlist的参数格式如下：
```php
[
    别名 => 列表达式，
    别名 => 列表达式
]
```

### 22.2 如果不设置columnlist参数，等效于 SELECT *。

## 23. delete()

```php
$admin = $db->table('admin')
    ->delete()
    ->build();
echo var_export($admin->statement, true) . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
'DELETE FROM zp_admin'
array()
```

## 24. delete() 带where条件

```php
$admin = $db->table('admin')
    ->whereMatch([
        'id'      => [1, 2, 3],
        'company' => 'zeupin',
    ])
    ->delete()
    ->build();
echo var_export($admin->statement, true) . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
'DELETE FROM zp_admin WHERE ((id IN (?, ?, ?)) AND (brand = ?))'
array (
  0 => 1,
  1 => 2,
  2 => 3,
  3 => 'zeupin',
)
```

## 25. insert($record)

插入一条记录

```php
$admin = $db->table('admin')
    ->insert([
        'name'  => 'James Band',
        'level' => 40,
    ])
    ->build();
echo var_export($admin->statement, true) . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
'INSERT INTO zp_admin(name, level) VALUES (?, ?)'
array (
  0 => 'James Band',
  1 => 40,
)
```

## 26. update()

## 27. setValue(字段，值)

## 28. setExpr(字段，表达式，参数数组)

## 29. setFromTable(字段，表B，目标列B，表A的字段A，表B的字段B，是否检查表B的记录存在)

```php
$admin = $db->table('admin')
    ->setValue('name', 'Me')
    ->setExpr('age', 'age + ?', [1])
    ->setFromTable('fullname', '###_admin_info', 'fullname', 'id', 'id', true)
    ->whereMatch([
        'id'      => [1, 2, 3],
        'company' => 'zeupin',
    ])
    ->where('valid = 1')
    ->update()
    ->build();
echo var_export($admin->statement, true) . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
'UPDATE
    zp_admin
SET
    name = ?,
    age = age + ?,
    fullname = (SELECT zp_admin_info.fullname FROM zp_admin_info WHERE zp_admin.id = zp_admin_info.id)
WHERE
    ((id IN (?, ?, ?)) AND (company = ?))
    AND (valid = 1)
    AND (EXISTS (SELECT zp_admin_info.fullname FROM zp_admin_info WHERE zp_admin.id = zp_admin_info.id))'

array (
  0 => 'Me',
  1 => 1,
  2 => 1,
  3 => 2,
  4 => 3,
  5 => 'zeupin',
)

如果“是否检查表B的记录存在”设置为了false，则结果为：
'UPDATE
    zp_admin
SET
    name = ?,
    age = age + ?,
    fullname = (SELECT zp_admin_info.fullname FROM zp_admin_info WHERE zp_admin.id = zp_admin_info.id)
WHERE
    ((id IN (?, ?, ?)) AND (company = ?))
    AND (valid = 1)'
上面的WHERE条件中的Exists那行就没有了。
```

## 30. JOIN

四种JOIN：`JOIN`，`INNER JOIN`，`LEFT JOIN`， `RIGHT JOIN`

`join(表，on条件)`

```php
$admin = $db->table('admin', 'a')
    ->setValue('name', 'Me')
    ->setExpr('age', 'age + ?', [1])
    ->setFromTable('fullname', '###_admin_info', 'fullname', 'id', 'id', false)
    ->join('###_admin_info AS b', 'a.id > b.id+?', [99])
    ->leftJoin('###_admin_info AS c', 'a.id=c.id')
    ->whereMatch([
        'id'      => [1, 2, 3],
        'company' => 'zeupin',
    ])
    ->where('valid = 1')
    ->select()
    ->build();
echo $admin->statement . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
SELECT
    *
FROM
    zp_admin AS a
JOIN zp_admin_info AS b
    ON a.id > b.id+?
LEFT JOIN zp_admin_info AS c
    ON a.id=c.id
WHERE
    ((id IN (?, ?, ?)) AND (company = ?))
    AND (valid = 1)

array (
  0 => 99,
  1 => 1,
  2 => 2,
  3 => 3,
  4 => 'zeupin',
)
```

## 31. inc(列名，值)

把指定的列自增一个值

```php
$admin = $db->table('admin', 'a')
    ->where('valid = 1')
    ->inc('age')
    ->update()
    ->build();
echo $admin->statement . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
UPDATE
    zp_admin AS a
SET
    age = age + 1
WHERE
    (valid = 1)
```

## 32. 优化update、insert、delete时的表名子句，不加AS。

## 33. `GROUP BY`

## 34. `HAVING`

```php
$admin = $db->table('admin', 'a')
    ->where('valid = 1')
    ->groupBy(['name', 'age'])
    ->having(['sum(age)', '>', 100])
    ->having(['avg(age)', '>', 20])
    ->build();
echo $admin->statement . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
SELECT
    *
FROM
    zp_admin AS a
WHERE
    (valid = 1)
GROUP BY
    name, age
HAVING
    (sum(age) > ?)
    AND (avg(age) > ?)
    
array (
  0 => 100,
  1 => 20,
)
```

## 35. distinct

```php
$admin = $db->table('admin', 'a')
    ->distinct()
    ->build();
echo $admin->statement . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
SELECT
    DISTINCT
    *
FROM
    zp_admin AS a
```

## 36. count()

## 37. orderBy($columns)

设置order by子句，允许多次链式调用。

参数 `$columns` 可以为如下格式：
* 数组：格式为 `[列名=>asc/desc/空串, 列名, '列名 DESC']`
* 字符串：列名 DESC, 列名, 列名 DESC （以逗号分隔的字符串）

```php
$admin = $db->table('admin', 'a')
    ->orderBy('id desc, name ,###_admin.name DESC, age asc, ')
    ->build();
echo $admin->statement . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
SELECT
    *
FROM
    zp_admin AS a
ORDER BY
    id DESC, name, zp_admin.name DESC, age ASC
```

## 38. limit($limit)

设置limit条件。$limit为一个字符串，会照原样输出

```php
$admin = $db->table('admin', 'a')
    ->orderBy('id desc, name ,###_admin.name DESC, age asc, ')
    ->limit(5)
    ->build();
echo $admin->statement . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;

```

结果是：
```php
SELECT
    *
FROM
    zp_admin AS a
ORDER BY
    id DESC, name, zp_admin.name DESC, age ASC
LIMIT
    5
```

## 39. dec($column, $value)

把指定的列自减一个值。

```php
$admin = $db->table('admin', 'a')
    ->where('valid = 1')
    ->dec('age')
    ->update()
    ->build();
echo $admin->statement . PHP_EOL;
echo var_export($admin->parameters, true) . PHP_EOL;
```

结果是：
```php
UPDATE
    zp_admin AS a
SET
    age = age - 1
WHERE
    (valid = 1)
```

## 40. 执行查询query()动作

先build，然后执行SQL语句，返回查询结果。如果出错，返回false。如果正常，返回一个PDOStatement。

## 41. 执行fetch()动作

先执行query，然后返回下一条记录。

```php
$admin = $db->table('admin', 'a')
    ->fetch();
echo Debug::varExport($admin);
```

结果是：
```php
[
    'id'        => '2',
    'name'      => '李四',
    'mobile'    => '135044444444',
    'email'     => '444@444.com',
    'id_wechat' => null,
    'pwd_salt'  => null,
    'pwd_hash'  => null,
]
```

## 42. 执行fetchAll()动作

执行query，然后返回全部记录。

```php
$admin = $db->table('admin', 'a')
    ->fetchAll();
echo Debug::varExport($admin);
```

结果是：
```php
[
    0 => [
             'id'        => '2',
             'name'      => '李四',
             'mobile'    => '135044444444',
             'email'     => '444@444.com',
             'id_wechat' => null,
             'pwd_salt'  => null,
             'pwd_hash'  => null,
         ],
    1 => [
             'id'        => '3',
             'name'      => '王五',
             'mobile'    => '135055555555',
             'email'     => '555@555.com',
             'id_wechat' => null,
             'pwd_salt'  => null,
             'pwd_hash'  => null,
         ],
]
```

