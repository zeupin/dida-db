# dida-db

Dida\Db is a wrapper for PDO and PDOStatement. It's a part of Dida Framework.

It extends PDO and PDOStatement classes and adds some advanced features such as SQL query builder, chain-like function call and so on.

* Supports various SQL database, including MySQL, MariaDB, MSSQL, Oracle, PostgreSQL, SQLite and more.
* Friendly MIT License.
* Lazy database connection. Connects to the database only on method calls that require a connection. You can create an instance and not incur the cost of a connection if you never make a query.


## Requires

* PHP: ^5.4 || ^7.0
* Make sure the relative database systems are correctly installed and work well.
* Make sure php_pdo_xxx extensions are correctly installed and enabled.

## Installation

### Composer require

```
composer require dida/db
```

### Update

```
composer update
```

## Documents

* [API](docs/README.md)


## Authors

* [Macc Liu](https://github.com/maccliu)

## Credits

* [Zeupin LLC](http://zeupin.com) , especially [Dida Team](http://dida.zeupin.com)

## License

Copyright (c) 2017 Zeupin LLC. Released under the [MIT license](LICENSE).