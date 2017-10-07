# abstract class `\Dida\Db\Builder`

[TOC]

## 用法说明

```php
$users = $db->table('users')  // 生成Builder实例
    ->where()   // 各种where设置条件
    ->join()    // 更多设置
    ->select()  // 设置具体动作，默认为SELECT
    ->build();   // 开始构建，生成SELECT的$this->sql和$this->sql_parameters

$users->fetchAll();  // 取出所有的记录（如果有这一步，前面一步的build()可以省略）

$users->delete(); // 查询执行后，where条件都还继续有效，可以继续进行其它动作
    ->build()     // 开始构建，生成DELETE语句的$this->sql和$this->sql_parameters
    ->execute();       // 执行SQL（如果有这一步，前面一步的build()可以省略）
```

## 属性

### `$sql`
> `build()` 出来的SQL表达式。如果 `build()` 失败，会把这个值为`null`。

### `$sql_parameters`
> `build()` 出来的SQL表达式的参数。

### `$rowsAffected`
> 执行变更类SQL指令（INSERT / UPDATE / DELETE等）成功后，影响的行数。如果SQL执行失败，这个值是 `null`。

## 初始化和配置

### `__construct($db, $table, $prefix = '', $formal_prefix = '###_')`
> 类初始化。

| 参数 | 类型 | 说明
|----|---|----
| $db | \Dida\Db\Db | Db。
| $table | string | 表名，不含前缀。
| $prefix | string | 表的前缀。
| $formal_prefix | string | 表的形式前缀。根据这个形式前缀，在build时，`###_user`会被替换为`prefix_user`。

### `alias($alias)`
> 设置表的别名。

| 参数 | 类型 | 说明
|----|---|----
| $alias | string | 表的别名。如果`$alias`不是一个有效的别名，则设置表别名为`null`。

### `prefixConfig($prefix = '', $formal_prefix = '###_')`
> 设置表的`prefix`和`formal_prefix`

### `prepare($flag = true)`
> 设置本条语句的后面prepare模式，本条语句之前的语句的prepare模式不受影响。

### `reset()`
> 重置所有build相关的变量为初始值。但是不包括如下类变量：
> 1. `$preparemode`。一般不会出现有些网页要prepare，另外一些不要prepare。可通过 `prepare()` 改。
> 2. `$prefix`和`$formal_prefix`。一般类初始化后不会变。可通过 `prefixConfig()` 改。
> 3. `$alias`。可以通过 `alias()` 改。

## WHERE条件

### `where($condition, $parameters = [])`
> 设置一个where条件。

> 如果`$condition`是字符串，则不做任何处理，直接作为条件表达式。并将$parameters作为对应的参数数组一并导入。复杂条件这个用这个方式可以比较灵活的设置。

> 如果`$condition`是数组，则`$condition`必须是如下格式的一维数组：

```php
[列名或者列表达式, 比较指令, 数据]           // 适用于大多数比较指令
[列名或者列表达式, 比较指令]                 // 如比较指令为isnull或者exists等
[列名或者列表达式, 比较指令, 数据1, 数据2]   // 如比较指令为between等
```

> 举例如下：
```php
... ->where('id > 5')-> ...             // 为字符串的形式
... ->where(['id', '>', 5])-> ...       // 为条件数组的形式
```

### `whereMany(array $conditions, $logic = 'AND')`
> 同时设置多个where条件，各个条件之间用指定的`$logic`连接起来。`$conditions`是个二维数组，每条都是一个上面格式的`$condition`。

### `whereMatch(array $array)`
> 精确匹配`$array`数组指定的条件。举例如下：
```php
->whereMatch([
    'age'    => 30,
    'gender' => 'man',
])-> ...                   // 等于 where ('age=30 AND gender='man')
```

### 所有支持的条件指令

```php
/*
 * 支持的SQL条件运算集
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

## SELECT和相关设置

### `select(array $columnlist = [])`
> 生成 `SELECT` 语句。`$columnlist`的某项如有设置了别名，则必须为 `[ '别名'=>'列名或者列表达式', ...]` 的形式。

### `distinct($flag = true)`
> 生成 `DISTINCT` 语句。

### `join($tableB, $colA, $rel, $colB)`
> 生成 `INNER JOIN ...` 语句。

| 参数 | 类型 | 说明
|----|---|----
| $tableB | string | 要join进来的表B。可用###_指代表前缀。
| $colA | string | 当前表的某列。
| $rel | string | 比较运算符，一般为`=`，`>`，`<`之类。
| $colB | string | 表B的某列。

> 假设当前表为user, `join('###_order', 'id', '=', 'user_id')` 会生成：
```SQL
INNER JOIN tb_order ON tb_user.id = tb_order.user_id
```

### `innerJoin($tableB, $colA, $rel, $colB)`
> 生成 `INNER JOIN ...` 语句。参见join方法。

### `leftJoin($tableB, $colA, $rel, $colB)`
> 生成 `LEFT JOIN ...` 语句。参见join方法。

### `rightJoin($tableB, $colA, $rel, $colB)`
> 生成 `RIGHT JOIN ...` 语句。参见join方法。

### `groupBy(array $columnlist)`
> 生成 `GROUP BY ...` 语句。列名的排列顺序是无所谓的。

### `having($condition, $logic = null)`
> 生成 `HAVING ...` 语句。如果 `$logic` 为非空字符串，则 `$condition` 是一个二维条件数组；其它情况，则`$condition` 是一个一维条件数组。

### `orderBy($columns = '')`
> 生成 `ORDER BY ...` 语句。

### `count($columns = ['*'], $alias = null)`
> 生成 `SELECT COUNT(...)` 语句。

## UPDATE和相关设置

### `update()`
> 生成 `UPDATE` 语句。

### `set($column, $new_value)`
> 生成 UPDATE 语句中的一条 set。

### `setExpr($column, $expr, $parameters = [])`
> 生成 UPDATE 语句中的一条 set。

### `setFromTable($columnA, $tableB, $columnB, $colA, $colB, $checkExistsInWhere = true)`
> 生成 UPDATE 语句中的一条 set。对应的值从另外一个表中取来。

```sql
UPDATE
    tableA
SET
    columnA = (SELECT tableB.columnB WHERE tableA.colA = tableb.colB)
```

如果设置了 $checkExistsInWhere = true，则还要检查tableB中是否存在要填的值：

```sql
UPDATE
    tableA
SET
    columnA = (SELECT tableB.columnB WHERE tableA.colA = tableb.colB)
WHERE
    EXISTS (SELECT tableB.columnB WHERE tableA.colA = tableb.colB)
```

### `inc($column, $value = 1)`
> 指定的列自增指定数值。


## INSERT和相关设置

### `insert(array $record)`
> 生成 INSERT 语句，插入一条记录。`$record` 是一维数组。

## DELETE 和 TRUNCATE

### `delete()`
> 生成 DELETE 语句。

### `truncate()`
> 生成 TRUNCATE TABLE ... 语句。


## 构建

### `build()`
> 开始构建SQL表达式。如果成功，会更新 `$this->sql` 和 `$this->sql_parameters`。如果失败，会把 `$this->sql`设为`null`。


## 查询类的执行

### `fetch($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)`
> 从结果集中获取下一条记录，用法同PDOStatement的fetch()，详见PHP手册。默认是以`PDO::FETCH_ASSOC`返回一个一维数组。

### `fetchAll($fetch_style = null, $fetch_argument = null, array $ctor_args = null)`
> 从结果集中获取剩余的所有记录，用法同PDOStatement的fetchAll()，详见PHP手册。默认是以`PDO::FETCH_ASSOC`返回一个二维数组。

### `value($column_number = 0)`
> 获取下一条记录中的第n列的值。第1列的column_number是0。成功返回字符串，失败返回false。

### `exists()`
> 执行搜索，检查当前的 SELECT 语句是否能返回至少一条数据。根据执行情况，返回true/false。

## 更新类的执行

### `execute()`
> 执行变更类的SQL。verb必须是INSER，UPDATE，DELETE等。

### `lastInsertId($name = null)`
> 如果是自动递增id，返回最近新增记录的id值。