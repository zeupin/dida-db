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
    /**
     * @var \Dida\Db
     */
    protected $db = null;
    protected $pdo_default_fetch_mode = null;

    /* table and its defination */
    protected $table = null;
    protected $table_alias = null;
    protected $prefix = null;
    protected $def = null;
    protected $def_columns = null;
    protected $def_basetype = [];

    /* prepare mode */
    protected $preparemode = true;  // default TRUE

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

    /* execution's result */
    public $rowCount = null;

    /* builder-sql */
    protected $bstr_prefix = '###_';

    /* class constants */
    const VALUE_COLUMN = 'value';
    const CALC_COLUMN = 'calc';
    const SELECT_COLUMN = 'select';

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


    /* 支持的SQL条件运算集 */
    protected static $opertor_set = [
        /* Raw SQL */
        'SQL'              => 'SQL',
        /* equal */
        'EQ'               => 'EQ',
        '='                => 'EQ',
        '=='               => 'EQ',
        /* not equal */
        'NEQ'              => 'NEQ',
        '<>'               => 'NEQ',
        '!='               => 'NEQ',
        /* <,>,<=,>= */
        'GT'               => 'GT',
        '>'                => 'GT',
        'EGT'              => 'EGT',
        '>='               => 'EGT',
        'LT'               => 'LT',
        '<'                => 'LT',
        'ELT'              => 'ELT',
        '<='               => 'ELT',
        /* LIKE */
        'LIKE'             => 'LIKE',
        'NOT LIKE'         => 'NOTLIKE',
        'NOTLIKE'          => 'NOTLIKE',
        /* IN */
        'IN'               => 'IN',
        'NOT IN'           => 'NOTIN',
        'NOTIN'            => 'NOTIN',
        /* BETWEEN */
        'BETWEEN'          => 'BETWEEN',
        'NOT BETWEEN'      => 'NOTBETWEEN',
        'NOTBETWEEN'       => 'NOTBETWEEN',
        /* EXISTS */
        'EXISTS'           => 'EXISTS',
        'NOT EXISTS'       => 'NOTEXISTS',
        'NOTEXISTS'        => 'NOTEXISTS',
        /* NULL */
        'ISNULL'           => 'ISNULL',
        'NULL'             => 'ISNULL',
        'ISNOTNULL'        => 'ISNOTNULL',
        'IS NOT NULL'      => 'ISNOTNULL',
        'NOTNULL'          => 'ISNOTNULL',
        'NOT NULL'         => 'ISNOTNULL',
        /* 时间类型字段的运算 */
        'TIME >'           => 'TIME_GT',
        'TIME <'           => 'TIME_LT',
        'TIME >='          => 'TIME_EGT',
        'TIME <='          => 'TIME_ELT',
        'TIME BETWEEN'     => 'TIME_BETWEEN',
        'TIME NOT BETWEEN' => 'TIME_NOTBETWEEN',
    ];


    abstract protected function quoteTableName($table);


    abstract protected function quoteColumnName($column);


    abstract protected function quoteString($value);


    abstract protected function quoteTime($value);


    /**
     * @param \Dida\Db $db
     * @param string $table
     */
    public function __construct($db, $table, $prefix = '')
    {
        $this->db = $db;
        $this->pdo_default_fetch_mode = $this->db->pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

        $this->table = $prefix . $table;
        $this->prefix = $prefix;

        $this->def = include($db->workdir . '~SCHEMA' . DIRECTORY_SEPARATOR . $this->table . '.php');
        $this->def_columns = array_keys($this->def['COLUMNS']);
        $this->def_basetype = array_column($this->def['COLUMNS'], 'BASE_TYPE', 'COLUMN_NAME');
    }


    public function prepare($flag = true)
    {
        $this->preparemode = $flag;

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


    public function whereEQ(array $array)
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


    public function join($table, $colA, $rel, $colB)
    {
        $this->buildChanged();

        $tpl = [
            ' INNER JOIN ',
            'table' => $this->bstrTable($table),
            ' ON ',
            'colA'  => $this->bstr($colA),
            ' ',
            'rel'   => $rel,
            ' ',
            'colB'  => $this->bstr($colB),
        ];
        $this->join[] = implode('', $tpl);

        return $this;
    }


    public function leftJoin($table, $colA, $rel, $colB)
    {
        $this->buildChanged();

        $tpl = [
            ' LEFT JOIN ',
            'table' => $this->bstrTable($table),
            ' ON ',
            'colA'  => $this->bstr($colA),
            ' ',
            'rel'   => $rel,
            ' ',
            'colB'  => $this->bstr($colB),
        ];
        $this->join[] = implode('', $tpl);

        return $this;
    }


    public function rightJoin($table, $colA, $rel, $colB)
    {
        $this->buildChanged();

        $tpl = [
            ' RIGHT JOIN ',
            'table' => $this->bstrTable($table),
            ' ON ',
            'colA'  => $this->bstr($colA),
            ' ',
            'rel'   => $rel,
            ' ',
            'colB'  => $this->bstr($colB),
        ];
        $this->join[] = implode('', $tpl);

        return $this;
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


    public function select(array $columns = [])
    {
        $this->buildChanged();

        $this->verb = 'SELECT';
        $this->select_columnlist = $columns;

        return $this;
    }


    public function count($columns = '*', $alias = null)
    {
        $this->buildChanged();

        $this->verb = 'SELECT';

        if (is_string($alias)) {
            $this->select_columnlist = ["COUNT($columns)" => $alias];
        } else {
            $this->select_columnlist = ["COUNT($columns)"];
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
            foreach ($columns as $column => $order) {
                if (is_numeric($column)) {
                    $column = $order;
                    $std[$column] = $this->quoteColumnName($column);
                } else {
                    $order = strtoupper($order);
                    if ($order === 'ASC') {
                        $order = '';
                    }
                    if ($order === '' || $order === 'DESC') {
                        $column_quoted = $this->quoteColumnName($column);
                        $std[$column] = ($order === '') ? $column_quoted : "$column_quoted $order";
                    } else {
                        throw new Exception("Invalid ORDERBY expression: $column $order");
                    }
                }
            }
            $this->select_orderby_columns = implode(', ', $std);
            return $this;
        } elseif (is_string($columns)) {
            $this->select_orderby_columns = $columns;
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

        $this->update_set[$column] = [Builder::VALUE_COLUMN, $column, $new_value];

        return $this;
    }


    public function setCalc($column, $expr, $parameters = [])
    {
        $this->buildChanged();

        $this->update_set[$column] = [Builder::CALC_COLUMN, $column, $expr, $parameters];

        return $this;
    }


    /**
     * column = (SELECT tableB.columnB FROM tableB WHERE table.colA = tableB.colB)
     */
    public function setFromSelect($column, $tableB, $columnB, $colA, $colB, $alias = null)
    {
        $this->buildChanged();

        $this->update_set[$column] = [Builder::SELECT_COLUMN, $column, $tableB, $columnB, $colA, $colB, $alias];

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
            'table'    => $this->bstrTable([$this->table, $this->table_alias]),
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
                $this->select_columnlist_expression = $this->makeColumnList($this->def_columns);
            } else {
                $t = ($this->table_alias === null) ? $this->table : $this->table_alias;
                $array = $this->def_columns;
                array_walk($array, function (&$item, $key) use ($t) {
                    $item = $this->quoteTableName($t) . '.' . $this->quoteColumnName($item);
                });

                $this->select_columnlist_expression = $this->makeColumnList($array);
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
            $values_expression = '(' . implode(',', array_fill(0, count($record), '?')) . ')';
            $values_parameters = array_values($record);
        } else {
            foreach ($record as $column => $value) {
                $values[$column] = $this->quoteColumnValue($column, $value);
            }
            $values_expression = '(' . implode(',', $values) . ')';
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
        $this->sql = 'TRUNCATE TABLE ' . $this->table;
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
            $column_quoted = $this->quoteColumnName($column);

            switch ($type) {
                case Builder::VALUE_COLUMN:
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

                case Builder::CALC_COLUMN:
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

                case Builder::SELECT_COLUMN:
                    list($type, $column, $tableB, $columnB, $colA, $colB, $alias) = $item;

                    $table_quoted = $this->quoteTableName($this->table);
                    $tableB_quoted = $this->quoteTableName($this->prefix . $tableB);
                    $columnB_quoted = $this->quoteColumnName($columnB);
                    $colA_quoted = $this->quoteColumnName($colA);
                    $colB_quoted = $this->quoteColumnName($colB);

                    if (is_null($alias)) {
                        $alias = $tableB_quoted;
                        $as = '';
                    } else {
                        $alias = $this->quoteTableName($alias);
                        $as = ' AS ' . $alias;
                    }

                    $tpl = [
                        'column'         => $column_quoted,
                        ' = (SELECT ',
                        'tableB.columnB' => "$alias.$columnB_quoted",
                        ' FROM ',
                        'tableB'         => $tableB_quoted,
                        'as'             => $as,
                        ' WHERE ',
                        'table.colA'     => "$table_quoted.$colA_quoted",
                        ' = ',
                        'tableB.colB'    => "$alias.$colB_quoted",
                        ')',
                    ];
                    $expression[] = implode('', $tpl);
                    $parameters = [];
                    break;
            }
        }

        $this->update_set_expression = implode(', ', $expression);
        $this->update_set_parameters = $parameters;
    }


    /**
     * Resolves a record to
     *
     * @param data $record
     * @param type $expression
     * @param type $parameters
     */
    protected function resolveRecord($record, &$expression, &$parameters)
    {
    }


    protected function buildChanged()
    {
        $this->builded = false;
    }


    /**
     * Executes a DELETE, INSERT, or UPDATE statement (with the parameters).
     *
     * @return bool  true on success, false on failure.
     */
    public function go()
    {
        $this->build();

        // pre-processing
        switch ($this->verb) {
            case 'INSERT':
            case 'UPDATE':
            case 'DELETE':
            case 'TRUNCATE':
                $this->rowCount = null;
                break;
            default:
                return false;
        }

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
     * Action: Fetches a record from a recordset.
     */
    public function fetch($fetch_style = null)
    {
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
     * Action: Fetches all records from a recordset.
     */
    public function fetchAll($fetch_style = null)
    {
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


    public function value($column_number = 0)
    {
        $this->build();

        if (is_null($fetch_style)) {
            $fetch_style = $this->pdo_default_fetch_mode;
        }
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
            list($column, $op) = $condition;
            $data = null;
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


    protected function cond_SQL($column, $op, $data)
    {
        return [
            'expression' => $data,
            'parameters' => [],
        ];
    }


    protected function cond_COMMON($column, $op, $data)
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

        return $this->cond_COMMON($column, '=', $data);
    }


    protected function cond_GT($column, $op, $data)
    {
        return $this->cond_COMMON($column, '>', $data);
    }


    protected function cond_LT($column, $op, $data)
    {
        return $this->cond_COMMON($column, '<', $data);
    }


    protected function cond_EGT($column, $op, $data)
    {
        return $this->cond_COMMON($column, '>=', $data);
    }


    protected function cond_ELT($column, $op, $data)
    {
        return $this->cond_COMMON($column, '<=', $data);
    }


    protected function cond_NEQ($column, $op, $data)
    {
        if (is_array($data)) {
            return $this->cond_NOTIN($column, $op, $data);
        }

        return $this->cond_COMMON($column, '<>', $data);
    }


    protected function cond_IN($column, $op, $data)
    {
        if (empty($data)) {
            throw new Exception('An empty array not allowed use in a IN expression');
        }

        $tpl = [
            '(',
            'column' => $this->quoteColumnName($column),
            'op'     => " $op ",
            '(',
            'list'   => '',
            '))'
        ];
        $expression = '';
        $parameters = [];

        if ($this->preparemode) {
            $marks = array_fill(0, count($data), '?');
            $tpl['list'] = implode(',', $marks);
            $expression = implode('', $tpl);
            $parameters = array_values($data);

            $part = [
                'expression' => $expression,
                'parameters' => $parameters,
            ];
            return $part;
        }

        $data[$key] = $this->quoteColumnValue($column, $value);
        $tpl['list'] = implode(',', $data);

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
        $column_quoted = $this->quoteColumnName($column);
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

        $column_quoted = $this->quoteColumnName($column);

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


    public function lastInsertId($name = null)
    {
        return $this->db->pdo->lastInsertId($name);
    }


    /**
     * Brackets a string value.
     *
     * @param string $string
     */
    public static function bracket($string)
    {
        return ($string === '') ? '' : "($string)";
    }


    protected function makeColumnList(array $columns)
    {
        $return = [];
        foreach ($columns as $key => $item) {
            if (is_int($key)) {
                $return[] = $this->makeColumn($item);
            } else {
                $return[] = $this->makeColumn($key, $item);
            }
        }
        return implode(', ', $return);
    }


    protected function makeColumn($column, $alias = null)
    {
        // column
        $column_quoted = '';
        $column = $this->bstr($column);
        if ($this->isName($column)) {
            // "column"
            $column_quoted = $this->quoteColumnName($column);
        } elseif ($this->isNameWithDot($column)) {
            // "table.column"
            $array = explode('.', $column);
            $column_quoted = $this->quoteTableName($array[0]) . '.' . $this->quoteColumnName($array[1]);
        } else {
            $column_quoted = $column;
        }

        // alias
        $alias_quoted = '';
        if (!is_string($alias) || $alias === '') {
            $alias_quoted = '';
        } else {
            $alias_quoted = ' AS ' . $this->quoteColumnName($alias);
        }

        // combine column and alias
        return $column_quoted . $alias_quoted;
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
     * Converts bstr SQL to real SQL
     */
    protected function bstr($bstr_sql)
    {
        $search = [];
        $replace = [];

        // prefix
        $search[] = $this->bstr_prefix;
        $replace[] = $this->prefix;

        // execute
        return str_replace($search, $replace, $bstr_sql);
    }


    /**
     * @param string $table  'table_name', 'table_name alias', 'table_name as alias'
     * @param array  $table  [tablename, alias]
     */
    protected function bstrTable($table)
    {
        $t = '';
        $as = '';

        if (is_string($table)) {
            $table = $this->splitNameAlias($table);
            $t = $table['name'];
            $as = (isset($table['alias'])) ? $table['alias'] : null;
        } elseif (is_array($table)) {
            $t = $table[0];
            if (!isset($table[1]) || $table[1] === '') {
                $as = null;
            } else {
                $as = $table[1];
            }
        } else {
            throw new Exception('Invalid function parameter');
        }

        $t = $this->bstr($t);
        $t = $this->quoteTableName($t);
        $as = ($as === null) ? '' : ' ' . $this->quoteTableName($as);
        return $t . $as;
    }


    /**
     * 把一个表名或者列名字符串转换为标准格式待用。
     * 支持：“名称”、“名称 AS 别名”这两种形式。
     *
     * 考虑到“表名 别名”这种用法不易一眼识别。因此表名如果带别名，一律要求都用" as "显式指明。
     * 如“tb_user AS u”，其中AS的大小写没有关系，用AS、as或As都行。
     *
     * Converts a table/column name string to an array of a specified format.
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
     * 检查给出的名称字符串是否是一个可用作表名或列名的单词。
     * 标准是：以下划线或字母开头，后面跟若干个_、字母和数字。
     * 注意：执行本函数前，要先转换好 ###_tablename
     *
     * Tests the specified $name is a valid table/column name.
     *
     * @param string $name
     * @return int   1 for yes, 0 for no
     */
    protected function isName($name)
    {
        return preg_match('/^[_A-Za-z]{1}\w*$/', $name);
    }


    /**
     * 检查给出的字符串是否是一个以点分隔的名字。
     * 用于检查列名是否是“表名称.列名称”这种形式。
     * 注意：执行本函数前，要先转换好 ###_tablename
     *
     * Tests the specified $name is a name splitted by a dot, like "tb_user.address"
     *
     * @param string $name
     * @return int   1 for yes, 0 for no
     */
    protected function isNameWithDot($name)
    {
        return preg_match('/^[_A-Za-z]{1}\w*\.[_A-Za-z]{1}\w*$/', $name);
    }
}
