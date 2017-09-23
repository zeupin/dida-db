# class `Dida\Db\Db`

[TOC]

## 属性

### `pdo` -- PDO实例对象
### `pdoexception` -- PDOException实例对象
### `workdir` -- 工作目录
> 必须存在且可写。

### `rowsAffected` -- 影响的行数
> 执行变更类SQL指令（INSERT / UPDATE / DELETE等）成功后实际影响的行数。如果SQL执行失败，这个值是 `null`。

## 配置和连接

### `__construct(array $cfg)`
> 初始化本类，使用 `$cfg` 传入配置参数。常用的数据库的dsn可以参阅 [Drivers.md](Drivers.md)。

### `connect()`
> 开始连接数据库。

### `disconnect()`
> 断开数据库连接。

### `isConnected($strict_mode = false)`
> 检查当前是否已连接。如果启用严格模式，则还会实际执行一条`SELECT 1`的语句，用来检查连接是否能正常用。

## 执行SQL语句

### `query($sql, $data = null)`
> 执行一条查询类的SQL语句。如果成功，返回一个PDOStatement对象；如果失败，返回 `false`。

###`execute($sql, $data = null)`
> 执行一条变更类的SQL语句。如果成功，返回`true`，并将影响的行数写到`rowsAffected`属性；如果失败，返回`false`，并将`rowsAffected`属性设置为`null`。

### 注意事项

> 1. 如果`$data`为`null`，则执行普通SQL查询；如果`$data`是一个数组，则以prepare方式执行查询，并将`$data`作为参数传入。
> 2. PDO不允许在$sql中同时用`问号参数`和`命名参数`，只能二选一。



