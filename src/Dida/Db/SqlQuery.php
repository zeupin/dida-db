<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

use \Exception;

/**
 * SQL Statement Query
 */
class SqlQuery implements SqlQueryInterface, SqlSelectInterface, SqlUpdateInterface
{
    /**
     * Version
     */
    const VERSION = '0.1.5';

    /**
     * @var \Dida\Db\Db
     */
    protected $db = null;

    /**
     * @var \Dida\Db\Builder
     */
    protected $builder = null;

    /**
     * @var boolean
     */
    public $built = false;

    /**
     * The result of $this->build()
     *
     * @var boolean
     */
    public $build_ok = false;

    /**
     * Enable/Disable pull-execution
     *
     * @see __call()
     * @var boolean
     */
    protected $pullexec = true;

    /**
     * SQL statement
     *
     * @var string
     */
    public $statement = null;

    /**
     * SQL parameters
     *
     * @var array
     */
    public $parameters = null;

    /**
     * @var array
     */
    protected $taskbase = [
        'verb'         => 'SELECT',
        'prefix'       => '',
        'swap_prefix'  => '###_',
        'where_logic'  => 'AND',
        'having_logic' => 'AND',
    ];

    /**
     * 任务清单
     *
     * @var array
     */
    protected $tasklist = [];


    /**
     * Class construct.
     *
     * @param array $options
     * @param \Dida\Db\Db $db
     */
    public function __construct(&$db)
    {
        $this->db = $db;

        $cfg = $db->getConfig();

        $this->taskbase = array_merge($this->taskbase, [
            'prefix'      => $cfg['db.prefix'],
            'swap_prefix' => $cfg['db.swap_prefix'],
        ]);

        // 重置任务列表为空
        $this->init();
    }


    /**
     * 重置任务列表为空
     *
     * @return $this
     */
    public function init()
    {
        $this->tasklist = $this->taskbase;

        return $this->changed();
    }


    /**
     * Implicit calling the methods in the DataSet class.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        // Pull-Execution feature
        if ($this->pullexec) {
            if (method_exists('\Dida\Db\DataSet', $name)) {
                $result = $this->execute();
                return call_user_func_array([$result, $name], $arguments);
            }
        }

        throw new Exception(sprintf('Method %s::%s does not exist.', __CLASS__, $name));
    }


    /**
     * Enable Pull-Execution feature.
     *
     * @return $this
     */
    public function setPullExec($flag = true)
    {
        $this->pullexec = $flag;

        return $this;
    }


    /**
     * Clears the $built flag, and prepares to build() again.
     *
     * @return $this
     */
    public function changed()
    {
        $this->statement = null;
        $this->parameters = null;
        $this->built = false;

        return $this;
    }


    /**
     * Resets the COUNT(...) relative data.
     * See count()
     *
     * @return $this
     */
    public function resetCount()
    {
        unset($this->tasklist['count'], $this->tasklist['count_built']);

        return $this->changed();
    }


    /**
     * Changes the master table.
     *
     * @param string $name
     * @param string $alias
     * @param string $prefix
     *
     * @return $this
     */
    public function table($name, $alias = null, $prefix = null)
    {
        $this->init();

        $this->tasklist['table'] = [
            'name'   => $name,
            'alias'  => $alias,
            'prefix' => $prefix,
        ];
        $this->tasklist['table_built'] = false;

        return $this->changed();
    }

    /**
     * 设置tablelist到SqlQuery
     *
     * @param $tablelist
     *      @@string
     *          "表名 别名, 表名 别名， ..."
     *          推荐用这种形式，使用比较方便，但是不支持prefix
     *      @@array
     *      [
     *          [$name, $alias, $prefix],
     *          [$name, $alias, $prefix],
     *      ]
     */
    public function tablelist(array $tablelist)
    {
        $this->init();

        // 设置tablelist项目
        $this->tasklist['tablelist'] = $tablelist;

        return $this->changed();
    }


    /**
     * Adds a WHERE condition.
     *
     * @param mixed $condition
     * @param array $data
     *
     * @return $this
     */
    public function where($condition, array $data = [])
    {
        if (is_string($condition)) {
            if (substr($condition, 0, 1) !== '(') {
                $condition = "($condition)";
            }
            $condition = [$condition, 'RAW', $data];
        }

        $this->tasklist['where'][] = $condition;
        $this->tasklist['where_built'] = false;

        return $this->changed();
    }


    /**
     * Adds many WHERE conditions.
     *
     * @param array $conditions
     * @param string $logic
     *
     * @return $this
     */
    public function whereMany(array $conditions, $logic = 'AND')
    {
        $logic = strtoupper(trim($logic));

        $cond = new \stdClass();
        $cond->logic = $logic;
        $cond->items = $conditions;

        $this->tasklist['where'][] = $cond;
        $this->tasklist['where_built'] = false;

        return $this->changed();
    }


    /**
     * How to join the WHERE condition parts.
     *
     * @param string $logic AND/OR/...
     *
     * @return $this
     */
    public function whereLogic($logic)
    {
        $logic = strtoupper(trim($logic));

        if ($logic === $this->tasklist['where_logic']) {
            return $this;
        }

        $this->tasklist['where_logic'] = $logic;
        $this->tasklist['where_built'] = false;

        return $this->changed();
    }


    /**
     * Build a WHERE condition to match the given array.
     *
     * @param array $array
     * @param string $logic
     *
     * @return $this
     */
    public function whereMatch(array $array, $logic = 'AND')
    {
        $conditions = [];
        foreach ($array as $key => $value) {
            $conditions[] = [$key, '=', $value];
        }
        $this->whereMany($conditions, $logic);

        return $this->changed();
    }


    /**
     * Set column value.
     *
     * @param string|array $column
     * @param mixed|null $value
     */
    public function setValue($column, $value = null)
    {
        $this->tasklist['verb'] = 'UPDATE';

        if (is_string($column)) {
            $this->tasklist['set'][$column] = [
                'type'   => 'value',
                'column' => $column,
                'value'  => $value,
            ];
        } elseif (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->tasklist['set'][$key] = [
                    'type'   => 'value',
                    'column' => $key,
                    'value'  => $value,
                ];
            }
        } else {
            throw new Exception('Invalid argument type for $column');
        }

        return $this->changed();
    }


    /**
     * Set column expression.
     *
     * @param string $column
     * @param mixed $expr
     * @param array $parameters
     *
     * @return $this
     */
    public function setExpr($column, $expr, array $parameters = [])
    {
        $this->tasklist['verb'] = 'UPDATE';

        $this->tasklist['set'][$column] = [
            'type'       => 'expr',
            'column'     => $column,
            'expr'       => $expr,
            'parameters' => $parameters,
        ];

        return $this->changed();
    }


    /**
     * Set column value using a SELECT subquery.
     *
     * @param string $column
     * @param string $tableB
     * @param string $columnB
     * @param string $colA
     * @param string $colB
     * @param boolean $checkExistsInWhere
     *
     * @return $this
     */
    public function setFromTable($column, $tableB, $columnB, $colA, $colB, $checkExistsInWhere = true)
    {
        $this->tasklist['verb'] = 'UPDATE';

        $this->tasklist['set'][$column] = [
            'type'               => 'from_table',
            'column'             => $column,
            'tableB'             => $tableB,
            'columnB'            => $columnB,
            'colA'               => $colA,
            'colB'               => $colB,
            'checkExistsInWhere' => $checkExistsInWhere,
        ];

        return $this->changed();
    }


    /**
     * JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function join($tableB, $on, array $parameters = [])
    {
        $this->tasklist['join'][] = ['JOIN', $tableB, $on, $parameters];
        $this->tasklist['join_built'] = false;

        return $this->changed();
    }


    /**
     * INNER JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function innerJoin($tableB, $on, array $parameters = [])
    {
        $this->tasklist['join'][] = ['INNER JOIN', $tableB, $on, $parameters];
        $this->tasklist['join_built'] = false;

        return $this->changed();
    }


    /**
     * LEFT JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function leftJoin($tableB, $on, array $parameters = [])
    {
        $this->tasklist['join'][] = ['LEFT JOIN', $tableB, $on, $parameters];
        $this->tasklist['join_built'] = false;

        return $this->changed();
    }


    /**
     * RIGHT JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function rightJoin($tableB, $on, array $parameters = [])
    {
        $this->tasklist['join'][] = ['RIGHT JOIN', $tableB, $on, $parameters];
        $this->tasklist['join_built'] = false;

        return $this->changed();
    }


    /**
     * Increases $column by $value
     *
     * @param string $column
     * @param mixed $value
     */
    public function increment($column, $value = 1)
    {
        $this->tasklist['verb'] = 'UPDATE';

        $this->setExpr($column, "$column + $value");

        return $this->changed();
    }


    /**
     * Decreases $column by $value
     *
     * @param string $column
     * @param mixed $value
     */
    public function decrement($column, $value = 1)
    {
        $this->tasklist['verb'] = 'UPDATE';

        $this->setExpr($column, "$column - $value");

        return $this->changed();
    }


    /**
     * GROUP BY clause.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function groupBy(array $columns)
    {
        $this->tasklist['groupby'] = $columns;
        $this->tasklist['groupby_built'] = false;

        return $this->changed();
    }


    /**
     * Adds a having condition.
     *
     * @param string|array $condition
     * @param array $parameters
     *
     * @return $this
     */
    public function having($condition, array $parameters = [])
    {
        if (is_string($condition)) {
            if (substr($condition, 0, 1) !== '(') {
                $condition = "($condition)";
            }
            $condition = [$condition, 'RAW', $parameters];
        }

        $this->tasklist['having'][] = $condition;
        $this->tasklist['having_built'] = false;

        return $this->changed();
    }


    /**
     * Adds many having conditions.
     *
     * @param array $conditions
     * @param string $logic
     *
     * @return $this
     */
    public function havingMany(array $conditions, $logic = 'AND')
    {
        $logic = strtoupper(trim($logic));

        $cond = new \stdClass();
        $cond->logic = $logic;
        $cond->items = $conditions;

        $this->tasklist['having'][] = $cond;
        $this->tasklist['having_built'] = false;

        return $this->changed();
    }


    /**
     * How to join having clause parts.
     *
     * @param string $logic AND/OR/XOR/...
     *
     * @return $this
     */
    public function havingLogic($logic)
    {
        $logic = strtoupper(trim($logic));

        if ($logic === $this->tasklist['having_logic']) {
            return $this;
        }

        $this->tasklist['having_logic'] = $logic;
        $this->tasklist['having_built'] = false;

        return $this->changed();
    }


    /**
     * DISTINCT clause.
     *
     * @param boolean $distinct
     *
     * @return $this
     */
    public function distinct($distinct = true)
    {
        $this->tasklist['distinct'] = $distinct;
        $this->tasklist['distinct_built'] = false;

        return $this->changed();
    }


    /**
     * ORDER BY clause.
     *
     * @param array|string $columns
     *
     * @return $this
     */
    public function orderBy($columns)
    {
        if (!isset($this->tasklist['orderby'])) {
            $this->tasklist['orderby'] = [];
        }

        $this->tasklist['orderby'][] = $columns;
        $this->tasklist['orderby_built'] = false;

        return $this->changed();
    }


    /**
     * SELECT COUNT(...)
     *
     * @param array $columns
     * @param string $alias
     *
     * @return $this
     */
    public function count(array $columns = null, $alias = null)
    {
        $this->tasklist['verb'] = 'SELECT';

        $this->tasklist['count'] = [$columns, $alias];
        $this->tasklist['count_built'] = false;

        return $this->changed();
    }


    /**
     * LIMIT clause.
     *
     * @param int|string $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->tasklist['limit'] = $limit;

        return $this->changed();
    }


    /**
     * 导出当前的tasklist
     *
     * @return array
     */
    public function exportTaskList()
    {
        return $this->tasklist;
    }


    /**
     * 导入一个tasklist
     *
     * @param array $tasklist
     */
    public function importTaskList(array $tasklist)
    {
        $this->tasklist = $tasklist;
    }


    /**
     * SELECT
     *
     * @return $this
     */
    public function select(array $arrayColumnAsAlias = [])
    {
        $this->tasklist['verb'] = 'SELECT';

        $this->tasklist['select_column_list'] = $arrayColumnAsAlias;
        $this->tasklist['select_column_list_built'] = false;

        return $this->changed();
    }


    /**
     * DELETE
     *
     * @return $this
     */
    public function delete()
    {
        $this->tasklist['verb'] = 'DELETE';

        return $this->changed();
    }


    /**
     * INSERT
     *
     * @return $this
     */
    public function insert(array $record)
    {
        $this->tasklist['verb'] = 'INSERT';

        $this->tasklist['record'] = $record;

        return $this->changed();
    }


    /**
     * UPDATE
     *
     * @return $this
     */
    public function update()
    {
        $this->tasklist['verb'] = 'UPDATE';

        return $this->changed();
    }


    /**
     * TRUNCATE
     *
     * @return $this
     */
    public function truncate()
    {
        $this->tasklist['verb'] = 'TRUNCATE';

        return $this->changed();
    }


    /**
     * Builds the statement.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->built) {
            return $this;
        }

        $this->build_ok = false;

        $this->builder = $this->db->getBuilder();

        if ($this->builder === null) {
            throw new Exception('Not specified a Builder object.');
        }

        $result = $this->builder->build($this->tasklist);

        if ($result === false) {
            $this->statement = null;
            $this->parameters = null;
            $this->build_ok = false;
        } else {
            $this->statement = $result['statement'];
            $this->parameters = $result['parameters'];
            $this->build_ok = true;
        }

        $this->built = true;

        return $this;
    }


    /**
     * Executes the SQL statement built and returns a DataSet object.
     *
     * @param string $sql
     * @param array $sql_parameters
     *
     * @return DataSet
     */
    public function execute()
    {
        if (!$this->built) {
            $this->build();
        }

        // Makes a DB connection.
        if ($this->db->connect() === false) {
            throw new Exception('Fail to connect the database.');
        }

        try {
            $pdoStatement = $this->db->getPDO()->prepare($this->statement);
            $success = $pdoStatement->execute($this->parameters);
            return new DataSet($this->db, $pdoStatement, $success);
        } catch (Exception $ex) {
            return false;
        }
    }
}
