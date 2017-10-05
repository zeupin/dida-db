<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * SQL Statement
 */
class Sql
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
     * @var \Dida\Db\SqlBuilder
     */
    protected $builder = null;

    /**
     * @var array
     */
    protected $base = [
        'verb'        => 'SELECT',
        'prefix'      => '',
        'vprefix'     => '###_',
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


    public function resetAll()
    {
        $this->todolist = $this->base;
        return $this;
    }


    public function resetCount()
    {
        unset($this->todolist['count'], $this->todolist['count_built']);
        return $this;
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

        if ($this->builder === null) {
            $this->builder = new SqlBuilder();
        }

        $result = $this->builder->build($this->todolist);

        if ($result === false) {
            $this->statement = null;
            $this->parameters = null;
        } else {
            $this->statement = $result['statement'];
            $this->parameters = $result['parameters'];
        }

        //var_dump($this->todolist);

        $this->built = true;
        return $this;
    }


    public function sql($statement, $parameters = [])
    {
        $this->resetAll();

        $this->statement = $statement;
        $this->parameters = $parameters;

        $this->built = true;
        return $this;
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

        $this->built = false;
        return $this;
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

        $this->built = false;
        return $this;
    }


    public function whereMany(array $conditions, $logic = 'AND')
    {
        $logic = strtoupper(trim($logic));

        $cond = new \stdClass();
        $cond->logic = $logic;
        $cond->items = $conditions;

        $this->todolist['where'][] = $cond;
        $this->todolist['where_built'] = false;

        $this->built = false;
        return $this;
    }


    public function whereLogic($logic)
    {
        $logic = strtoupper(trim($logic));

        if ($logic === $this->todolist['where_logic']) {
            return $this;
        }

        $this->todolist['where_logic'] = $logic;
        $this->todolist['where_built'] = false;

        $this->built = false;
        return $this;
    }


    public function whereMatch(array $array, $logic = 'AND')
    {
        $conditions = [];
        foreach ($array as $key => $value) {
            $conditions[] = [$key, '=', $value];
        }
        $this->whereMany($conditions, $logic);

        $this->built = false;
        return $this;
    }


    public function select(array $columnAsAliasArray = [])
    {
        $this->todolist['verb'] = 'SELECT';

        $this->todolist['select_column_list'] = $columnAsAliasArray;
        $this->todolist['select_column_list_built'] = false;

        $this->built = false;
        return $this;
    }


    public function delete()
    {
        $this->todolist['verb'] = 'DELETE';

        $this->built = false;
        return $this;
    }


    public function insert(array $record)
    {
        $this->todolist['verb'] = 'INSERT';

        $this->todolist['record'] = $record;

        $this->built = false;
        return $this;
    }


    public function update()
    {
        $this->todolist['verb'] = 'UPDATE';

        $this->built = false;
        return $this;
    }


    public function setValue($column, $value)
    {
        $this->todolist['set'][$column] = [
            'type'   => 'value',
            'column' => $column,
            'value'  => $value,
        ];

        $this->built = false;
        return $this;
    }


    public function setExpr($column, $expr, $parameters = [])
    {
        $this->todolist['set'][$column] = [
            'type'       => 'expr',
            'column'     => $column,
            'expr'       => $expr,
            'parameters' => $parameters,
        ];

        $this->built = false;
        return $this;
    }


    public function setFromTable($column, $tableB, $columnB, $colA, $colB, $checkExistsInWhere = true)
    {
        $this->todolist['set'][$column] = [
            'type'               => 'from_table',
            'column'             => $column,
            'tableB'             => $tableB,
            'columnB'            => $columnB,
            'colA'               => $colA,
            'colB'               => $colB,
            'checkExistsInWhere' => $checkExistsInWhere,
        ];

        $this->built = false;
        return $this;
    }


    public function join($tableB, $on, $parameters = [])
    {
        $this->todolist['join'][] = ['JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

        $this->built = false;
        return $this;
    }


    public function innerJoin($tableB, $on, $parameters = [])
    {
        $this->todolist['join'][] = ['INNER JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

        $this->built = false;
        return $this;
    }


    public function leftJoin($tableB, $on, $parameters = [])
    {
        $this->todolist['join'][] = ['LEFT JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

        $this->built = false;
        return $this;
    }


    public function rightJoin($tableB, $on, $parameters = [])
    {
        $this->todolist['join'][] = ['RIGHT JOIN', $tableB, $on, $parameters];
        $this->todolist['join_built'] = false;

        $this->built = false;
        return $this;
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

        $this->built = false;
        return $this;
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

        $this->built = false;
        return $this;
    }


    public function groupBy(array $columns)
    {
        $this->todolist['groupby'] = $columns;
        $this->todolist['groupby_built'] = false;

        $this->built = false;
        return $this;
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

        $this->built = false;
        return $this;
    }


    public function havingMany(array $conditions, $logic = 'AND')
    {
        $logic = strtoupper(trim($logic));

        $cond = new \stdClass();
        $cond->logic = $logic;
        $cond->items = $conditions;

        $this->todolist['having'][] = $cond;
        $this->todolist['having_built'] = false;

        $this->built = false;
        return $this;
    }


    public function havingLogic($logic)
    {
        $logic = strtoupper(trim($logic));

        if ($logic === $this->todolist['having_logic']) {
            return $this;
        }

        $this->todolist['having_logic'] = $logic;
        $this->todolist['having_built'] = false;

        $this->built = false;
        return $this;
    }


    public function distinct($distinct = true)
    {
        $this->todolist['distinct'] = $distinct;
        $this->todolist['distinct_built'] = false;

        $this->built = false;
        return $this;
    }


    /**
     * ORDER BY clause.
     *
     * @param type $columns
     * @return $this
     */
    public function orderBy($columns)
    {
        if (!isset($this->todolist['orderby'])) {
            $this->todolist['orderby'] = [];
        }

        $this->todolist['orderby'][] = $columns;
        $this->todolist['orderby_built'] = false;

        $this->built = false;
        return $this;
    }


    public function count(array $columns = null, $alias = null)
    {
        $this->todolist['verb'] = 'SELECT';

        $this->todolist['count'] = [$columns, $alias];
        $this->todolist['count_built'] = false;

        $this->built = false;
        return $this;
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

        $this->built = false;
        return $this;
    }
}
