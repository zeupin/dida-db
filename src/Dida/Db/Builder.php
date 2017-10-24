<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \Exception;

/**
 * SQL表达式构造器。
 *
 * 每次调用build()，只会生成一条SQL语句。
 */
class Builder implements BuilderInterface
{
    /**
     * 指向Db实例的指针
     *
     * @var \Dida\Db\Db
     */
    protected $db = null;

    /**
     * 环境上下文
     *
     * [
     *     表名 => [
     *          'columnlist' =>[], // 全部列名的列表
     *          'columns' => ,   // 列的详细信息
     *          'pri' => ,       // 主键字段名
     *          'uni' => [],     // 唯一索引的字段
     *          ],
     * ]
     *
     * @var array
     */
    protected $context = [];

    /**
     * 任务列表
     *
     * @var array
     */
    protected $tasklist = [];

    /**
     * 本次build用到的数据表
     *
     * [
     *   [name=>, alias=>, nameAsAlias=>],
     * ]
     *
     * @var array
     */
    protected $tables = [];

    /**
     * build时，用于保存临时数据的字典
     *
     * @var array
     */
    protected $dict = [
        'table' => '',
    ];

    /**
     * 最终的表达式数组(statement array)
     *
     * @var array
     */
    protected $ST = [];

    /**
     * 最终的参数数组(parameter array)
     *
     * @var array
     */
    protected $PA = [];

    /**
     * 支持的操作符集合
     */
    protected static $opertor_set = [
        /* Raw SQL */
        'RAW' => 'RAW', //

        /* 等于 */
        'EQ' => 'EQ',
        '='  => 'EQ',
        '==' => 'EQ', //

        /* 不等于 */
        'NEQ' => 'NEQ',
        '<>'  => 'NEQ',
        '!='  => 'NEQ', //

        /* <,>,<=,>= */
        'GT'  => 'GT',
        '>'   => 'GT',
        'EGT' => 'EGT',
        '>='  => 'EGT',
        'LT'  => 'LT',
        '<'   => 'LT',
        'ELT' => 'ELT',
        '<='  => 'ELT', //

        /* LIKE */
        'LIKE'     => 'LIKE',
        'NOT LIKE' => 'NOTLIKE',
        'NOTLIKE'  => 'NOTLIKE', //

        /* IN */
        'IN'     => 'IN',
        'NOT IN' => 'NOTIN',
        'NOTIN'  => 'NOTIN', //

        /* BETWEEN */
        'BETWEEN'     => 'BETWEEN',
        'NOT BETWEEN' => 'NOTBETWEEN',
        'NOTBETWEEN'  => 'NOTBETWEEN', //

        /* EXISTS */
        'EXISTS'     => 'EXISTS',
        'NOT EXISTS' => 'NOTEXISTS',
        'NOTEXISTS'  => 'NOTEXISTS', //

        /* ISNULL */
        'ISNULL'      => 'ISNULL',
        'NULL'        => 'ISNULL',
        'ISNOTNULL'   => 'ISNOTNULL',
        'IS NOT NULL' => 'ISNOTNULL',
        'NOTNULL'     => 'ISNOTNULL',
        'NOT NULL'    => 'ISNOTNULL', //
    ];


    /**
     * 类的构造函数
     *
     * @param \Dida\Db\Db $db
     */
    public function __construct(&$db)
    {
        $this->db = $db;
    }

    protected function reset()
    {
        // 重置数据表
        $this->table = [];

        // 重置字典
        $this->dict = [
            'table' => '',
        ];

        // 重置ST和PA
        $this->ST = [];
        $this->PA = [];

        // 完成标志
        $this->done = null;
    }

    /**
     * 根据给出的$tasklist数组，构造出对应的SQL表达式
     *
     * @param array $tasklist
     *
     * @return
     * @@array
     *      [
     *          'statement'  => ...,
     *          'parameters' => ...,
     *      ]
     */
    public function build(&$tasklist)
    {
        // 重置内部变量
        $this->reset();

        // 保存传过来的任务列表
        $this->tasklist = $tasklist;

        // 准备context
        $this->prepareContext();

        // 根据verb不同，选择对应的模板进行构建
        switch ($this->tasklist['verb']) {
            case 'SELECT':
                return $this->build_SELECT();
            case 'DELETE':
                return $this->build_DELETE();
            case 'INSERT':
                return $this->build_INSERT();
            case 'UPDATE':
                return $this->build_UPDATE();
            case 'TRUNCATE':
                return $this->build_TRUNCATE();
            default:
                throw new Exception("Invalid build verb: {$this->tasklist['verb']}");
        }
    }

    protected function prepareContext()
    {
        $this->tables = [];
        $this->process_tablelist();
        $this->process_table();

        // 检查context中是否已经有table对应的数据，没有的话，就调入数据
        foreach ($this->tables as $table) {
            extract($table);

            // 如果没有表格元数据，则先去载入进来
            if (!array_key_exists($name, $this->context)) {
                $this->db->getSchemaInfo()->readTableInfoFromCache($name);
            }
        }
    }

    /**
     * 把 $this->tasklist[tablelist]，按照标准格式导入到 $this->tables
     *
     * @throws Exception
     */
    protected function process_tablelist()
    {
        // 如果没有设置tablelist，直接退出
        if (!array_key_exists('tablelist', $this->tasklist)) {
            return;
        }

        $tablelist = $this->tasklist['tablelist'];

        // 如果$tablelist是字符串形式："表名 别名,表名 别名，..."
        if (is_string($tablelist)) {
            $prefix = $this->tasklist['prefix'];

            $arrTemp = explode(',', $tablelist);
            foreach ($arrTemp as $item) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }

                $data = $this->splitNameAlias($item); // name=>,alias=>
                $realname = $prefix . $data['name'];
                $alias = $data['alias'];
                if ($alias) {
                    $nameAsAlias = "$realname AS $alias";
                } else {
                    $nameAsAlias = $realname;
                }
                $this->tables[] = [
                    'name'        => $realname,
                    'alias'       => $alias,
                    'nameAsAlias' => $nameAsAlias,
                ];
            }

            // 完成
            return;
        }

        // 如果$tablelist是数组
        if (is_array($tablelist)) {
            foreach ($tablelist as $table) {
                $alias = null;
                $prefix = null;
                switch (count($tablelist)) {
                    case 1:
                        list($name) = $tablelist;
                        break;
                    case 2:
                        list($name, $alias) = $tablelist;
                        break;
                    case 3:
                        list($name, $alias, $prefix) = $tablelist;
                        break;
                    default:
                        throw new Exception('tablelist的表格式非法，必须为 [name必填, alias选填, prefix选填] ');
                }

                // 表前缀
                if (!is_string($prefix)) {
                    $prefix = $this->tasklist['prefix'];
                }
                $realname = $prefix . $name;

                if (is_string($alias) && $alias) {
                    $nameAsAlias = "$realname AS $alias";
                } else {
                    $alias = null;
                    $nameAsAlias = $realname;
                }
                $this->tables[] = [
                    'name'        => $realname,
                    'alias'       => $alias,
                    'nameAsAlias' => $nameAsAlias,
                ];

                // 完成
                return;
            }
        }
    }


    /**
     * 把 $this->tasklist[table]，按照标准格式导入到 $this->tables
     */
    protected function process_table()
    {
        // 如果没有设置table，直接退出
        if (!array_key_exists('table', $this->tasklist)) {
            return;
        }

        // $name, $alias, $prefix
        extract($this->tasklist['table']);

        if (!is_string($prefix)) {
            $prefix = $this->tasklist['prefix'];
        }

        $realname = $prefix . $name;

        if (is_string($alias) && $alias) {
            $nameAsAlias = "$realname AS $alias";
        } else {
            $alias = null;
            $nameAsAlias = $realname;
        }

        $this->tables[] = [
            'name'        => $realname,
            'alias'       => $alias,
            'nameAsAlias' => $nameAsAlias,
        ];
    }


    protected function build_SELECT()
    {
        $this->prepare_SELECT();

        $TPL = [
            "SELECT\n    ",
            'columnlist' => &$this->ST['columnlist'],
            "\nFROM\n    ",
            'table'      => &$this->ST['table'],
            'join'       => &$this->ST['join'],
            'where'      => &$this->ST['where'],
            'groupby'    => &$this->ST['groupby'],
            'having'     => &$this->ST['having'],
            'orderby'    => &$this->ST['orderby'],
            'limit'      => &$this->ST['limit'],
        ];
        $PARAMS = [
            'join'   => &$this->PA['join'],
            'where'  => &$this->PA['where'],
            'having' => &$this->PA['having'],
        ];

        return [
            'statement'  => implode('', $TPL),
            'parameters' => $this->combineParameterArray($PARAMS),
        ];
    }


    protected function build_INSERT()
    {
        $this->prepare_INSERT();

        /* INSERT statement template */
        $TPL = [
            'INSERT INTO ',
            'table'   => &$this->ST['table'],
            'columns' => &$this->ST['insert_column_list'],
            ' VALUES ',
            'values'  => &$this->ST['insert_values'],
        ];
        $PARAMS = [
            'values' => &$this->PA['insert_values'],
        ];

        return [
            'statement'  => implode('', $TPL),
            'parameters' => $this->combineParameterArray($PARAMS),
        ];
    }


    protected function build_UPDATE()
    {
        $this->prepare_UPDATE();

        $TPL = [
            "UPDATE\n    ",
            'table'   => &$this->ST['table'],
            "\nSET\n    ",
            'set'     => &$this->ST['set'],
            'join'    => &$this->ST['join'],
            'where'   => &$this->ST['where'],
            'groupby' => &$this->ST['groupby'],
            'having'  => &$this->ST['having'],
            'orderby' => &$this->ST['orderby'],
        ];
        $PARAMS = [
            'set'    => &$this->PA['set'],
            'join'   => &$this->PA['join'],
            'where'  => &$this->PA['where'],
            'having' => &$this->PA['having'],
        ];

        return [
            'statement'  => implode('', $TPL),
            'parameters' => $this->combineParameterArray($PARAMS),
        ];
    }


    protected function build_DELETE()
    {
        $this->prepare_DELETE();

        $TPL = [
            'DELETE FROM ',
            'table'   => &$this->ST['table'],
            'join'    => &$this->ST['join'],
            'where'   => &$this->ST['where'],
            'groupby' => &$this->ST['groupby'],
            'having'  => &$this->ST['having'],
            'orderby' => &$this->ST['orderby'],
        ];
        $PARAMS = [
            'join'   => &$this->PA['join'],
            'where'  => &$this->PA['where'],
            'having' => &$this->PA['having'],
        ];

        return [
            'statement'  => implode('', $TPL),
            'parameters' => $this->combineParameterArray($PARAMS),
        ];
    }


    protected function build_TRUNCATE()
    {
        $this->prepare_TRUNCATE();

        $TPL = [
            'TRUNCATE TABLE ',
            'table' => &$this->ST['table'],
        ];

        return [
            'statement'  => implode('', $TPL),
            'parameters' => [],
        ];
    }


    /**
     * Picks all items with a string key.
     *
     * @param array $array
     */
    protected function pickItemsWithKey(array $array)
    {
        $return = [];
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                $return[$key] = $value;
            }
        }
        return $return;
    }


    /**
     * Makes a question mark list with includes $count '?'.
     *
     * @param int|array $count
     * @param boolean $braket
     *
     * @return string
     */
    protected function makeQuestionMarkList($count, $braket = false)
    {
        if (is_array($count)) {
            $count = count($count);
        }
        $list = implode(', ', array_fill(0, $count, '?'));
        if ($braket) {
            return ($braket) ? "($list)" : $list;
        }
    }


    protected function clause_TABLELIST()
    {

    }


    protected function prepare_SELECT()
    {
        $this->clause_TABLE();

        /* Prepares the column list expression. */
        $this->dict_SELECT_COLUMN_LIST();
        $this->dict_DISTINCT();

        /* If count() */
        if ($this->has('count')) {
            $this->clause_COUNT();
        } else {
            $this->ST['columnlist'] = $this->dict['distinct'] . $this->dict['select_column_list'];
        }

        $this->clause_JOIN();
        $this->clause_WHERE();
        $this->clause_GROUP_BY();
        $this->clause_HAVING();
        $this->clause_ORDER_BY();
        $this->clause_LIMIT();
    }


    protected function prepare_INSERT()
    {
        $this->clause_TABLE();
        $this->clause_JOIN();
        $this->clause_WHERE();
        $this->clause_GROUP_BY();
        $this->clause_HAVING();

        $record = &$this->tasklist['record'];
        $record = $this->pickItemsWithKey($record);
        $columns = array_keys($record);
        $values = array_values($record);

        $this->ST['insert_column_list'] = '(' . implode(', ', $columns) . ')';
        $this->ST['insert_values'] = $this->makeQuestionMarkList($columns, true);
        $this->PA['insert_values'] = $values;
    }


    protected function prepare_UPDATE()
    {
        $this->clause_TABLE();
        $this->clause_SET();
        $this->clause_JOIN();
        $this->clause_WHERE();
        $this->clause_GROUP_BY();
        $this->clause_HAVING();
        $this->clause_ORDER_BY();
    }


    protected function prepare_DELETE()
    {
        $this->clause_TABLE();
        $this->clause_JOIN();
        $this->clause_WHERE();
        $this->clause_GROUP_BY();
        $this->clause_HAVING();
        $this->clause_ORDER_BY();
    }


    protected function prepare_TRUNCATE()
    {
        $this->clause_TABLE();
    }


    /**
     * Returns a SELECT columnlist clause.
     *
     * If $columns = null/[]/'', equivalent to return '*' (with all column names of the table)
     * If $columns is a string, returns it directly.
     * If $columns is an array, returns the imploded expression.
     *
     * @param string|array $columns
     * @return string
     */
    protected function process_SelectColumnList($columns)
    {
        // if $columns = ''/null/[]
        if (!$columns) {
            return $this->getAllColumnNames($this->dict['table']['name']);
        }

        if (is_string($columns)) {
            return $columns;
        }

        if (is_array($columns)) {
            $array = [];
            foreach ($columns as $alias => $column) {
                if (is_string($alias)) {
                    $array[] = "$column AS $alias";
                } else {
                    $array[] = $column;
                }
            }
            return implode(', ', $array);
        }
    }


    protected function dict_SELECT_COLUMN_LIST()
    {
        if (!isset($this->tasklist['columnlist'])) {
            $this->dict['select_column_list'] = $this->process_SelectColumnList(null);
            return;
        }

        $columns = $this->tasklist['columnlist'];
        $this->dict['select_column_list'] = $this->process_SelectColumnList($columns);
    }


    /**
     * @param array $columns ['alias'=>'column',]
     */
    protected function combineColumnList(array $columns, $table = null)
    {
        if (is_string($table) && $table) {
            $table = $table . '.';
        }
    }


    protected function getAllColumnNames($table)
    {
        return '*';
    }


    protected function clause_TABLE()
    {
        // $name, $alias, $prefix
        extract($this->tasklist['table']);

        if (!is_string($prefix)) {
            $prefix = $this->tasklist['prefix'];
        }

        $realname = $prefix . $name;

        if (!is_string($alias)) {
            $alias = null;
        }

        /* dict */
        $this->dict['table'] = [
            'name'  => $realname,
            'alias' => $alias,
        ];
        $this->dict['table']['ref'] = $this->tableRef($this->dict['table']['name'], $this->dict['table']['alias']);
        $this->dict['table']['name_as_alias'] = $this->tableNameAsAlias($this->dict['table']['name'], $this->dict['table']['alias']);

        /* ST */
        switch ($this->tasklist['verb']) {
            case 'SELECT':
                $this->ST['table'] = $this->dict['table']['name_as_alias'];
                break;
            default:
                $this->ST['table'] = $this->dict['table']['name'];
        }

        $this->tasklist['table_built'] = true;
        return;
    }


    protected function tableRef($name, $alias)
    {
        return ($alias) ? $alias : $name;
    }


    protected function tableNameAsAlias($name, $alias)
    {
        if ($alias) {
            return $name . ' AS ' . $alias;
        } else {
            return $name;
        }
    }


    /**
     * Replaces a swapped SQL to a normal SQL.
     */
    protected function replaceSwapPrefix($swapsql)
    {
        $prefix = $this->tasklist['prefix'];
        $swap_prefix = $this->tasklist['swap_prefix'];
        if ($swap_prefix) {
            return str_replace($swap_prefix, $prefix, $swapsql);
        } else {
            return $swapsql;
        }
    }


    protected function cond($condition, $parameters = [])
    {
        if (is_string($condition)) {
            $part = [
                'statement'  => $condition,
                'parameters' => $parameters,
            ];
            return $part;
        }

        if (is_array($condition)) {
            return $this->condAsArray($condition);
        }

        if (is_object($condition)) {
            return $this->condAsObject($condition->logic, $condition->items);
        }

        throw new Exception("Invalid condition format");
    }


    protected function condAsArray($condition)
    {
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


    protected function condAsObject($logic, $conditions)
    {
        $parts = [];
        foreach ($conditions as $condition) {
            $part = $this->cond($condition);
            $parts[] = $part;
        }
        $statement = '';
        $parameters = [];
        $this->combineParts($parts, " $logic ", $statement, $parameters);
        if (count($conditions) > 1) {
            $statement = "($statement)";
        }

        return [
            'statement'  => "$statement",
            'parameters' => $parameters,
        ];
    }


    protected function cond_RAW($column, $op, $data)
    {
        return [
            'statement'  => $column,
            'parameters' => $data,
        ];
    }


    protected function cond_COMPARISON($column, $op, $data)
    {
        $column = $this->replaceSwapPrefix($column);
        $part = [
            'statement'  => "($column $op ?)",
            'parameters' => [$data],
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
            throw new Exception('An empty array not allowed use in a IN statement');
        }

        $column = $this->replaceSwapPrefix($column);
        $marks = implode(', ', array_fill(0, count($data), '?'));
        $part = [
            'statement'  => "($column $op ($marks))",
            'parameters' => array_values($data),
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
        $column = $this->replaceSwapPrefix($column);
        $part = [
            'statement'  => "$column $op ?",
            'parameters' => $data,
        ];
        return $part;
    }


    protected function cond_NOTLIKE($column, $op, $data)
    {
        return $this->cond_LIKE($column, 'NOT LIKE', $data);
    }


    protected function cond_BETWEEN($column, $op, $data)
    {
        $column = $this->replaceSwapPrefix($column);
        $part = [
            'statement'  => "($column $op ? AND ?)",
            'parameters' => $data,
        ];
        return $part;
    }


    protected function cond_NOTBETWEEN($column, $op, $data)
    {
        return $this->cond_BETWEEN($column, 'NOT BETWEEN', $data);
    }


    protected function cond_ISNULL($column, $op, $data = null)
    {
        $column = $this->replaceSwapPrefix($column);
        $part = [
            'statement'  => "$column IS NULL",
            'parameters' => [],
        ];
        return $part;
    }


    protected function cond_ISNOTNULL($column, $op, $data = null)
    {
        $column = $this->replaceSwapPrefix($column);
        $part = [
            'statement'  => "$column IS NOT NULL",
            'parameters' => [],
        ];
        return $part;
    }


    protected function cond_EXISTS($column, $op, $data)
    {
        $sql = $this->fsql($column);

        $part = [
            'statement'  => "EXISTS ($sql)",
            'parameters' => $data,
        ];
        return $part;
    }


    protected function cond_NOTEXISTS($column, $op, $data)
    {
        $sql = $this->fsql($column);

        $part = [
            'statement'  => "NOT EXISTS ($sql)",
            'parameters' => $data,
        ];
        return $part;
    }


    /**
     * Builds the WHERE statement.
     */
    protected function clause_WHERE()
    {
        if ($this->isBuilt('where')) {
            return;
        }

        if (!$this->has('where')) {
            $this->ST['where'] = '';
            $this->PA['where'] = [];
            $this->tasklist['where_built'] = true;
            return;
        }

        $conditions = $this->tasklist['where'];

        if ($this->has('where_logic')) {
            $logic = $this->tasklist['where_logic'];
        } else {
            $logic = 'AND';
        }

        $parts = [];
        foreach ($conditions as $condition) {
            $parts[] = $this->cond($condition);
        }

        $statement = '';
        $parameters = [];
        $this->combineParts($parts, "\n    $logic ", $statement, $parameters);
        if ($statement) {
            $this->ST['where'] = "\nWHERE\n    $statement";
            $this->PA['where'] = $parameters;
        }

        $this->tasklist['where_built'] = true;
        return;
    }


    protected function combineParts($parts, $glue, &$statement, &$parameters)
    {
        $statement_array = array_column($parts, 'statement');
        $statement = implode($glue, $statement_array);

        $parameters_array = array_column($parts, 'parameters');
        $parameters = $this->combineParameterArray($parameters_array);
    }


    protected function combineParameterArray(array $parameters)
    {
        $ret = [];
        foreach ($parameters as $array) {
            $ret = array_merge($ret, array_values($array));
        }
        return $ret;
    }


    protected function clause_SET()
    {
        if ($this->isBuilt('set')) {
            return;
        }

        $set = $this->tasklist['set'];

        $parts = [];
        foreach ($set as $item) {
            switch ($item['type']) {
                case 'value':
                    $parts[] = $this->setValue($item);
                    break;
                case 'expr':
                    $parts[] = $this->setExpr($item);
                    break;
                case 'from_table':
                    $parts[] = $this->setFromTable($item);
                    break;
            }
        }

        $statement = '';
        $parameters = [];
        $this->combineParts($parts, ",\n    ", $statement, $parameters);

        $this->ST['set'] = $statement;
        $this->PA['set'] = $parameters;
    }


    protected function setValue($item)
    {
        extract($item);

        return [
            'statement'  => "$column = ?",
            'parameters' => [$value],
        ];
    }


    protected function setExpr($item)
    {
        extract($item);

        return [
            'statement'  => "$column = $expr",
            'parameters' => $parameters,
        ];
    }


    /**
     * Set column from other table.
     */
    protected function setFromTable($item)
    {
        extract($item);
        $tableB = $this->replaceSwapPrefix($tableB);

        $tableRef = $this->dict['table']['ref'];

        $target = "(SELECT $tableB.$columnB FROM $tableB WHERE $tableRef.$colA = $tableB.$colB)";
        $statement = "$column = $target";

        if ($checkExistsInWhere) {
            $this->tasklist['where']['insert_if_exists'] = ["(EXISTS $target)", 'RAW', []];
        }

        return [
            'statement'  => $statement,
            'parameters' => [],
        ];
    }


    protected function clause_JOIN()
    {
        if ($this->isBuilt('join')) {
            return;
        }

        if (!$this->has('join')) {
            $this->ST['join'] = '';
            $this->PA['join'] = [];
            $this->tasklist['join_built'] = true;
            return;
        }

        $stmts = [];
        $params = [];

        $joins = $this->tasklist['join'];
        foreach ($joins as $join) {
            list($jointype, $table, $on, $parameters) = $join;

            $table = $this->replaceSwapPrefix($table);
            $on = $this->replaceSwapPrefix($on);

            $stmts[] = "\n$jointype {$table}\n    ON $on";
            $params[] = $parameters;
        }
        $this->ST["join"] = implode("", $stmts);
        $this->PA['join'] = $this->combineParameterArray($params);
        $this->tasklist['join_built'] = true;
    }


    protected function clause_GROUP_BY()
    {
        if ($this->isBuilt('groupby')) {
            return;
        }

        if (!$this->has('groupby')) {
            $this->ST['groupby'] = '';
            $this->tasklist['groupby_built'] = true;
            return;
        }

        $columns = $this->tasklist['groupby'];
        $columnlist = $this->process_SelectColumnList($columns);

        if ($columnlist) {
            $this->ST['groupby'] = "\nGROUP BY\n    $columnlist";
        } else {
            $this->ST['groupby'] = '';
        }

        $this->tasklist['groupby_built'] = true;
        return;
    }


    protected function has($key)
    {
        return array_key_exists($key, $this->tasklist);
    }


    protected function isBuilt($key)
    {
        // 先临时屏蔽掉这个功能，后期根据需要可能会删除掉这个函数
        return false;

        $built = $key . '_built';
        return ($this->has($built) && $this->tasklist[$built] === true);
    }


    /**
     * Builds the HAVING clause.
     */
    protected function clause_HAVING()
    {
        if ($this->isBuilt('having')) {
            return;
        }

        if (!$this->has('having')) {
            $this->ST['having'] = '';
            $this->PA['having'] = [];
            $this->tasklist['having_built'] = true;
            return;
        }

        $conditions = $this->tasklist['having'];

        if ($this->has('having_logic')) {
            $logic = $this->tasklist['having_logic'];
        } else {
            $logic = 'AND';
        }

        $parts = [];
        foreach ($conditions as $condition) {
            $parts[] = $this->cond($condition);
        }

        $statement = '';
        $parameters = [];
        $this->combineParts($parts, "\n    $logic ", $statement, $parameters);
        if ($statement) {
            $this->ST['having'] = "\nHAVING\n    $statement";
            $this->PA['having'] = $parameters;
        }

        $this->tasklist['having_built'] = true;
        return;
    }


    protected function dict_DISTINCT()
    {
        if (!$this->has('distinct')) {
            $this->dict['distinct'] = '';
            return;
        }

        $flag = $this->tasklist['distinct'];
        if ($flag) {
            $this->dict['distinct'] = "DISTINCT ";
        } else {
            $this->dict['distinct'] = '';
        }

        return;
    }


    protected function clause_ORDER_BY()
    {
        if ($this->isBuilt('orderby')) {
            return;
        }

        if (!$this->has('orderby')) {
            $this->ST['orderby'] = '';
            $this->tasklist['orderby_built'] = true;
            return;
        }

        $array = [];
        $orders = $this->tasklist['orderby'];
        foreach ($orders as $order) {
            if (is_string($order)) {
                $array[] = $this->process_OrderBy($order);
            } elseif (is_array($order)) {
                foreach ($order as $key => $value) {
                    if (is_int($key)) {
                        $array[] = $this->process_OrderBy($value);
                    } else {
                        $key = $this->replaceSwapPrefix($key);
                        $value = strtoupper(trim($value));
                        if ($value === 'ASC' || $value === 'DESC') {
                            $array[] = "$key $value";
                        } else {
                            $array[] = $key;
                        }
                    }
                }
            }
        }

        if (count($array)) {
            $this->ST['orderby'] = "\nORDER BY\n    " . implode(', ', $array);
        } else {
            $this->ST['orderby'] = '';
        }
        $this->tasklist['orderby_built'] = true;
    }


    protected function process_OrderBy($string)
    {
        $search = [
            '/\s{1,}asc$/i',
            '/\s{1,}desc$/i'
        ];
        $replace = [
            ' ASC',
            ' DESC'
        ];

        $return = [];
        $string = $this->replaceSwapPrefix($string);
        $array = explode(',', $string);
        foreach ($array as $item) {
            $item = trim($item);
            if ($item) {
                $item = preg_replace($search, $replace, $item);
                $return[] = $item;
            }
        }

        return implode(', ', $return);
    }


    protected function clause_COUNT()
    {
        list($columns, $alias) = $this->tasklist['count'];

        if (is_string($alias) && $alias) {
            $asAlias = " AS $alias";
        } else {
            $asAlias = '';
        }

        if (!$columns) {
            $columnlist = $this->dict['distinct'] . $this->dict['select_column_list'];
        } elseif (is_array($columns)) {
            $columnlist = $this->process_SelectColumnList($columns);
        }

        $this->ST['columnlist'] = "COUNT({$columnlist}){$asAlias}";
    }


    /**
     * 把一个 “name alias”形式或者“name AS alias”形式的字符串解析成数组形式。
     *
     * @param string $string 格式可为：
     *      "name" 或者 "name AS alias" 或者 "name alias"
     */
    protected function splitNameAlias($string)
    {
        // Finds the first ' AS ' string, then split it.
        $result = preg_split('/\s+(AS)\s+/i', $string, 2);
        $name = $result[0];
        $alias = (isset($result[1])) ? $result[1] : null;

        return [
            'name'  => $name,
            'alias' => $alias,
        ];
    }


    protected function clause_LIMIT()
    {
        if ($this->isBuilt('limit')) {
            return;
        }

        if (!$this->has('limit')) {
            $this->ST['limit'] = '';
            $this->tasklist['limit_built'] = true;
            return;
        }

        $limit = $this->tasklist['limit'];
        $this->ST['limit'] = "\nLIMIT\n    $limit";

        $this->tasklist['limit_built'] = true;
    }
}
