# Common PDO Driver Examples

[TOC]

## MySQL/MariaDB

### General mode

```php
[
    'dsn'      => 'mysql:host=localhost;port=3306;dbname=db_name',
    'username' => 'db_username',
    'password' => 'db_password',
    'options'  => [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => true
    ],
]
```

### Unix socket mode

```php
[
    'dsn'      => 'mysql:unix_socket=/tmp/mysql.sock;dbname=db_name',
    'username' => 'db_username',
    'password' => 'db_password',
    'options'  => [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
]
```

## SQLite

### SQLite 3

```php
[
    'dsn' => 'sqlite:/opt/databases/mydb.sq3',
]
```

### SQLite 3 memory database

```php
[
    'dsn' => 'sqlite::memory:',
]
```

### SQLite 2

```php
[
    'dsn' => 'sqlite2:/opt/databases/mydb.sq2',
]
```

### SQLite 2 memory database

```php
[
    'dsn' => 'sqlite2::memory:',
]
```

## MS SQL Server (Windows)

### host

```php
[
    'dsn' => 'sqlsrv:Server=localhost;Database=testdb',
    'username' => 'db_username',
    'password' => 'db_password',
]
```

### host and port

```php
[
    'dsn' => 'sqlsrv:Server=localhost,1521;Database=testdb',
    'username' => 'db_username',
    'password' => 'db_password',
]
```

### remote host

```php
[
    'dsn' => 'sqlsrv:Server=12345abcde.database.windows.net;Database=testdb',
    'username' => 'db_username',
    'password' => 'db_password',
]
```

## MS SQL Server (Linux)

```php
[
    'dsn' => 'dblib:host=localhost:1512;dbname=testdb;charset=utf8',
    'username' => 'db_username',
    'password' => 'db_password',
]
```

## Oracle

```php
[
    'dsn' => 'oci:dbname=//db.example.com:1521/testdb',
    'username' => 'db_username',
    'password' => 'db_password',
]
```

## PostgreSQL

```php
[
    'dsn' => 'pgsql:host=db.example.com;port=31075;dbname=testdb;',
    'username' => 'db_username',
    'password' => 'db_password',
]
```

## ODBC

```php
[
    'dsn' => 'odbc:Driver={Microsoft Access Driver (*.mdb)};DBQ=D:\\data\\testdb.mdb;Uid=admin',
]
```

