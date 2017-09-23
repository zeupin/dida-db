# interface `Dida\Db\SchemaInterface`

[TOC]

## Schema接口

### `listTableNames($schema, $prefix = '')`
> 列出`$schema`数据库里面含有`$prefix`的所有数据表。
> 
> 结果返回一个一维数组，里面包含所有查询到的表名。

### `getTableInfo($schema, $table)`
> 获取`$schema`数据库的`$table`表的相关信息。`$table`为实际的数据表名。
>
> 结果返回一个一维数组，至少要包含如下的键名：
> * `TABLE_SCHEMA`
> * `TABLE_NAME`
> * `TABLE_TYPE`

### `getColumnsInfo($schema, $table);`
> 获取`$schema`数据库的`$table`表的所有列的相关信息。`$table`为实际的数据表名。
> 
> 结果返回一个二维数组，格式如下，并至少包含列举出来的若干键名：
```
[
    '列名' => [
        'COLUMN_NAME'     => ,  // 列名
        'BASE_TYPE'       => ,  // 基本类型：'numeric'，'string'，'time'
        'DATA_TYPE'       => ,  // 如：'int',
        'COLUMN_TYPE'     => ,  // 如：'int(11)',
        'COLUMN_KEY'      => ,  // 如：'PRI',
        'COLUMN_DEFAULT'  => ,  // 如：NULL,
        'IS_NULLABLE'     => ,  // 如：'NO',
    ],
    ...
]
```
