<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db;

use \Exception;

class Builder
{
    const VERSION = '20171113';

    protected $db = null;

    protected $localSchemaInfo = [];

    protected $tasklist = [];

    protected $mainTable = [];

    protected $ST = [];

    protected $PA = [];

    public static $opertor_set = [
        'RAW' => 'RAW',

        'EQ' => 'EQ',
        '='  => 'EQ',
        '==' => 'EQ',

        'NEQ' => 'NEQ',
        '<>'  => 'NEQ',
        '!='  => 'NEQ',

        'GT'  => 'GT',
        '>'   => 'GT',
        'EGT' => 'EGT',
        '>='  => 'EGT',
        'LT'  => 'LT',
        '<'   => 'LT',
        'ELT' => 'ELT',
        '<='  => 'ELT',

        'LIKE'     => 'LIKE',
        'NOT LIKE' => 'NOTLIKE',
        'NOTLIKE'  => 'NOTLIKE',

        'IN'     => 'IN',
        'NOT IN' => 'NOTIN',
        'NOTIN'  => 'NOTIN',

        'BETWEEN'     => 'BETWEEN',
        'NOT BETWEEN' => 'NOTBETWEEN',
        'NOTBETWEEN'  => 'NOTBETWEEN',

        'EXISTS'     => 'EXISTS',
        'NOT EXISTS' => 'NOTEXISTS',
        'NOTEXISTS'  => 'NOTEXISTS',

        'ISNULL'      => 'ISNULL',
        'NULL'        => 'ISNULL',
        'ISNOTNULL'   => 'ISNOTNULL',
        'IS NOT NULL' => 'ISNOTNULL',
        'NOTNULL'     => 'ISNOTNULL',
        'NOT NULL'    => 'ISNOTNULL',
    ];


    public function __construct(&$db)
    {
        $this->db = $db;
    }


    protected function init()
    {
        $this->ST = [];
        $this->PA = [];
    }


    public function build(&$tasklist)
    {
        $this->init();

        $this->tasklist = $tasklist;

        switch ($this->tasklist['verb']) {
            case 'SELECT':
                return $this->build_SELECT();
            case 'INSERT':
                return $this->build_INSERT();
            case 'UPDATE':
                return $this->build_UPDATE();
            case 'DELETE':
                return $this->build_DELETE();
            case 'TRUNCATE':
                return $this->build_TRUNCATE();
            default:
                throw new \Dida\Db\Exceptions\InvalidVerbException($this->tasklist['verb']);
        }
    }


    private function _________________________BUILD()
    {
    }


    protected function build_SELECT()
    {
        $this->prepare_SELECT();

        $STMT = [
            "SELECT\n    ",
            'columnlist' => &$this->ST['columnlist'],
            'from'       => &$this->ST['selectfrom'],
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
            'statement'  => implode('', $STMT),
            'parameters' => $this->util_combine_parameters($PARAMS),
        ];
    }


    protected function build_INSERT()
    {
        $this->prepare_INSERT();

        $STMT = [
            "INSERT INTO\n    ",
            'table'  => &$this->ST['table'],
            'record' => &$this->ST['record'],
        ];

        $PARAMS = [
            'record' => &$this->PA['record'],
        ];

        return [
            'statement'  => implode('', $STMT),
            'parameters' => $this->util_combine_parameters($PARAMS),
        ];
    }


    protected function build_UPDATE()
    {
        $this->prepare_UPDATE();

        $STMT = [
            "UPDATE\n    ",
            'table'   => &$this->ST['table'],
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
            'statement'  => implode('', $STMT),
            'parameters' => $this->util_combine_parameters($PARAMS),
        ];
    }


    protected function build_DELETE()
    {
        $this->prepare_DELETE();

        $STMT = [
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
            'statement'  => implode('', $STMT),
            'parameters' => $this->util_combine_parameters($PARAMS),
        ];
    }


    protected function build_TRUNCATE()
    {
        $this->prepare_TRUNCATE();

        $STMT = [
            'TRUNCATE TABLE ',
            'table' => &$this->ST['table'],
        ];

        return [
            'statement'  => implode('', $STMT),
            'parameters' => [],
        ];
    }


    private function _________________________PREPARE()
    {
    }


    protected function prepare_SELECT()
    {
        $this->clause_TABLE();
        $this->clause_COLUMNLIST();
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
        $this->clause_INSERT();
    }


    protected function prepare_UPDATE()
    {
        $this->clause_TABLE();
        $this->clause_JOIN();
        $this->clause_SET();
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


    private function _________________________TABLE()
    {
    }


    protected function clause_TABLE()
    {
        if (!$this->has('table')) {
            return;
        }

        extract($this->tasklist['table']);
        $name = trim($name);

        if (strpos($name, ',') === false) {
            $this->parse_table_one($name, $prefix);
        } else {
            $this->parse_table_many($name, $prefix);
        }
    }


    protected function parse_table_one($name, $prefix)
    {
        $t = $this->util_split_name_alias($name);
        $name = $t['name'];
        $alias = $t['alias'];

        $this->util_register_table($name, $alias, $prefix);

        $realname = $this->util_table_with_prefix($name, $prefix);

        $this->mainTable = [
            'name'  => $realname,
            'alias' => $alias,
        ];

        $this->ST['table'] = $realname;
        $this->ST['table_with_alias'] = $this->util_table_with_alias($realname, $alias);
        $this->ST['table_ref'] = $this->util_get_table_ref($realname, $alias);
        $this->ST['selectfrom'] = "\nFROM\n    " . $this->util_table_with_alias($realname, $alias);
    }


    protected function parse_table_many($name, $prefix)
    {
        $firstTable = null;
        $selectfrom = [];

        $tables = explode(',', $name);
        foreach ($tables as $table) {
            $table = trim($table);
            if ($table === '') {
                continue;
            }

            $t = $this->util_split_name_alias($table);
            $name = $t['name'];
            $alias = $t['alias'];

            $this->util_register_table($name, $alias, $prefix);

            $realname = $this->util_table_with_prefix($name, $prefix);

            if ($firstTable === null) {
                $firstTable = [
                    'name'  => $realname,
                    'alias' => $alias,
                ];
            }

            $selectfrom[] = $this->util_table_with_alias($realname, $alias);
        }

        $this->mainTable = $firstTable;

        $this->ST['table'] = $firstTable['name'];
        $this->ST['table_with_alias'] = $this->util_table_with_alias($firstTable['name'], $firstTable['alias']);
        $this->ST['table_ref'] = $this->util_get_table_ref($firstTable['name'], $firstTable['alias']);
        $this->ST['selectfrom'] = "\nFROM\n    " . implode(', ', $selectfrom);
    }


    private function _________________________COLUMNLIST()
    {
    }


    protected function clause_COLUMNLIST()
    {
        if (!$this->has('columnlist')) {
            $this->ST['columnlist'] = "{$this->mainTable["name"]}.*";
            return;
        }

        $final = '';

        $columnlist = $this->tasklist['columnlist'];
        foreach ($columnlist as $item) {
            $type = $item[0];
            switch ($type) {
                case 'raw':
                    $s = $item[1];
                    if ($final) {
                        $final .= ', ' . $s;
                    } else {
                        $final = $s;
                    }
                    break;

                case 'array':
                    $columnArray = $item[1];
                    $s = implode(', ', $columnArray);
                    if ($final) {
                        $final .= ', ' . $s;
                    } else {
                        $final = $s;
                    }
                    break;

                case 'distinct':
                    $final = "DISTINCT " . $final;
                    break;

                case 'count':
                    list($type, $columnlist_for_count, $alias) = $item;

                    if ($columnlist_for_count) {
                        if (is_array($columnlist_for_count)) {
                            $columnlist_for_count = implode(', ', $columnlist_for_count);
                        }
                        $final = $final . (($final) ? ", " : '');
                        $final .= "COUNT($columnlist_for_count)";
                    } else {
                        if ($final === '') {
                            $final = 'COUNT(*)';
                        } else {
                            $final = "COUNT($final)";
                        }
                    }

                    if ($alias) {
                        $final = "$final AS $alias";
                    }
                    break;
            }
        }

        $this->ST['columnlist'] = $final;
    }


    private function _________________________JOIN()
    {
    }


    protected function clause_JOIN()
    {
        if (!$this->has('join')) {
            $this->ST['join'] = '';
            $this->PA['join'] = [];
            return;
        }

        $st = [];
        $pa = [];

        $joins = $this->tasklist['join'];
        foreach ($joins as $join) {
            list($jointype, $table, $on, $parameters) = $join;

            $table_alias = $this->util_split_name_alias($table);

            $this->util_register_table($table_alias['name'], $table_alias['alias']);

            $tablename_with_prefix = $this->util_table_with_prefix($table_alias['name']);
            $table = $this->util_table_with_alias($tablename_with_prefix, $table_alias['alias']);

            $st[] = "\n{$jointype} {$table}\n    ON $on";
            $pa[] = $parameters;
        }

        $this->ST["join"] = implode("", $st);
        $this->PA['join'] = $this->util_combine_parameters($pa);
    }


    private function _________________________WHERE_and_HAVING()
    {
    }


    protected function clause_WHERE()
    {
        if (!$this->has('where')) {
            $this->ST['where'] = '';
            $this->PA['where'] = [];
            return;
        }

        $whereTree = $this->tasklist['where'];
        if (empty($whereTree->items)) {
            $this->ST['where'] = '';
            $this->PA['where'] = [];
            return;
        }

        $part = $this->parse_conditionTree($whereTree);

        $this->ST['where'] = "\nWHERE\n    " . $part['statement'];
        $this->PA['where'] = $part['parameters'];
        return;
    }


    protected function clause_HAVING()
    {
        if (!$this->has('having')) {
            $this->ST['having'] = '';
            $this->PA['having'] = [];
            return;
        }

        $havingTree = $this->tasklist['having'];
        if (empty($havingTree->items)) {
            $this->ST['having'] = '';
            $this->PA['having'] = [];
            return;
        }

        $part = $this->parse_conditionTree($havingTree);

        $this->ST['having'] = "\nHAVING\n    " . $part['statement'];
        $this->PA['having'] = $part['parameters'];
        return;
    }


    protected function parse_conditionTree(ConditionTree $conditionTree)
    {
        $parts = [];

        foreach ($conditionTree->items as $condition) {
            if ($condition instanceof ConditionTree) {
                $parts[] = $this->parse_conditionTree($condition);
            } else {
                $parts[] = $this->cond($condition);
            }
        }

        $stArray = array_column($parts, 'statement');
        $st = implode(" $conditionTree->logic ", $stArray);
        $st = "($st)";

        $paArray = array_column($parts, 'parameters');
        $pa = [];
        foreach ($paArray as $param) {
            $pa = array_merge($pa, $param);
        }

        return [
            'statement'  => $st,
            'parameters' => $pa,
        ];
    }


    protected function cond($condition)
    {
        $cnt = count($condition);

        if ($cnt === 3) {
            $column = array_shift($condition);
            $op = array_shift($condition);
            $data = array_shift($condition);
        } elseif ($cnt === 2) {
            $column = array_shift($condition);
            $op = array_shift($condition);
            $data = null;
        } elseif ($cnt === 4) {
            $column = array_shift($condition);
            $op = array_shift($condition);
            $data1 = array_shift($condition);
            $data2 = array_shift($condition);
            $data = [$data1, $data2];
        } else {
            throw new Exception("不正确的条件表达式" . var_export($condition, true));
        }

        $op = strtoupper($op);
        if (!array_key_exists($op, self::$opertor_set)) {
            throw new Exception("不支持此运算类型 \"$op\"" . var_export($condition, true));
        }

        $method_name = 'cond_' . self::$opertor_set[$op];
        return $this->$method_name($column, $op, $data);
    }


    protected function cond_RAW($column, $op, $data)
    {
        $column = "($column)";

        return [
            'statement'  => $column,
            'parameters' => $data,
        ];
    }


    protected function cond_COMPARISON($column, $op, $data)
    {
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
            throw new Exception('IN表达式不能为一个空数组');
        }

        $marks = implode(', ', array_fill(0, count($data), '?'));
        $part = [
            'statement'  => "($column $op ($marks))",
            'parameters' => array_values($data),
        ];
        return $part;
    }


    protected function cond_NOTIN($column, $op, $data)
    {
        return $this->cond_IN($column, 'NOT IN', $data);
    }


    protected function cond_LIKE($column, $op, $data)
    {
        if (is_scalar($data)) {
            $data = [$data];
        }

        $part = [
            'statement'  => "($column $op ?)",
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
        $part = [
            'statement'  => "($column IS NULL)",
            'parameters' => [],
        ];
        return $part;
    }


    protected function cond_ISNOTNULL($column, $op, $data = null)
    {
        $part = [
            'statement'  => "($column IS NOT NULL)",
            'parameters' => [],
        ];
        return $part;
    }


    protected function cond_EXISTS($column, $op, $data)
    {
        $part = [
            'statement'  => "(EXISTS ($column))",
            'parameters' => $data,
        ];
        return $part;
    }


    protected function cond_NOTEXISTS($column, $op, $data)
    {
        $part = [
            'statement'  => "(NOT EXISTS ($column))",
            'parameters' => $data,
        ];
        return $part;
    }


    private function _________________________GROUPBY_ORDERBY_LIMIT()
    {
    }


    protected function clause_GROUP_BY()
    {
        if (!$this->has('groupby')) {
            $this->ST['groupby'] = '';
            return;
        }

        $groupbys = $this->tasklist['groupby'];
        if (empty($groupbys)) {
            $this->ST['groupby'] = '';
            return;
        }

        $s = implode(', ', $groupbys);

        if ($groupbys) {
            $this->ST['groupby'] = "\nGROUP BY\n    $s";
        } else {
            $this->ST['groupby'] = '';
        }
        return;
    }


    protected function clause_ORDER_BY()
    {
        if (!$this->has('orderby')) {
            $this->ST['orderby'] = '';
            return;
        }

        $orderbys = $this->tasklist['orderby'];
        if (empty($orderbys)) {
            $this->ST['orderby'] = '';
            return;
        }

        $s = implode(', ', $orderbys);

        if ($orderbys) {
            $this->ST['orderby'] = "\nORDER BY\n    $s";
        } else {
            $this->ST['orderby'] = '';
        }
        return;
    }


    protected function clause_LIMIT()
    {
        if (!$this->has('limit')) {
            $this->ST['limit'] = '';
            return;
        }

        $limit = $this->tasklist['limit'];
        $this->ST['limit'] = "\nLIMIT $limit";
        return;
    }


    private function _________________________SET()
    {
    }


    protected function clause_SET()
    {
        $set = $this->tasklist['set'];

        $parts = [];
        foreach ($set as $item) {
            switch ($item['type']) {
                case 'value':
                    $parts[] = $this->set_Value($item);
                    break;
                case 'expr':
                    $parts[] = $this->set_Expr($item);
                    break;
                case 'from_table':
                    $parts[] = $this->set_FromTable($item);
                    break;
            }
        }

        $result = $this->util_combine_parts($parts, ",\n    ");

        $st = $result['statement'];
        $pa = $result['parameters'];

        $this->ST['set'] = "\nSET\n    " . $st;
        $this->PA['set'] = $pa;
    }


    protected function set_Value($item)
    {
        extract($item);

        return [
            'statement'  => "$column = ?",
            'parameters' => [$value],
        ];
    }


    protected function set_Expr($item)
    {
        extract($item);

        return [
            'statement'  => "$column = $expr",
            'parameters' => $parameters,
        ];
    }


    protected function set_FromTable($item)
    {
        extract($item);

        $table_ref = $this->ST['table_ref'];

        $target = "(SELECT $tableB.$columnB FROM $tableB WHERE $table_ref.$colA = $tableB.$colB)";
        $statement = "$column = $target";

        if ($checkExistsInWhere) {
            $this->tasklist['where']->items[] = ["(EXISTS $target)", 'RAW', []];
        }

        return [
            'statement'  => $statement,
            'parameters' => [],
        ];
    }


    private function _________________________INSERT()
    {
    }


    protected function clause_INSERT()
    {
        if (!$this->has('record')) {
            return;
        }

        $record = $this->tasklist['record'];
        $columns = array_keys($record);
        $values = array_values($record);

        $columnlist = '(' . implode(', ', $columns) . ')';
        $marklist = $this->util_make_marklist(count($columns), true);

        $this->ST['record'] = "{$columnlist}\nVALUES\n    {$marklist}";
        $this->PA['record'] = $values;
    }


    private function _________________________UTIL()
    {
    }


    protected function has($key)
    {
        return array_key_exists($key, $this->tasklist);
    }


    protected function util_combine_parts(array $parts, $stmt_glue)
    {
        $statement_array = array_column($parts, 'statement');
        $statement = implode($stmt_glue, $statement_array);

        $parameters_array = array_column($parts, 'parameters');
        $parameters = $this->util_combine_parameters($parameters_array);

        return [
            'statement'  => $statement,
            'parameters' => $parameters,
        ];
    }


    protected function util_combine_parameters(array $parameters)
    {
        $ret = [];
        foreach ($parameters as $array) {
            $ret = array_merge($ret, array_values($array));
        }
        return $ret;
    }


    protected function util_table_with_prefix($name, $prefix = null)
    {
        if (!is_string($prefix)) {
            $prefix = $this->tasklist['prefix'];
        }
        return $prefix . $name;
    }


    protected function util_split_name_alias($name_as_alias)
    {
        $name_as_alias = trim($name_as_alias);

        $i = strripos($name_as_alias, ' AS ');

        if ($i === false) {
            return [
                'name'  => $name_as_alias,
                'alias' => null,
            ];
        }

        $name = substr($name_as_alias, 0, $i);
        $alias = substr($name_as_alias, $i + 4);

        $name = trim($name);
        $alias = trim($alias);

        return [
            'name'  => $name,
            'alias' => $alias,
        ];
    }


    protected function util_table_with_alias($table, $alias)
    {
        if (is_string($alias) && $alias) {
            return $table . ' AS ' . $alias;
        } else {
            return $table;
        }
    }


    protected function util_col_with_alias($col_expr, $alias)
    {
        if (is_string($alias) && $alias) {
            return $col_expr . ' AS ' . $alias;
        } else {
            return $col_expr;
        }
    }


    protected function util_replace_swap_prefix($swapsql)
    {
        $prefix = $this->tasklist['prefix'];
        $swap_prefix = $this->tasklist['swap_prefix'];
        if ($swap_prefix) {
            return str_replace($swap_prefix, $prefix, $swapsql);
        } else {
            return $swapsql;
        }
    }


    protected function util_make_marklist($count, $braket = false)
    {
        if (is_array($count)) {
            $count = count($count);
        }

        $list = implode(', ', array_fill(0, $count, '?'));

        return ($braket) ? "($list)" : $list;
    }


    protected function util_get_table_ref($name, $alias)
    {
        return ($alias) ? $alias : $name;
    }


    protected function util_register_table($name, $alias, $prefix = null)
    {
    }
}
