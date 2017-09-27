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

        return $this;
    }


    public function find(array $array)
    {
        $conditions = [];
        foreach ($array as $key => $value) {
            $conditions[] = [$key, '=', $value];
        }
        $this->whereMany($conditions);

        return $this;
    }
}
