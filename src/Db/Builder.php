<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \PDO;
use \Exception;

/**
 * Builder
 */
abstract class Builder
{
    /*
     * ------------------------------------------------------------
     * Class common variables
     *
     * 类的公用变量
     * ------------------------------------------------------------
     */
    /**
     * @var \Dida\Db
     */
    protected $db = null;
    protected $pdo_default_fetch_mode = null;

    /* table and its defination */
    protected $table = null;
    protected $table_alias = null;
    protected $prefix = null;
    protected $fsql_prefix = '###_'; /* faked sql */
    protected $def = null;
    protected $def_columns = null;
    protected $def_basetype = [];

    /* prepare mode */
    protected $preparemode = true;  // default TRUE

    /*
     * ------------------------------------------------------------
     * Operation variables
     *
     * 和本次操作相关的内部变量
     * ------------------------------------------------------------
     */

    /* build */
    protected $builded = false;

    /* verb */
    protected $verb = 'SELECT';

    /* WHERE */
    protected $where_changed = true;
    protected $where_parts = [];
    protected $where_expression = '';
    protected $where_parameters = [];

    /* SELECT */
    protected $select_columnlist = [];
    protected $select_columnlist_expression = '';
    protected $select_distinct = false;
    protected $select_distinct_expression = '';
    protected $select_orderby_columns = '';
    protected $select_orderby_expression = '';
    protected $join = [];
    protected $join_expression = '';
    protected $groupby_columnlist = [];
    protected $groupby_expression = '';
    protected $having_conditions = [];
    protected $having_logic = '';
    protected $having_expression = '';

    /* INSERT */
    protected $insert_columns = [];
    protected $insert_record = [];
    protected $insert_expression = '';
    protected $insert_parameters = [];

    /* UPDATE */
    protected $update_set = [];
    protected $update_set_expression = '';
    protected $update_set_parameters = [];

    /* final sql */
    public $sql = '';
    public $sql_parameters = [];

    /*
     * ------------------------------------------------------------
     * Execution result variables.
     * ------------------------------------------------------------
     */

    /* execution's result */
    public $rowCount = null;

    /*
     * ------------------------------------------------------------
     * SQL template variables.
     *
     * 各种SQL操作的模板变量。
     * ------------------------------------------------------------
     */

    /* SELECT template */
    protected $SELECT_expression = [
        0          => 'SELECT ',
        'distinct' => '',
        'columns'  => '',
        1          => ' FROM ',
        'table'    => '',
        'join'     => '',
        'where'    => '',
        'groupby'  => '',
        'having'   => '',
        'orderby'  => '',
        'limit'    => '',
        'union'    => '',
    ];
    protected $SELECT_parameters = [
        'columns' => [],
        'table'   => [],
        'join'    => [],
        'where'   => [],
        'groupby' => [],
        'having'  => [],
        'orderby' => [],
        'limit'   => [],
        'union'   => [],
    ];

    /* INSERT template */
    protected $INSERT_expression = [
        0         => 'INSERT INTO ',
        'table'   => '',
        'columns' => '',
        1         => ' VALUES ',
        'values'  => '',
    ];

    /* UPDATE template */
    protected $UPDATE_expression = [
        0       => 'UPDATE ',
        'table' => '',
        1       => ' SET ',
        'set'   => '',
        'join'  => '',
        'where' => '',
    ];
    protected $UPDATE_parameters = [
        'set'   => [],
        'join'  => [],
        'where' => [],
    ];

    /* DELETE template */
    protected $DELETE_expression = [
        0       => 'DELETE FROM ',
        'table' => '',
        'join'  => '',
        'where' => '',
    ];
    protected $DELETE_parameters = [
        'join'  => [],
        'where' => [],
    ];

    /* EXISTS template */
    protected $EXISTS_expression = [
        0      => 'SELECT EXISTS (',
        'expr' => '',
        1      => ')',
    ];

    /*
     * All supported operater set.
     *
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

    /*
     * ------------------------------------------------------------
     * Class constants.
     *
     * 类常量
     * ------------------------------------------------------------
     */

    /* constants used by UPDATE set***() */
    const SET_VALUE = 'set value';
    const SET_EXPRESSION = 'set expression';
    const SET_FROM_TABLE = 'set from table';


    abstract protected function quoteTableName($table);


    abstract protected function quoteColumnName($column);


    abstract protected function quoteString($value);


    abstract protected function quoteTime($value);


    /**
     * @param \Dida\Db $db
     * @param string $table
     */
    public function __construct($db, $table, $prefix = '', $fsql_prefix = '###_')
    {
        $this->db = $db;
        $this->pdo_default_fetch_mode = $this->db->pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

        $this->table = $prefix . $table;
        $this->prefix = $prefix;
        $this->fsql_prefix = $fsql_prefix;

        $this->def = include($db->workdir . '~SCHEMA' . DIRECTORY_SEPARATOR . $this->table . '.php');
        $this->def_columns = array_keys($this->def['COLUMNS']);
        $this->def_basetype = array_column($this->def['COLUMNS'], 'BASE_TYPE', 'COLUMN_NAME');

        $this->reset();
    }


    /**
     * Resets all operation variables.
     *
     * 重置所有操作变量到初始状态。
     * 不包括：
     * 1. $preparemode。一般不会出现有些网页要prepare，另外一些不要prepare。可通过prepare()改。
     * 2. prefix和fsql_prefix。一般类初始化后不会变。可通过prefixConfig()改。
     * 3. alias。可以通过alias()改。
     */
    public function reset()
    {
        /* build */
        $this->builded = false;

        /* verb */
        $this->verb = 'SELECT';

        /* WHERE */
        $this->where_changed = true;
        $this->where_parts = [];
        $this->where_expression = '';
        $this->where_parameters = [];

        /* SELECT */
        $this->select_columnlist = [];
        $this->select_columnlist_expression = '';
        $this->select_distinct = false;
        $this->select_distinct_expression = '';
        $this->select_orderby_columns = '';
        $this->select_orderby_expression = '';
        $this->join = [];
        $this->join_expression = '';
        $this->groupby_columnlist = [];
        $this->groupby_expression = '';
        $this->having_conditions = [];
        $this->having_logic = '';
        $this->having_expression = '';

        /* INSERT */
        $this->insert_columns = [];
        $this->insert_record = [];
        $this->insert_expression = '';
        $this->insert_parameters = [];

        /* UPDATE */
        $this->update_set = [];
        $this->update_set_expression = '';
        $this->update_set_parameters = [];

        /* final sql */
        $this->sql = '';
        $this->sql_parameters = [];
    }


    public function prepare($flag = true)
    {
        $this->preparemode = $flag;

        return $this;
    }


    public function prefixConfig($prefix = '', $fsql_prefix = '###_')
    {
        $this->prefix = $prefix;
        $this->fsql_prefix = $fsql_prefix;

        return $this;
    }


    public function alias($alias)
    {
        $this->table_alias = $alias;

        return $this;
    }


    /**
     * Specify a WHERE condition.
     */
    public function where($condition, $parameters = [])
    {
        $this->whereChanged();

        $part = $this->cond($condition, $parameters);
        $this->where_parts[] = $part;

        return $this;
    }


    /**
     * Sets many WHERE conditions at one time.
     *
     * @param array $conditions  The conditions array like:
     *      [
     *          [$column, $op, $data],
     *          [$column, $op, $data],
     *          [$column, $op, $data],
     *      ]
     * @param string $logic
     */
    public function whereMany(array $conditions, $logic = 'AND')
    {
        $this->whereChanged();

        $parts = [];
        foreach ($conditions as $condition) {
            $parts[] = $this->cond($condition);
        }
        $part = $this->combineConditionParts($parts, $logic);
        $this->where_parts[] = $part;

        return $this;
    }


    public function whereMatch(array $array)
    {
        $this->whereChanged();

        $parts = [];

        foreach ($array as $column => $value) {
            $parts[] = $this->cond_EQ($column, '=', $value);
        }

        $this->where_parts[] = $this->combineConditionParts($parts, 'AND');

        return $this;
    }


    protected function whereChanged()
    {
        $this->where_changed = true;

        $this->buildChanged();
    }


    public function distinct($flag = true)
    {
        $this->buildChanged();

        $this->select_distinct = $flag;

        if ($flag) {
            $this->select_distinct_expression = 'DISTINCT ';
        } else {
            $this->select_distinct_expression = '';
        }

        return $this;
    }


    protected function joinCommon($joinType, $table, $colA, $rel, $colB)
    {
        $this->buildChanged();

        $tpl = [
            ' ',
            'join type' => $joinType,
            ' ',
            'table'     => $this->tableNormalize($table),
            ' ON ',
            'colA'      => $this->columnNormalize($colA),
            ' ',
            'rel'       => $rel,
            ' ',
            'colB'      => $this->columnNormalize($colB),
        ];
        $this->join[] = implode('', $tpl);

        return $this;
    }


    public function join($table, $colA, $rel, $colB)
    {
        return $this->joinCommon('INNER JOIN', $table, $colA, $rel, $colB);
    }


    public function innerJoin($table, $colA, $rel, $colB)
    {
        return $this->joinCommon('INNER JOIN', $table, $colA, $rel, $colB);
    }


    public function leftJoin($table, $colA, $rel, $colB)
    {
        return $this->joinCommon('LEFT JOIN', $table, $colA, $rel, $colB);
    }


    public function rightJoin($table, $colA, $rel, $colB)
    {
        return $this->joinCommon('RIGHT JOIN', $table, $colA, $rel, $colB);
    }


    public function groupBy(array $columnlist)
    {
        $this->buildChanged();

        $this->groupby_columnlist = $columnlist;

        return $this;
    }


    public function having($condition, $logic = null)
    {
        $this->buildChanged();

        if ($logic && is_string($logic)) {
            $this->having_conditions = $condition;
            $this->having_logic = " $logic ";
        } else {
            $this->having_conditions[] = $condition;
            $this->having_logic = '';
        }

        return $this;
    }


    public function select(array $columnlist = [])
    {
        $this->buildChanged();

        $this->verb = 'SELECT';
        $this->select_columnlist = $columnlist;

        return $this;
    }


    public function count($columns = ['*'], $alias = null)
    {
        $this->buildChanged();

        $this->verb = 'SELECT';

        $list = $this->makeColumnList($columns);

        if (is_string($alias)) {
            $this->select_columnlist = [$alias => "COUNT($list)"];
        } else {
            $this->select_columnlist = ["COUNT($list)"];
        }

        return $this;
    }


    /**
     *
     * @param string $columns  name, id DESC
     * @param array  $columns  ['name' => '', 'id' => 'DESC']
     */
    public function orderBy($columns = '')
    {
        $this->buildChanged();

        if (is_array($columns)) {
            $std = [];
            foreach ($columns as $key => $item) {
                if (is_numeric($key)) {
                    $column = $item;
                    $std[$column] = $this->makeColumn($column);
                } else {
                    $order = strtoupper($item);
                    $column = $key;
                    if ($order === 'ASC' || $order === '') {
                        $order = '';
                    }
                    if ($order === 'DESC') {
                        $column_quoted = $this->makeColumn($column);
                        $std[$column] = "$column_quoted DESC";
                    } else {
                        throw new Exception("Invalid ORDERBY expression: $column $order");
                    }
                }
            }
            $this->select_orderby_columns = implode(', ', $std);
            return $this;
        } elseif (is_string($columns)) {
            $this->select_orderby_columns = $this->makeColumn($columns);
        }

        return $this;
    }


    public function insert(array $record)
    {
        $this->buildChanged();

        $this->verb = 'INSERT';
        $this->insert_record = $record;

        return $this;
    }


    public function delete()
    {
        $this->buildChanged();

        $this->verb = 'DELETE';

        return $this;
    }


    public function truncate()
    {
        $this->buildChanged();

        $this->verb = 'TRUNCATE';

        return $this;
    }


    public function update()
    {
        $this->buildChanged();

        $this->verb = 'UPDATE';

        return $this;
    }


    public function set($column, $new_value)
    {
        $this->buildChanged();

        $this->update_set[$column] = [Builder::SET_VALUE, $column, $new_value];

        return $this;
    }


    public function setExpr($column, $expr, $parameters = [])
    {
        $this->buildChanged();

        $this->update_set[$column] = [Builder::SET_EXPRESSION, $column, $expr, $parameters];

        return $this;
    }


    /**
     * Set column from other table.
     *
     * 常用的一种UPDATE写法：
     * UPDATE tableA
     * SET
     *     columnA = (SELECT tableB.columnB FROM tableB WHERE tableA.colA = tableB.colB)
     * WHERE
     *     EXISTS (SELECT tableB.columnB FROM tableB WHERE tableA.colA = tableB.colB)
     *
     * 1. 一般还要和UPDATE的where条件的EXISTS子查询配合用，因为如果在tableB中找不到记录，会把columnA设置为null。
     * 2. $tableB 可以写为 “tablename AS alias” 的形式。
     */
    public function setFromTable($columnA, $tableB, $columnB, $colA, $colB, $checkExistsInWhere = true)
    {
        $this->buildChanged();

        $s = $this->splitNameAlias($tableB);
        $tableB = $s['name'];
        $aliasB = $s['alias'];

        $tableBFullname_quoted = $this->makeTableFullname($tableB, $aliasB);

        $refA_quoted = $this->makeTableRef($this->table, $this->table_alias);
        $refB_quoted = $this->makeTableRef($tableB, $aliasB);

        $columnB_quoted = $this->makeColumn($columnB);
        $colA_quoted = $this->makeColumn($colA);
        $colB_quoted = $this->makeColumn($colB);

        $tpl = [
            '(SELECT ',
            'tableB.columnB' => "$refB_quoted.$columnB_quoted",
            ' FROM ',
            'tableB'         => $tableBFullname_quoted,
            ' WHERE ',
            'tableA.colA'    => "$refA_quoted.$colA_quoted",
            ' = ',
            'tableB.colB'    => "$refB_quoted.$colB_quoted",
            ')',
        ];
        $expression = implode('', $tpl);

        $this->update_set[$columnA] = [Builder::SET_EXPRESSION, $columnA, $expression, []];

        if ($checkExistsInWhere) {
            $this->where(["EXISTS $expression", 'RAW', []]);
        }

        return $this;
    }


    /**
     * 指定列自增一个值，默认自增1。
     *
     * @param string $column
     * @param mixed $value
     */
    public function inc($column, $value = 1)
    {
        $this->buildChanged();

        $this->verb = 'UPDATE';

        $column_quoted = $this->makeColumnFullname($column);
        $plus = ($value < 0) ? '' : '+'; // 正数和零要显示加号

        $this->update_set[$column] = [Builder::SET_EXPRESSION, $column, "$column_quoted{$plus}$value"];

        return $this;
    }


    public function build()
    {
        if ($this->builded) {
            return $this;
        }

        switch ($this->verb) {
            case 'SELECT':
                $this->build_SELECT();
                break;
            case 'UPDATE':
                $this->build_UPDATE();
                break;
            case 'INSERT':
                $this->build_INSERT();
                break;
            case 'DELETE':
                $this->build_DELETE();
                break;
            case 'TRUNCATE':
                $this->build_TRUNCATE();
                break;
            default:
                throw new Exception('Unknown verb "' . $this->verb . '"');
        }

        $this->builded = true;
        return $this;
    }


    /**
     * Builds the WHERE expression.
     */
    protected function build_WHERE()
    {
        // if not changed, do nothing
        if (!$this->where_changed) {
            return;
        }

        // combine the where_parts
        $where = $this->combineConditionParts($this->where_parts, 'AND');

        if ($where['expression'] === '') {
            $this->where_expression = '';
            $this->where_parameters = [];
        } else {
            $this->where_expression = " WHERE " . $where['expression'];
            $this->where_parameters = $where['parameters'];
        }

        // build completed
        $this->where_changed = false;
    }


    protected function build_SELECT()
    {
        $this->build_SELECT_COLUMNS();
        $this->build_JOIN();
        $this->build_WHERE();
        $this->build_GROUP_BY();
        $this->build_HAVING();
        $this->build_ORDER_BY();

        $expression = [
            'table'    => $this->makeTableFullname($this->table, $this->table_alias),
            'distinct' => $this->select_distinct_expression,
            "columns"  => $this->select_columnlist_expression,
            'join'     => $this->join_expression,
            'where'    => $this->where_expression,
            'groupby'  => $this->groupby_expression,
            'having'   => $this->having_expression,
            'orderby'  => $this->select_orderby_expression,
        ];
        $expression = array_merge($this->SELECT_expression, $expression);
        $this->sql = implode('', $expression);

        $parameters = [
            'where' => $this->where_parameters,
        ];
        $parameters = array_merge($this->SELECT_parameters, $parameters);
        $this->sql_parameters = $this->combineParameterArray($parameters);
    }


    protected function build_SELECT_COLUMNS()
    {
        if (empty($this->select_columnlist)) {
            if (empty($this->join)) {
                $ref = ($this->table_alias && is_string($this->table_alias)) ? $this->table_alias : $this->table;
                $columnlist = $this->convertSimpleColumnsToFullnames($ref, $this->def_columns);
                $this->select_columnlist_expression = $this->makeColumnList($columnlist);
            } else {
                $this->select_columnlist_expression = $this->makeColumnList($this->def_columns);
            }
        } else {
            $this->select_columnlist_expression = $this->makeColumnList($this->select_columnlist);
        }
    }


    protected function build_JOIN()
    {
        $this->join_expression = implode('', $this->join);
    }


    protected function build_GROUP_BY()
    {
        $expr = $this->makeColumnList($this->groupby_columnlist);
        if ($expr === '') {
            $this->groupby_expression = '';
        } else {
            $this->groupby_expression = ' GROUP BY ' . $expr;
        }
    }


    protected function build_HAVING()
    {
        $expr = implode($this->having_logic, $this->having_conditions);
        if ($expr === '') {
            $this->having_expression = '';
        } else {
            $this->having_expression = ' HAVING ' . $expr;
        }
    }


    protected function build_ORDER_BY()
    {
        if ($this->select_orderby_columns === '') {
            $this->select_orderby_expression = '';
        } else {
            $this->select_orderby_expression = ' ORDER BY ' . $this->select_orderby_columns;
        }
    }


    protected function build_INSERT()
    {
        $record = $this->insert_record;

        $columns = array_keys($record);
        $columns_expression = '(' . $this->makeColumnList($columns) . ')';

        $values = [];
        if ($this->preparemode) {
            $values_expression = '(' . implode(', ', array_fill(0, count($record), '?')) . ')';
            $values_parameters = array_values($record);
        } else {
            foreach ($record as $column => $value) {
                $values[$column] = $this->quoteColumnValue($column, $value);
            }
            $values_expression = '(' . implode(', ', $values) . ')';
            $values_parameters = [];
        }

        $expression = [
            'table'   => $this->quoteTableName($this->table),
            "columns" => $columns_expression,
            'values'  => $values_expression,
        ];
        $expression = array_merge($this->INSERT_expression, $expression);

        $this->sql = implode('', $expression);
        $this->sql_parameters = $values_parameters;
    }


    protected function build_DELETE()
    {
        $this->build_WHERE();

        $expression = [
            'table' => $this->quoteTableName($this->table),
            'where' => $this->where_expression,
        ];
        $expression = array_merge($this->DELETE_expression, $expression);
        $this->sql = implode('', $expression);

        $parameters = [
            'where' => $this->where_parameters,
        ];
        $parameters = array_merge($this->DELETE_parameters, $parameters);
        $this->sql_parameters = $this->combineParameterArray($parameters);
    }


    protected function build_TRUNCATE()
    {
        $this->sql = 'TRUNCATE TABLE ' . $this->quoteTableName($this->table);
        $this->sql_parameters = [];
    }


    protected function build_UPDATE()
    {
        $this->build_WHERE();
        $this->build_UPDATE_SET();

        // build expression
        $expression = [
            'table' => $this->quoteTableName($this->table),
            'set'   => $this->update_set_expression,
            'join'  => '',
            'where' => $this->where_expression,
        ];
        $expression = array_merge($this->UPDATE_expression, $expression);
        $this->sql = implode('', $expression);

        // build parameters
        $parameters = [
            'set'   => $this->update_set_parameters,
            'where' => $this->where_parameters,
        ];
        $parameters = array_merge($this->UPDATE_parameters, $parameters);
        $this->sql_parameters = $this->combineParameterArray($parameters);
    }


    protected function build_UPDATE_SET()
    {
        $expression = [];
        $parameters = [];

        foreach ($this->update_set as $item) {
            list($type, $column) = $item;
            $column_quoted = $this->makeColumn($column);

            switch ($type) {
                case Builder::SET_VALUE:
                    list($type, $column, $new_value) = $item;
                    if ($this->preparemode) {
                        $expression[] = $column_quoted . ' = ?';
                        $parameters[] = $new_value;
                    } else {
                        $new_value = $this->quoteColumnValue($column, $new_value);
                        $expression[] = $column_quoted . ' = ' . $new_value;
                        $parameters = [];
                    }
                    break;

                case Builder::SET_EXPRESSION:
                    switch (count($item)) {
                        case 3:
                            list($type, $column, $expr) = $item;
                            $param = [];
                            break;
                        case 4:
                            list($type, $column, $expr, $param) = $item;
                            break;
                        default:
                            throw new Exception('Invalid parameters number');
                    }
                    if ($this->preparemode) {
                        $expression[] = $column_quoted . ' = ' . $expr;
                        $parameters = array_merge($parameters, $param);
                    } else {
                        $expression[] = $column_quoted . ' = ' . $expr;
                        $parameters = [];
                    }
                    break;
            }
        }

        $this->update_set_expression = implode(', ', $expression);
        $this->update_set_parameters = $parameters;
    }


    protected function buildChanged()
    {
        $this->builded = false;
    }


    /**
     * Executes a DELETE, INSERT, or UPDATE statement (with the parameters).
     *
     * 执行变更数据类的SQL指令。
     *
     * @return bool  true on success, false on failure.
     */
    public function go()
    {
        // pre-processing
        switch ($this->verb) {
            case 'INSERT':
            case 'UPDATE':
            case 'DELETE':
            case 'TRUNCATE':
                $this->rowCount = null;
                break;
            default:
                throw new Exception("Illegal verb type \"$this->verb\", expects INSERT/UPDATE/DELETE/TRUNCATE.");
        }

        // build
        $this->build();

        // execute
        try {
            if (count($this->sql_parameters)) {
                $stmt = $this->db->pdo->prepare($this->sql);
                if ($stmt->execute($this->sql_parameters)) {
                    $this->rowCount = $stmt->rowCount();
                } else {
                    return false;
                }
            } else {
                $result = $this->db->pdo->exec($this->sql);
                if ($result === false) {
                    return false;
                } else {
                    $this->rowCount = $result;
                }
            }
        } catch (Exception $ex) {
            $this->db->pdoexception = $ex;
            return false;
        }

        // return true on success
        return true;
    }


    /**
     * Fetches a record from the result set.
     *
     * 执行一个查询操作，返回匹配的下一条记录。
     */
    public function fetch($fetch_style = null)
    {
        // check verb
        $this->checkVerbQuery();

        // build
        $this->build();

        if (is_null($fetch_style)) {
            $fetch_style = $this->pdo_default_fetch_mode;
        }
        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                return $stmt->fetch($fetch_style);
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                return $stmt->fetch($fetch_style);
            }
        }
    }


    /**
     * Fetches all records from a result set.
     *
     * 执行一个查询操作，返回所有获取的记录。
     */
    public function fetchAll($fetch_style = null)
    {
        // check verb
        $this->checkVerbQuery();

        // build
        $this->build();

        if (is_null($fetch_style)) {
            $fetch_style = $this->pdo_default_fetch_mode;
        }
        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                return $stmt->fetchAll($fetch_style);
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                return $stmt->fetchAll($fetch_style);
            }
        }
    }


    /**
     * Returns a column value of the next record.
     *
     * 返回查询操作的下一行的第n列的值，默认是取第1列的值。
     * 如果返回多列，可指定要取的列的序号，第1列的序号是0。
     *
     * @param int $column_number
     * @return mixed 失败返回false，成功返回字符串。
     */
    public function value($column_number = 0)
    {
        // check verb
        $this->checkVerbQuery();

        // build
        $this->build();

        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                return $stmt->fetchColumn($column_number);
            }
        } else {
            $stmt = $this->db->pdo->prepare($this->sql);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                return $stmt->fetchColumn($column_number);
            }
        }
    }


    /**
     * Checks records exists.
     *
     * 检查当前的SELECT语句的是否能找到至少一条数据。
     *
     * @return boolean  有数据返回true，没有数据返回false
     */
    public function exists()
    {
        // check verb
        $this->checkVerbQuery();

        // build
        $this->build();

        $tpl = [
            'SELECT EXISTS (',
            $this->sql,
            ')',
        ];
        $sql = implode('', $tpl);

        if (count($this->sql_parameters) === 0) {
            $stmt = $this->db->pdo->query($sql);
            if ($stmt === false) {
                return false;
            } else {
                $result = $stmt->fetchColumn();
            }
        } else {
            $stmt = $this->db->pdo->prepare($sql);
            if ($stmt === false) {
                return false;
            } else {
                $stmt->execute($this->sql_parameters);
                $result = $stmt->fetchColumn();
            }
        }

        return ($result === '1');
    }


    protected function cond($condition, $parameters = [])
    {
        if (is_string($condition)) {
            $part = [
                'expression' => $condition,
                'parameters' => $parameters,
            ];
            return $part;
        }

        if (!is_array($condition)) {
            throw new Exception("Invalid condition as " . var_export($condition, true));
        }

        // check condition is valid
        $cnt = count($condition);
        if ($cnt === 3) {
            list($column, $op, $data) = $condition;
        } elseif ($cnt === 2) {
            // isnull, isnotnull
            list($column, $op) = $condition;
            $data = null;
        } elseif ($cnt === 4) {
            // between
            list($column, $op, $data1, $data2) = $condition;
            $data = [$data1, $data2];
        } else {
            throw new Exception("Invalid condition as " . var_export($condition, true));
        }

        // Checks whether $op is valid.
        $op = strtoupper($op);
        if (!array_key_exists($op, self::$opertor_set)) {
            throw new Exception("Invalid operator \"$op\" in condition " . var_export($condition, true));
        }

        // calls the 'cond_*' function
        $method_name = 'cond_' . self::$opertor_set[$op];
        return $this->$method_name($column, $op, $data);
    }


    protected function cond_RAW($column, $op, $data)
    {
        return [
            'expression' => $column,
            'parameters' => $data,
        ];
    }


    protected function cond_COMPARISON($column, $op, $data)
    {
        $tpl = [
            '(',
            'column' => $this->makeColumn($column),
            'op'     => " $op ",
            'value'  => '',
            ')'
        ];

        $expression = '';
        $parameters = [];

        if ($this->preparemode) {
            $tpl['value'] = '?';
            $expression = implode('', $tpl);
            $parameters[] = $data;
            $part = [
                'expression' => $expression,
                'parameters' => $parameters,
            ];
            return $part;
        }

        $tpl['value'] = $this->quoteColumnValue($column, $data);

        $expression = implode('', $tpl);
        $part = [
            'expression' => $expression,
            'parameters' => [],
        ];
        return $part;
    }


    protected function cond_EQ($column, $op, $data)
    {
        if (is_array($data)) {
            return $this->cond_IN($column, 'IN', $data);
        }

        return $this->cond_COMPARISON($column, '=', $data);
    }


    protected function cond_GT($column, $op, $data)
    {
        return $this->cond_COMPARISON($column, '>', $data);
    }


    protected function cond_LT($column, $op, $data)
    {
        return $this->cond_COMPARISON($column, '<', $data);
    }


    protected function cond_EGT($column, $op, $data)
    {
        return $this->cond_COMPARISON($column, '>=', $data);
    }


    protected function cond_ELT($column, $op, $data)
    {
        return $this->cond_COMPARISON($column, '<=', $data);
    }


    protected function cond_NEQ($column, $op, $data)
    {
        if (is_array($data)) {
            return $this->cond_NOTIN($column, $op, $data);
        }

        return $this->cond_COMPARISON($column, '<>', $data);
    }


    protected function cond_IN($column, $op, $data)
    {
        if (empty($data)) {
            throw new Exception('An empty array not allowed use in a IN expression');
        }

        $tpl = [
            '(',
            'column' => $this->makeColumn($column),
            'op'     => " $op ",
            '(',
            'list'   => '',
            '))'
        ];
        $expression = '';
        $parameters = [];

        if ($this->preparemode) {
            $marks = array_fill(0, count($data), '?');
            $tpl['list'] = implode(', ', $marks);
            $expression = implode('', $tpl);
            $parameters = array_values($data);

            $part = [
                'expression' => $expression,
                'parameters' => $parameters,
            ];
            return $part;
        }

        $data[$key] = $this->quoteColumnValue($column, $value);
        $tpl['list'] = implode(', ', $data);

        $part = [
            'expression' => implode('', $tpl),
            'parameters' => [],
        ];
        return $part;
    }


    /**
     * Do not use this operator, which will greatly affect performance!
     */
    protected function cond_NOTIN($column, $op, $data)
    {
        return $this->cond_IN($column, 'NOT IN', $data);
    }


    protected function cond_LIKE($column, $op, $data)
    {
        $column_quoted = $this->makeColumn($column);
        $value_quoted = $this->quoteString($data);

        if ($this->preparemode) {
            $expression = "$column_quoted $op ?";
            $parameters[] = $data;
        } else {
            $expression = "$column_quoted $op $value_quoted";
            $parameters = [];
        }

        $part = [
            'expression' => $expression,
            'parameters' => $parameters,
        ];
        return $part;
    }


    protected function cond_NOTLIKE($column, $op, $data)
    {
        return $this->cond_LIKE($column, 'NOT LIKE', $data);
    }


    protected function cond_BETWEEN($column, $op, $data)
    {
        $expression = '';
        $parameters = [];

        $column_quoted = $this->makeColumn($column);

        $value1 = $data[0];
        $value2 = $data[1];
        $value1_quoted = $this->quoteColumnValue($column, $value1);
        $value2_quoted = $this->quoteColumnValue($column, $value2);

        if ($this->preparemode) {
            $expression = "$column_quoted $op ? AND ?";
            $parameters[] = $value1;
            $parameters[] = $value2;
        } else {
            $expression = "$column_quoted $op $value1_quoted AND $value2_quoted";
            $parameters = [];
        }

        $part = [
            'expression' => $expression,
            'parameters' => $parameters,
        ];
        return $part;
    }


    protected function cond_NOTBETWEEN($column, $op, $data)
    {
        return $this->cond_BETWEEN($column, 'NOT BETWEEN', $data);
    }


    protected function cond_ISNULL($column, $op, $data = null)
    {
        $column_quoted = $this->makeColumn($column);
        $part = [
            'expression' => "$column_quoted IS NULL",
            'parameters' => [],
        ];
        return $part;
    }


    protected function cond_ISNOTNULL($column, $op, $data = null)
    {
        $column_quoted = $this->makeColumn($column);
        $part = [
            'expression' => "$column_quoted IS NOT NULL",
            'parameters' => [],
        ];
        return $part;
    }


    protected function cond_EXISTS($column, $op, $data)
    {
        $sql = $this->fsql($column);

        $part = [
            'expression' => "EXISTS ($sql)",
            'parameters' => $data,
        ];
        return $part;
    }


    protected function cond_NOTEXISTS($column, $op, $data)
    {
        $sql = $this->fsql($column);

        $part = [
            'expression' => "NOT EXISTS ($sql)",
            'parameters' => $data,
        ];
        return $part;
    }


    public function lastInsertId($name = null)
    {
        return $this->db->pdo->lastInsertId($name);
    }


    protected function combineConditionParts($parts, $logic = 'AND')
    {
        $expression = array_column($parts, 'expression');
        $parameters = array_column($parts, 'parameters');

        $expression_cnt = count($expression);
        $expression = implode(" $logic ", $expression);
        if ($expression_cnt > 1) {
            $expression = "($expression)";
        }

        $parameters = $this->combineParameterArray($parameters);

        return [
            'expression' => $expression,
            'parameters' => $parameters,
        ];
    }


    protected function combineParameterArray(array $parameters)
    {
        $ret = [];
        foreach ($parameters as $array) {
            $ret = array_merge($ret, array_values($array));
        }
        return $ret;
    }


    protected function quoteColumnValue($column, $value)
    {
        if (!array_key_exists($column, $this->def_basetype)) {
            throw new Exception("Invalid column name `$column`");
        }

        switch ($this->def_basetype[$column]) {
            case 'string':
                return $this->quoteString($value);
            case 'time':
                return $this->quoteTime($value);
            case 'numeric':
                return $value;
            default:
                return $value;
        }
    }


    /**
     * Brackets a string value.
     *
     * 用小括号把一个非空字符串括起来。
     *
     * @param string $string
     */
    protected function bracket($string)
    {
        return ($string === '') ? '' : "($string)";
    }


    /**
     * Checks the verb is SELECT.
     *
     * 检查当前SQL类型是否是SELECT。
     * 成功返回true，失败抛异常。
     *
     * @return boolean
     */
    protected function checkVerbQuery()
    {
        switch ($this->verb) {
            case 'SELECT':
                return true;
            default:
                throw new Exception("Illegal verb type \"$this->verb\" found, expects SELECT.");
        }
    }


    /**
     * Converts a Faked SQL to a normal SQL.
     *
     * 把一个伪SQL片段转变为常规SQL片段，替换掉其中的伪变量(如：###_等)
     */
    protected function fsql($fsql)
    {
        $search = [];
        $replace = [];

        // prefix
        $search[] = $this->fsql_prefix;
        $replace[] = $this->prefix;

        // execute
        return str_replace($search, $replace, $fsql);
    }


    /**
     * Normalizes a table name expression.
     *
     * 把一个表名表达式进行标准化。
     * 支持两种格式：“tablename”和“tablename AS alias”
     *
     * @param string $table
     */
    protected function tableNormalize($table)
    {
        $s = trim($table);
        $temp = $this->splitNameAlias($s);

        return $this->makeTableFullname($temp['name'], $temp['alias']);
    }


    /**
     * Normalizes a column name expression.
     *
     * 把一个列名表达式进行标准化。
     * 支持两种格式：“column”和“column AS alias”
     *
     * @param string $column
     */
    protected function columnNormalize($column)
    {
        $s = trim($column);
        $array = $this->splitNameAlias($s);

        return $this->makeColumnFullname($array['name'], $array['alias']);
    }


    /**
     * Returns a quoted table expression.
     *
     * 返回一个quoted的表名表达式。
     *
     * @param string $table
     * @return string
     */
    protected function makeTable($table)
    {
        $table = trim($table);
        if ($table === '') {
            return '';
        }

        $table = $this->fsql($table);

        if ($this->isName($table)) {
            return $this->quoteTableName($table);
        } else {
            return $table;
        }
    }


    /**
     * If $alias is not null or ''， returns the quoted table alias expression.
     *
     * 如果Alias不为空，则返回quoted的alias表达式。为空则返回空串。
     *
     * @param string $alias
     * @return string
     */
    protected function makeTableAlias($alias)
    {
        $alias = trim($alias);
        if ($alias && is_string($alias)) {
            return $this->quoteTableName($alias);
        } else {
            return '';
        }
    }


    /**
     * Returns a SQL code snippet of a table name (with an alias).
     *
     * 返回一个表的名称表达式的代码片段。
     * 注意：表的Alias一定会被quote的。
     *
     * @param string $table
     * @param string $alias
     */
    protected function makeTableFullname($table, $alias = null)
    {
        $table_quoted = $this->makeTable($table);
        $alias_quoted = $this->makeTableAlias($alias);

        if ($alias_quoted) {
            return "$table_quoted $alias_quoted";
        } else {
            return $table_quoted;
        }
    }


    /**
     * Returns a SQL code snippet of a table list names (with aliases).
     *
     * 返回一个tablelist的quoted的表达式。
     *
     * @param array $tablelist
     * @return string
     */
    protected function makeTableList(array $tablelist)
    {
        $array = [];
        foreach ($tablelist as $alias => $table) {
            if ($alias && is_string($alias)) {
                $array[] = $this->makeTableFullname($table, $alias);
            } else {
                $array[] = $this->makeTable($table);
            }
        }
        return implode(', ', $array);
    }


    /**
     * Returns quoted alias when alias exists, else returns quoted table name.
     *
     * 返回某个表的具体指代者，有别名返回别名；没有别名则返回表名。
     *
     * @param string $table
     * @param string $alias
     * @return string
     */
    protected function makeTableRef($table, $alias = null)
    {
        if ($alias && is_string($alias)) {
            return $this->makeTable($alias);
        } else {
            return $this->makeTable($table);
        }
    }


    /**
     * Returns a quoted column name
     *
     * 返回一个quoted的列名
     *
     * @param string $column
     * @return string
     */
    protected function makeColumn($column)
    {
        $column = trim($column);
        if ($column === '') {
            return '';
        }

        $column = $this->fsql($column);

        /*
         * case "column"
         * 只有“列名”的情况
         */
        if ($this->isName($column)) {
            return $this->quoteColumnName($column);
        }

        /*
         * case "table.column"
         * “表名.列名”的情况
         */
        if ($this->isNameWithDot($column)) {
            $array = explode('.', $column);
            return $this->quoteTableName($array[0]) . '.' . $this->quoteColumnName($array[1]);
        }

        /*
         * case other
         * 其它情况不quote此列
         */
        return $column;
    }


    /**
     * If $alias is not null or ''， returns the quoted column alias expression.
     *
     * 如果Alias不为空，则返回quoted的alias表达式。
     *
     * @param string $alias
     * @return string
     */
    protected function makeColumnAlias($alias)
    {
        $alias = trim($alias);
        if ($alias && is_string($alias)) {
            return $this->quoteColumnName($alias);
        } else {
            return '';
        }
    }


    /**
     * Returns a column name expression snippet.
     *
     * 返回一个列名的quoted全名表达式。
     *
     * @param string $column
     * @param string $alias
     * @return string
     */
    protected function makeColumnFullname($column, $alias = null)
    {
        $column_quoted = $this->makeColumn($column);
        $alias_quoted = $this->makeColumnAlias($alias);

        if ($alias_quoted) {
            return "$column_quoted AS $alias_quoted";
        } else {
            return $column_quoted;
        }
    }


    /**
     * Returns a columnlist expression snippet
     *
     * 返回一个列名数组对应的columnlist表达式。
     * 输入的$columns只允许以如下格式：
     * [
     *      'alias'=>'column',   // 带alias的
     *               'column',   // 不带alias的
     * ]
     *
     * @param array $columns ['alias'=>'column', 'column',]
     * @return string
     */
    protected function makeColumnList(array $columns)
    {
        $array = [];
        foreach ($columns as $alias => $column) {
            if (is_string($alias)) {
                $array[] = $this->makeColumnFullname($column, $alias);
            } else {
                $array[] = $this->makeColumnFullname($column);
            }
        }

        return implode(', ', $array);
    }


    /**
     * Converts a table/column name string to an array of a specified format.
     *
     * 把一个表名或者列名字符串转换为标准格式待用。
     * 支持：“名称”、“名称 AS 别名”这两种形式。
     *
     * 考虑到“表名 别名”这种用法不易一眼识别。因此表名如果带别名，一律要求都用" as "显式指明。
     * 如“tb_user AS u”，其中AS的大小写没有关系，用AS、as或As都行。
     */
    protected function splitNameAlias($string)
    {
        $result = preg_split('/\s+(AS|as|As)\s+/', $string, 2);
        $name = $result[0];
        $alias = (isset($result[1])) ? $result[1] : null;

        return [
            'name'  => $name,
            'alias' => $alias,
        ];
    }


    /**
     * Tests the specified $name is a valid table/column name.
     *
     * 检查给出的名称字符串是否是一个可用作表名或列名的单词。
     * 标准是：以下划线或字母开头，后面跟若干个_、字母和数字。
     * 注意：执行本函数前，要先转换好 ###_tablename
     *
     * @param string $name
     * @return int   1 for yes, 0 for no
     */
    protected function isName($name)
    {
        return preg_match('/^[_A-Za-z]{1}\w*$/', $name);
    }


    /**
     * Tests the specified $name is a name splitted by a dot, like "tb_user.address"
     *
     * 检查给出的字符串是否是一个以点分隔的名字。
     * 主要用于检查列名是否是“表名称.列名称”这种形式。
     * 注意：执行本函数前，要先转换好 ###_tablename
     *
     * @param string $name
     * @return int   1 for yes, 0 for no
     */
    protected function isNameWithDot($name)
    {
        return preg_match('/^[_A-Za-z]{1}\w*\.[_A-Za-z]{1}\w*$/', $name);
    }


    /**
     * Converts simple name columns to full name columns.
     *
     * 把一个不含表名的columnlist转换为含表名的columnlist
     *
     * @param string $table
     * @param array $columns
     * @return array
     */
    protected function convertSimpleColumnsToFullnames($table, array $columns)
    {
        $new = [];
        foreach ($columns as $column) {
            $new[] = "$table.$column";
        }
        return $new;
    }
}
