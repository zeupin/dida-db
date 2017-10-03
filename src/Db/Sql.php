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
     * @var \Dida\Db\Builder
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
     * Task list.
     *
     * @var array
     */
    protected $tasklist = [];


    public function __construct(&$db, array $options)
    {
        $this->db = $db;

        $this->base = array_merge($this->base, $options);
        $this->resetAll();
    }


    public function resetAll()
    {
        $this->tasklist = $this->base;
        return $this;
    }


    public function resetCount()
    {
        unset($this->tasklist['count'], $this->tasklist['count_built']);
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
            $this->builder = new Builder();
        }

        $result = $this->builder->build($this->tasklist);

        if ($result === false) {
            $this->statement = null;
            $this->parameters = null;
        } else {
            $this->statement = $result['statement'];
            $this->parameters = $result['parameters'];
        }

        //var_dump($this->tasklist);

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

        $this->tasklist['table'] = [
            'name'   => $name,
            'alias'  => $alias,
            'prefix' => $prefix,
        ];
        $this->tasklist['table_built'] = false;

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

        $this->tasklist['where'][] = $condition;
        $this->tasklist['where_built'] = false;

        $this->built = false;
        return $this;
    }


    public function whereMany(array $conditions, $logic = 'AND')
    {
        $logic = strtoupper(trim($logic));

        $cond = new \stdClass();
        $cond->logic = $logic;
        $cond->items = $conditions;

        $this->tasklist['where'][] = $cond;
        $this->tasklist['where_built'] = false;

        $this->built = false;
        return $this;
    }


    public function whereLogic($logic)
    {
        $logic = strtoupper(trim($logic));

        if ($logic === $this->tasklist['where_logic']) {
            return $this;
        }

        $this->tasklist['where_logic'] = $logic;
        $this->tasklist['where_built'] = false;

        $this->built = false;
        return $this;
    }


    public function find(array $array, $logic = 'AND')
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
        $this->tasklist['verb'] = 'SELECT';

        $this->tasklist['select_column_list'] = $columnAsAliasArray;
        $this->tasklist['select_column_list_built'] = false;

        $this->built = false;
        return $this;
    }


    public function delete()
    {
        $this->tasklist['verb'] = 'DELETE';

        $this->built = false;
        return $this;
    }


    public function insert(array $record)
    {
        $this->tasklist['verb'] = 'INSERT';

        $this->tasklist['record'] = $record;

        $this->built = false;
        return $this;
    }


    public function update()
    {
        $this->tasklist['verb'] = 'UPDATE';

        $this->built = false;
        return $this;
    }


    public function setValue($column, $value)
    {
        $this->tasklist['set'][$column] = [
            'type'   => 'value',
            'column' => $column,
            'value'  => $value,
        ];

        $this->built = false;
        return $this;
    }


    public function setExpr($column, $expr, $parameters = [])
    {
        $this->tasklist['set'][$column] = [
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
        $this->tasklist['set'][$column] = [
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
        $this->tasklist['join'][] = ['JOIN', $tableB, $on, $parameters];
        $this->tasklist['join_built'] = false;

        $this->built = false;
        return $this;
    }


    public function innerJoin($tableB, $on, $parameters = [])
    {
        $this->tasklist['join'][] = ['INNER JOIN', $tableB, $on, $parameters];
        $this->tasklist['join_built'] = false;

        $this->built = false;
        return $this;
    }


    public function leftJoin($tableB, $on, $parameters = [])
    {
        $this->tasklist['join'][] = ['LEFT JOIN', $tableB, $on, $parameters];
        $this->tasklist['join_built'] = false;

        $this->built = false;
        return $this;
    }


    public function rightJoin($tableB, $on, $parameters = [])
    {
        $this->tasklist['join'][] = ['RIGHT JOIN', $tableB, $on, $parameters];
        $this->tasklist['join_built'] = false;

        $this->built = false;
        return $this;
    }


    /**
     * @param string $column
     * @param mixed $value
     */
    public function inc($column, $value = 1)
    {
        if ($value < 0) {
            $value = abs($value);
            $this->setExpr($column, "$column - $value");
        } else {
            $this->setExpr($column, "$column + $value");
        }

        $this->built = false;
        return $this;
    }


    public function groupBy(array $columns)
    {
        $this->tasklist['groupby'] = $columns;
        $this->tasklist['groupby_built'] = false;

        $this->built = false;
        return $this;
    }


    public function having($condition, $data = [])
    {
        if (is_string($condition)) {
            if (substr($condition, 0, 1) !== '(') {
                $condition = "($condition)";
            }
            $condition = [$condition, 'RAW', $data];
        }

        $this->tasklist['having'][] = $condition;
        $this->tasklist['having_built'] = false;

        $this->built = false;
        return $this;
    }


    public function havingMany(array $conditions, $logic = 'AND')
    {
        $logic = strtoupper(trim($logic));

        $cond = new \stdClass();
        $cond->logic = $logic;
        $cond->items = $conditions;

        $this->tasklist['having'][] = $cond;
        $this->tasklist['having_built'] = false;

        $this->built = false;
        return $this;
    }


    public function havingLogic($logic)
    {
        $logic = strtoupper(trim($logic));

        if ($logic === $this->tasklist['having_logic']) {
            return $this;
        }

        $this->tasklist['having_logic'] = $logic;
        $this->tasklist['having_built'] = false;

        $this->built = false;
        return $this;
    }


    public function distinct($distinct = true)
    {
        $this->tasklist['distinct'] = $distinct;
        $this->tasklist['distinct_built'] = false;

        $this->built = false;
        return $this;
    }


    public function orderBy($columns)
    {
        if (!isset($this->tasklist['orderby'])) {
            $this->tasklist['orderby'] = [];
        }

        $this->tasklist['orderby'][] = $columns;
        $this->tasklist['orderby_built'] = false;

        $this->built = false;
        return $this;
    }


    public function count(array $columns = null, $alias = null)
    {
        $this->tasklist['verb'] = 'SELECT';

        $this->tasklist['count'] = [$columns, $alias];
        $this->tasklist['count_built'] = false;

        $this->built = false;
        return $this;
    }


    public function limit($limit)
    {
        $this->tasklist['limit'] = $limit;

        $this->built = false;
        return $this;
    }
}
