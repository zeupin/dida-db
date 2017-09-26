<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * Builder
 */
class Builder
{
    protected $input = [];
    protected $dict = [
        'table' => '',
    ];
    protected $stmtdict = [];
    protected $paramdict = [];
    protected $output = [
        'statement'  => '',
        'parameters' => [],
    ];

    /*
     * All supported operater set.
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


    /**
     * Builds.
     *
     * @param type $input
     */
    public function build(&$input)
    {
        $this->done = null;

        $this->input = &$input;
        $this->output = [];

        switch ($this->input['verb']) {
            case 'SELECT':
                return $this->build_SELECT();
        }
    }


    protected function build_SELECT()
    {
        $this->part_TABLE();
        $this->part_SELECT_COLUMN_LIST();
        $this->part_WHERE();

        $tpl = [
            'SELECT ',
            'select_column_list' => $this->stmtdict['select_column_list'],
            ' FROM ',
            'table'              => $this->stmtdict['table'],
            'where'              => $this->stmtdict['where'],
        ];
        $params = [
            'where' => $this->paramdict['where']
            ];

        return [
            'statement'  => implode('', $tpl),
            'parameters' => $this->combineParameterArray($params),
        ];
    }


    protected function part_SELECT_COLUMN_LIST()
    {
        if (!isset($this->input['select_column_list'])) {
            $this->input['select_column_list'] = [];
            $this->input['select_column_list_built'] = true;
            $this->stmtdict['select_column_list'] = $this->getAllColumnNames($this->dict['table']['name']);
            return;
        }

        if ($this->input['select_column_list_built']) {
            return;
        }

        $columnlist = $this->input['select_column_list'];
        if (empty($columnlist)) {
            $this->stmtdict['select_column_list'] = $this->getAllColumnNames($this->dict['table']['name']);
            $this->input['select_column_list_built'] = true;
            return;
        }
    }


    protected function getAllColumnNames($table)
    {
        return '*';
    }


    protected function part_TABLE()
    {
        // already done
        if ($this->input['table_built']) {
            return;
        }

        // built, name, alias, prefix
        extract($this->input['table']);

        if (!is_string($prefix)) {
            $prefix = $this->input['prefix'];
        }

        $name = $prefix . $name;

        if (!is_string($alias)) {
            $alias = null;
        }

        /* dict */
        $this->dict['table'] = [
            'name'  => $name,
            'alias' => $alias,
        ];
        $this->dict['table']['ref'] = $this->tableRef($this->dict['table']['name'], $this->dict['table']['alias']);
        $this->dict['table']['name_as_alias'] = $this->tableNameAsAlias($this->dict['table']['name'], $this->dict['table']['alias']);

        /* stmtdict */
        $this->stmtdict['table'] = $this->dict['table']['name_as_alias'];
        $this->input['table_built'] = true;
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
     * Converts a vitrual SQL to a normal SQL.
     */
    protected function vsql($vsql)
    {
        $prefix = $this->input['prefix'];
        $vprefix = $this->input['vprefix'];
        if ($vprefix) {
            return str_replace($vprefix, $prefix, $vsql);
        } else {
            return $vsql;
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

        if (!is_array($condition)) {
            throw new Exception("Invalid condition format");
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
            'statement'  => $column,
            'parameters' => $data,
        ];
    }


    protected function cond_COMPARISON($column, $op, $data)
    {
        $column = $this->vsql($column);
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

        $column = $this->vsql($column);
        $marks = implode(', ', array_fill(0, count($data), '?'));
        $part = [
            'statement'  => '($column $op ($marks))',
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
        $column = $this->vsql($column);
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
        $column = $this->vsql($column);
        $part = [
            'statement'  => "$column $op ? AND ?",
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
        $column = $this->vsql($column);
        $part = [
            'statement'  => "$column IS NULL",
            'parameters' => [],
        ];
        return $part;
    }


    protected function cond_ISNOTNULL($column, $op, $data = null)
    {
        $column = $this->vsql($column);
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
    protected function part_WHERE()
    {
        // already done
        if ($this->input['where_built']) {
            return;
        }

        if (!isset($this->input['where'])) {
            $this->input['where_built'] = true;
            $this->stmtdict['where'] = '';
            $this->paramdict['where'] = [];
            return;
        }

        $conditions = $this->input['where'];
        $logic = $this->input['where_logic'];

        $parts = [];
        foreach ($conditions as $condition) {
            $parts[] = $this->cond($condition);
        }

        $statement = '';
        $parameters = [];
        $this->combineParts($parts, $logic, $statement, $parameters);
        if ($statement) {
            $this->stmtdict['where'] = " WHERE $statement";
            $this->paramdict['where'] = $parameters;
        }

        $this->input['where_built'] = true;
        return;
    }


    protected function combineParts($parts, $logic, &$statement, &$parameters)
    {
        $statement_array = array_column($parts, 'statement');
        $statement = implode(" $logic ", $statement_array);

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
}
