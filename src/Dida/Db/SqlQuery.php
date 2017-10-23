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
    protected $base = [
        'verb'         => 'SELECT',
        'prefix'       => '',
        'swap_prefix'  => '###_',
        'where_logic'  => 'AND',
        'having_logic' => 'AND',
    ];

    /**
     * Todo list.
     *
     * @var array
     */
    protected $todolist = [];


    /**
     * Class construct.
     *
     * @param array $options
     * @param \Dida\Db\Db $db
     * @param \Dida\Db\Builder $builder
     */
    public function __construct(array $options, &$db, &$builder)
    {
        $this->db = $db;
        $this->builder = $builder;

        $this->base = array_merge($this->base, $options);
        $this->resetAll();
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
     * Set $db for this object.
     *
     * @param \Dida\Db\Db $Db
     *
     * @return $this
     */
    public function setDb(&$db)
    {
        $this->db = $db;

        return $this->changed();
    }


    /**
     * Set $builder for this object.
     *
     * @param \Dida\Db\Db $Db
     *
     * @return $this
     */
    public function setBuilder(&$builder)
    {
        $this->builder = $builder;

        return $this->changed();
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
     * Resets all todolist data.
     *
     * @return $this
     */
    public function resetAll()
    {
        $this->todolist = $this->base;

        return $this->changed();
    }


    /**
     * Resets the COUNT(...) relative data.
     * See count()
     *
     * @return $this
     */
    public function resetCount()
    {
        unset($this->todolist['count'], $this->todolist['count_built']);

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
        $this->resetAll();

        $this->todolist['table'] = [
            'name'   => $name,
            'alias'  => $alias,
            'prefix' => $prefix,
        ];
        $this->todolist['table_built'] = false;

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

        $this->todolist['where'][] = $condition;
        $this->todolist['where_built'] = false;

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

        $this->todolist['where'][] = $cond;
        $this->todolist['where_built'] = false;

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

        if ($logic === $this->todolist['where_logic']) {
            return $this;
        }

        $this->todolist['where_logic'] = $logic;
        $this->todolist['where_built'] = false;

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
        $this->todolist['verb'] = 'UPDATE';

        if (is_string($column)) {
            $this->todolist['set'][$column] = [
                'type'   => 'value',
                'column' => $column,
                'value'  => $value,
            ];
        } elseif (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->todolist['set'][$key] = [
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
        $this->todolist['verb'] = 'UPDATE';

        $this->todolist['set'][$column] = [
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
        $this->todolist['verb'] = 'UPDATE';

        $this->todolist['set'][$column] = [
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
        $this->todolist['join'][] = ['JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

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
        $this->todolist['join'][] = ['INNER JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

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
        $this->todolist['join'][] = ['LEFT JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

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
        $this->todolist['join'][] = ['RIGHT JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

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
        $this->todolist['verb'] = 'UPDATE';

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
        $this->todolist['verb'] = 'UPDATE';

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
        $this->todolist['groupby'] = $columns;
        $this->todolist['groupby_built'] = false;

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

        $this->todolist['having'][] = $condition;
        $this->todolist['having_built'] = false;

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

        $this->todolist['having'][] = $cond;
        $this->todolist['having_built'] = false;

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

        if ($logic === $this->todolist['having_logic']) {
            return $this;
        }

        $this->todolist['having_logic'] = $logic;
        $this->todolist['having_built'] = false;

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
        $this->todolist['distinct'] = $distinct;
        $this->todolist['distinct_built'] = false;

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
        if (!isset($this->todolist['orderby'])) {
            $this->todolist['orderby'] = [];
        }

        $this->todolist['orderby'][] = $columns;
        $this->todolist['orderby_built'] = false;

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
        $this->todolist['verb'] = 'SELECT';

        $this->todolist['count'] = [$columns, $alias];
        $this->todolist['count_built'] = false;

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
        $this->todolist['limit'] = $limit;

        return $this->changed();
    }


    /**
     * SELECT
     *
     * @return $this
     */
    public function select(array $arrayColumnAsAlias = [])
    {
        $this->todolist['verb'] = 'SELECT';

        $this->todolist['select_column_list'] = $arrayColumnAsAlias;
        $this->todolist['select_column_list_built'] = false;

        return $this->changed();
    }


    /**
     * DELETE
     *
     * @return $this
     */
    public function delete()
    {
        $this->todolist['verb'] = 'DELETE';

        return $this->changed();
    }


    /**
     * INSERT
     *
     * @return $this
     */
    public function insert(array $record)
    {
        $this->todolist['verb'] = 'INSERT';

        $this->todolist['record'] = $record;

        return $this->changed();
    }


    /**
     * UPDATE
     *
     * @return $this
     */
    public function update()
    {
        $this->todolist['verb'] = 'UPDATE';

        return $this->changed();
    }


    /**
     * TRUNCATE
     *
     * @return $this
     */
    public function truncate()
    {
        $this->todolist['verb'] = 'TRUNCATE';

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

        if ($this->builder === null) {
            throw new Exception('Not specified a Builder object.');
        }

        $result = $this->builder->build($this->todolist);

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
            $pdoStatement = $this->db->pdo->prepare($this->statement);
            $success = $pdoStatement->execute($this->parameters);
            return new DataSet($this->db, $pdoStatement, $success);
        } catch (Exception $ex) {
            return false;
        }
    }
}
