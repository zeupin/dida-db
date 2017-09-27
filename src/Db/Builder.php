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
    protected $dictStatement = [];
    protected $dictParameters = [];

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
            case 'DELETE':
                return $this->build_DELETE();
            case 'INSERT':
                return $this->build_INSERT();
            case 'UPDATE':
                return $this->build_UPDATE();
            default :
                throw new Exception("Invalid build verb: {$this->input['verb']}");
        }
    }


    protected function prepare_INSERT()
    {
        $this->clause_TABLE();
        $this->clause_JOIN();

        $record = &$this->input['record'];
        $record = $this->pickItemsWithKey($record);
        $columns = array_keys($record);
        $values = array_values($record);

        $this->dictStatement['insert_column_list'] = '(' . implode(', ', $columns) . ')';
        $this->dictStatement['insert_values'] = $this->makeQuestionMarkList($columns, true);
        $this->dictParameters['insert_values'] = $values;
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


    protected function build_INSERT()
    {
        $this->prepare_INSERT();

        /* INSERT statement template */
        $TPL = [
            'INSERT INTO ',
            'table'   => $this->dictStatement['table'],
            'columns' => $this->dictStatement['insert_column_list'],
            ' VALUES ',
            'values'  => $this->dictStatement['insert_values'],
        ];
        $PARAMS = [
            'values' => $this->dictParameters['insert_values'],
        ];

        return [
            'statement'  => implode('', $TPL),
            'parameters' => $this->combineParameterArray($PARAMS),
        ];
    }


    protected function prepare_DELETE()
    {
        $this->clause_TABLE();
        $this->clause_JOIN();
        $this->clause_WHERE();
    }


    protected function build_DELETE()
    {
        $this->prepare_DELETE();

        $TPL = [
            'DELETE FROM ',
            'table' => $this->dictStatement['table'],
            'join'  => $this->dictStatement['join'],
            'where' => $this->dictStatement['where'],
        ];
        $PARAMS = [
            'where' => $this->dictParameters['where']
        ];

        return [
            'statement'  => implode('', $TPL),
            'parameters' => $this->combineParameterArray($PARAMS),
        ];
    }


    protected function prepare_SELECT()
    {
        $this->clause_TABLE();
        $this->clause_SELECT_COLUMN_LIST();
        $this->clause_JOIN();
        $this->clause_WHERE();
    }


    protected function build_SELECT()
    {
        $this->prepare_SELECT();

        $TPL = [
            'SELECT',
            'select_column_list' => "\n    " . $this->dictStatement['select_column_list'],
            "\nFROM",
            'table'              => "\n    " . $this->dictStatement['table'],
            'join'               => $this->dictStatement['join'],
            'where'              => $this->dictStatement['where'],
        ];
        $PARAMS = [
            'where' => $this->dictParameters['where']
        ];

        return [
            'statement'  => implode('', $TPL),
            'parameters' => $this->combineParameterArray($PARAMS),
        ];
    }


    protected function clause_SELECT_COLUMN_LIST()
    {
        if (!isset($this->input['select_column_list'])) {
            $this->input['select_column_list'] = [];
            $this->input['select_column_list_built'] = true;
            $this->dictStatement['select_column_list'] = $this->getAllColumnNames($this->dict['table']['name']);
            return;
        }

        if ($this->input['select_column_list_built']) {
            return;
        }

        $columnlist = &$this->input['select_column_list'];
        if (empty($columnlist)) {
            $this->dictStatement['select_column_list'] = $this->getAllColumnNames($this->dict['table']['name']);
            $this->input['select_column_list_built'] = true;
            return;
        }

        $this->dictStatement['select_column_list'] = $this->combineColumnList($columnlist);
        $this->input['select_column_list_built'] = true;
        return;
    }


    /**
     * @param array $columns ['alias'=>'column',]
     */
    protected function combineColumnList(array $columns, $table = null)
    {
        $array = [];
        foreach ($columns as $alias => $column) {
            if (is_string($table) && $table) {
                $column = "$table.$column";
            }

            if (is_string($alias)) {
                $array[] = "$column AS $alias";
            } else {
                $array[] = $column;
            }
        }

        return implode(', ', $array);
    }


    protected function getAllColumnNames($table)
    {
        return '*';
    }


    protected function clause_TABLE()
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

        /* dictStatement */
        $this->dictStatement['table'] = $this->dict['table']['name_as_alias'];
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
    protected function clause_WHERE()
    {
        if (!isset($this->input['where_built'])) {
            $this->input['where_built'] = true;
            $this->dictStatement['where'] = '';
            $this->dictParameters['where'] = [];
            return;
        }

        // already done
        if ($this->input['where_built']) {
            return;
        }

        if (!isset($this->input['where'])) {
            $this->input['where_built'] = true;
            $this->dictStatement['where'] = '';
            $this->dictParameters['where'] = [];
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
        $this->combineParts($parts, "\n    $logic ", $statement, $parameters);
        if ($statement) {
            $this->dictStatement['where'] = "\nWHERE\n    $statement";
            $this->dictParameters['where'] = $parameters;
        }

        $this->input['where_built'] = true;
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


    protected function build_UPDATE()
    {
        $this->prepare_UPDATE();

        $TPL = [
            'UPDATE ',
            'table' => "\n    " . $this->dictStatement['table'],
            "\nSET",
            'set'   => "\n    " . $this->dictStatement['set'],
            'join'  => $this->dictStatement['join'],
            'where' => $this->dictStatement['where'],
        ];
        $PARAMS = [
            'set'   => $this->dictParameters['set'],
            'where' => $this->dictParameters['where'],
        ];

        return [
            'statement'  => implode('', $TPL),
            'parameters' => $this->combineParameterArray($PARAMS),
        ];
    }


    protected function prepare_UPDATE()
    {
        $this->clause_TABLE();
        $this->clause_SET();
        $this->clause_JOIN();
        $this->clause_WHERE();
    }


    protected function clause_SET()
    {
        $set = $this->input['set'];

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

        $this->dictStatement['set'] = $statement;
        $this->dictParameters['set'] = $parameters;
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
        $tableB = $this->vsql($tableB);

        $tableRef = $this->dict['table']['ref'];

        $target = "(SELECT $tableB.$columnB FROM $tableB WHERE $tableRef.$colA = $tableB.$colB)";
        $statement = "$column = $target";

        if ($checkExistsInWhere) {
            $this->input['where']['insert_if_exists'] = ["(EXISTS $target)", 'RAW', []];
        }

        return [
            'statement'  => $statement,
            'parameters' => [],
        ];
    }


    protected function clause_JOIN()
    {
        $parts = [];
        $joins = $this->input['join'];
        foreach ($joins as $join) {
            list($jointype, $table, $on) = $join;
            $table = $this->vsql($table);
            $on = $this->vsql($on);
            $parts[] = "\n$jointype {$table}\n    ON $on";
        }
        $this->dictStatement["join"] = implode("", $parts);
    }
}
