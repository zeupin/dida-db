<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * SQL
 */
class SQL
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
     *
     * @var array
     */
    protected $input = [];


    public function __construct(&$db, array $base)
    {
        $this->db = $db;

        $this->base = array_merge($this->base, $base);
        $this->reset();
    }


    public function reset()
    {
        $this->input = $this->base;
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

        $result = $this->builder->build($this->input);

        if ($result === false) {
            $this->statement = null;
            $this->parameters = null;
        } else {
            $this->statement = $result['statement'];
            $this->parameters = $result['parameters'];
        }

        var_dump($this->input);

        $this->built = true;
        return $this;
    }


    public function sql($statement, $parameters = [])
    {
        $this->reset();

        $this->statement = $statement;
        $this->parameters = $parameters;

        $this->built = true;
        return $this;
    }


    public function table($name, $alias = null, $prefix = null)
    {
        $this->reset();

        $this->input['table'] = [
            'name'   => $name,
            'alias'  => $alias,
            'prefix' => $prefix,
        ];
        $this->input['table_built'] = false;

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

        $this->input['where'][] = $condition;
        $this->input['where_built'] = false;

        $this->built = false;
        return $this;
    }


    public function whereMany(array $conditions, $logic = 'AND')
    {
        $logic = strtoupper(trim($logic));

        $cond = new \stdClass();
        $cond->logic = $logic;
        $cond->items = $conditions;

        $this->input['where'][] = $cond;
        $this->input['where_built'] = false;

        $this->built = false;
        return $this;
    }


    public function whereLogic($logic)
    {
        $logic = strtoupper(trim($logic));

        if ($logic === $this->input['where_logic']) {
            return $this;
        }

        $this->input['where_logic'] = $logic;
        $this->input['where_built'] = false;

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


    public function select(array $columnlist = [])
    {
        $this->input['verb'] = 'SELECT';

        $this->input['select_column_list'] = $columnlist;
        $this->input['select_column_list_built'] = false;

        $this->built = false;
        return $this;
    }


    public function delete()
    {
        $this->input['verb'] = 'DELETE';

        $this->built = false;
        return $this;
    }


    public function insert(array $record)
    {
        $this->input['verb'] = 'INSERT';

        $this->input['record'] = $record;

        $this->built = false;
        return $this;
    }


    public function update()
    {
        $this->input['verb'] = 'UPDATE';

        $this->built = false;
        return $this;
    }


    protected function set_Begin()
    {
        if (!isset($this->input['set'])) {
            $this->input['set'] = [];
        }
        $this->input['set_built'] = false;
    }


    public function setValue($column, $value)
    {
        $this->set_Begin();

        $this->input['set'][$column] = [
            'type'   => 'value',
            'column' => $column,
            'value'  => $value,
        ];

        $this->built = false;
        return $this;
    }


    public function setExpr($column, $expr, $parameters = [])
    {
        $this->set_Begin();

        $this->input['set'][$column] = [
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
        $this->set_Begin();

        $this->input['set'][$column] = [
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
        $this->input['join'][] = ['JOIN', $tableB, $on, $parameters];
        $this->input['join_built'] = false;

        $this->built = false;
        return $this;
    }


    public function innerJoin($tableB, $on, $parameters = [])
    {
        $this->input['join'][] = ['INNER JOIN', $tableB, $on, $parameters];
        $this->input['join_built'] = false;

        $this->built = false;
        return $this;
    }


    public function leftJoin($tableB, $on, $parameters = [])
    {
        $this->input['join'][] = ['LEFT JOIN', $tableB, $on, $parameters];
        $this->input['join_built'] = false;

        $this->built = false;
        return $this;
    }


    public function rightJoin($tableB, $on, $parameters = [])
    {
        $this->input['join'][] = ['RIGHT JOIN', $tableB, $on, $parameters];
        $this->input['join_built'] = false;

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
        $this->input['groupby'] = $columns;
        $this->input['groupby_built'] = false;

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

        $this->input['having'][] = $condition;
        $this->input['having_built'] = false;

        $this->built = false;
        return $this;
    }


    public function havingMany(array $conditions, $logic = 'AND')
    {
        $logic = strtoupper(trim($logic));

        $cond = new \stdClass();
        $cond->logic = $logic;
        $cond->items = $conditions;

        $this->input['having'][] = $cond;
        $this->input['having_built'] = false;

        $this->built = false;
        return $this;
    }


    public function havingLogic($logic)
    {
        $logic = strtoupper(trim($logic));

        if ($logic === $this->input['having_logic']) {
            return $this;
        }

        $this->input['having_logic'] = $logic;
        $this->input['having_built'] = false;

        $this->built = false;
        return $this;
    }
}
