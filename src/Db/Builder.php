<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * SQL Statement Builder
 */
class Builder
{
    /**
     * @var \Dida\Db\Db
     */
    protected $db = null;

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
     * @var boolean
     */
    public $built = false;

    /**
     * @var \Dida\Db\BuilderCore
     */
    protected $core = null;

    /**
     * @var array
     */
    protected $base = [
        'verb'        => 'SELECT',
        'prefix'      => '',
        'swap_prefix' => '###_',
        'where_logic' => 'AND',
    ];

    /**
     * Todo list.
     *
     * @var array
     */
    protected $todolist = [];


    public function __construct(&$db, array $options)
    {
        $this->db = $db;

        $this->base = array_merge($this->base, $options);
        $this->resetAll();
    }


    public function changed()
    {
        $this->statement = null;
        $this->parameters = null;
        $this->built = false;

        return $this;
    }


    public function resetAll()
    {
        $this->todolist = $this->base;

        return $this->changed();
    }


    public function resetCount()
    {
        unset($this->todolist['count'], $this->todolist['count_built']);

        return $this->changed();
    }


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


    public function where($condition, $data = [])
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


    public function whereMatch(array $array, $logic = 'AND')
    {
        $conditions = [];
        foreach ($array as $key => $value) {
            $conditions[] = [$key, '=', $value];
        }
        $this->whereMany($conditions, $logic);

        return $this->changed();
    }


    public function select(array $columnAsAliasArray = [])
    {
        $this->todolist['verb'] = 'SELECT';

        $this->todolist['select_column_list'] = $columnAsAliasArray;
        $this->todolist['select_column_list_built'] = false;

        return $this->changed();
    }


    public function delete()
    {
        $this->todolist['verb'] = 'DELETE';

        return $this->changed();
    }


    public function insert(array $record)
    {
        $this->todolist['verb'] = 'INSERT';

        $this->todolist['record'] = $record;

        return $this->changed();
    }


    public function update()
    {
        $this->todolist['verb'] = 'UPDATE';

        return $this->changed();
    }


    /**
     * Set column expression.
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
     * Set column value using tableB.columnB where table.colA=tableB.colB.
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


    public function join($tableB, $on, $parameters = [])
    {
        $this->todolist['join'][] = ['JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

        return $this->changed();
    }


    public function innerJoin($tableB, $on, $parameters = [])
    {
        $this->todolist['join'][] = ['INNER JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

        return $this->changed();
    }


    public function leftJoin($tableB, $on, $parameters = [])
    {
        $this->todolist['join'][] = ['LEFT JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

        return $this->changed();
    }


    public function rightJoin($tableB, $on, $parameters = [])
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
    public function inc($column, $value = 1)
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
    public function dec($column, $value = 1)
    {
        $this->todolist['verb'] = 'UPDATE';

        $this->setExpr($column, "$column - $value");

        return $this->changed();
    }


    public function groupBy(array $columns)
    {
        $this->todolist['groupby'] = $columns;
        $this->todolist['groupby_built'] = false;

        return $this->changed();
    }


    public function having($condition, $parameters = [])
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
     * @return $this
     */
    public function limit($limit)
    {
        $this->todolist['limit'] = $limit;

        return $this->changed();
    }


    /**
     * Builds the statement.
     *
     * @return SQL|false
     */
    public function build()
    {
        if ($this->built) {
            return $this;
        }

        if ($this->core === null) {
            $this->core = new BuilderCore();
        }

        $result = $this->core->build($this->todolist);

        if ($result === false) {
            $this->statement = null;
            $this->parameters = null;
        } else {
            $this->statement = $result['statement'];
            $this->parameters = $result['parameters'];
        }

        $this->built = true;
        return $this;
    }


    /**
     * Executes an SQL statement directly.
     *
     * @param string $sql
     * @param array $sql_parameters
     *
     * @return Result
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
            return new Result($this->db, $pdoStatement, $success);
        } catch (Exception $ex) {
            return false;
        }
    }
}
