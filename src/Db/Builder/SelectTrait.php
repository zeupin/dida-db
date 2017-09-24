<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Builder;

/**
 * WhereTrait
 */
trait SelectTrait
{
    /* SELECT */
    protected $select_columnlist = [];
    protected $select_columnlist_statement = '';
    protected $select_distinct = false;
    protected $select_distinct_statement = '';
    protected $select_orderby_columns = '';
    protected $select_orderby_statement = '';
    protected $join = [];
    protected $join_statement = '';
    protected $groupby_columnlist = [];
    protected $groupby_statement = '';
    protected $having_conditions = [];
    protected $having_logic = '';
    protected $having_statement = '';



    /* SELECT statement template */
    protected $SELECT_STMT = [
        0          => 'SELECT ',
        'distinct' => '',
        'columns'  => '',
        1          => ' FROM ',
        'table'    => '',
        'join'     => '',
        'where'    => '',
        'groupby'  => '',
        'having'   => '',
        'orderby'  => '',
        'limit'    => '',
        'union'    => '',
    ];
    protected $SELECT_PARAMS = [
        'columns' => [],
        'table'   => [],
        'join'    => [],
        'where'   => [],
        'groupby' => [],
        'having'  => [],
        'orderby' => [],
        'limit'   => [],
        'union'   => [],
    ];


    public function select(array $columnlist = [])
    {
        $this->buildChanged();

        $this->verb = 'SELECT';
        $this->select_columnlist = $columnlist;

        return $this;
    }


    public function distinct($flag = true)
    {
        $this->buildChanged();

        $this->select_distinct = $flag;

        if ($flag) {
            $this->select_distinct_statement = 'DISTINCT ';
        } else {
            $this->select_distinct_statement = '';
        }

        return $this;
    }


    protected function joinCommon($joinType, $tableB, $colA, $rel, $colB)
    {
        $this->buildChanged();

        $tpl = [
            ' ',
            'join type' => $joinType,
            ' ',
            'table'     => $this->tableNormalize($tableB),
            ' ON ',
            'colA'      => $this->columnNormalize($colA),
            ' ',
            'rel'       => $rel,
            ' ',
            'colB'      => $this->columnNormalize($colB),
        ];
        $this->join[] = implode('', $tpl);

        return $this;
    }


    public function join($tableB, $colA, $rel, $colB)
    {
        return $this->joinCommon('INNER JOIN', $tableB, $colA, $rel, $colB);
    }


    public function innerJoin($tableB, $colA, $rel, $colB)
    {
        return $this->joinCommon('INNER JOIN', $tableB, $colA, $rel, $colB);
    }


    public function leftJoin($tableB, $colA, $rel, $colB)
    {
        return $this->joinCommon('LEFT JOIN', $tableB, $colA, $rel, $colB);
    }


    public function rightJoin($tableB, $colA, $rel, $colB)
    {
        return $this->joinCommon('RIGHT JOIN', $tableB, $colA, $rel, $colB);
    }


    public function groupBy(array $columnlist)
    {
        $this->buildChanged();

        $this->groupby_columnlist = $columnlist;

        return $this;
    }


    public function having($condition, $logic = null)
    {
        $this->buildChanged();

        if ($logic && is_string($logic)) {
            $this->having_conditions = $condition;
            $this->having_logic = " $logic ";
        } else {
            $this->having_conditions[] = $condition;
            $this->having_logic = '';
        }

        return $this;
    }


    public function count($columns = ['*'], $alias = null)
    {
        $this->buildChanged();

        $this->verb = 'SELECT';

        $list = $this->makeColumnList($columns);

        if (is_string($alias)) {
            $this->select_columnlist = [$alias => "COUNT($list)"];
        } else {
            $this->select_columnlist = ["COUNT($list)"];
        }

        return $this;
    }


    /**
     * @param array|string $columns
     */
    public function orderBy($columns = '')
    {
        $this->buildChanged();

        if (is_array($columns)) {
            $std = [];
            foreach ($columns as $key => $item) {
                if (is_numeric($key)) {
                    $column = $item;
                    $std[$column] = $this->makeColumn($column);
                } else {
                    $order = strtoupper($item);
                    $column = $key;
                    if ($order === 'ASC' || $order === '') {
                        $order = '';
                    }
                    if ($order === 'DESC') {
                        $column_quoted = $this->makeColumn($column);
                        $std[$column] = "$column_quoted DESC";
                    } else {
                        throw new Exception("Invalid ORDERBY statement: $column $order");
                    }
                }
            }
            $this->select_orderby_columns = implode(', ', $std);
            return $this;
        } elseif (is_string($columns)) {
            $this->select_orderby_columns = $this->makeColumn($columns);
        }

        return $this;
    }


    protected function build_SELECT()
    {
        $this->build_SELECT_COLUMNS();
        $this->build_JOIN();
        $this->build_WHERE();
        $this->build_GROUP_BY();
        $this->build_HAVING();
        $this->build_ORDER_BY();

        $statement = [
            'table'    => $this->makeTableFullname($this->table, $this->table_alias),
            'distinct' => $this->select_distinct_statement,
            "columns"  => $this->select_columnlist_statement,
            'join'     => $this->join_statement,
            'where'    => $this->where_statement,
            'groupby'  => $this->groupby_statement,
            'having'   => $this->having_statement,
            'orderby'  => $this->select_orderby_statement,
        ];
        $statement = array_merge($this->SELECT_STMT, $statement);
        $this->sql = implode('', $statement);

        $parameters = [
            'where' => $this->where_parameters,
        ];
        $parameters = array_merge($this->SELECT_PARAMS, $parameters);
        $this->sql_parameters = $this->combineParameterArray($parameters);
    }


    protected function build_SELECT_COLUMNS()
    {
        if (empty($this->select_columnlist)) {
            if (empty($this->join)) {
                $ref = ($this->table_alias && is_string($this->table_alias)) ? $this->table_alias : $this->table;
                $columnlist = $this->convertSimpleColumnsToFullnames($ref, $this->def_columns);
                $this->select_columnlist_statement = $this->makeColumnList($columnlist);
            } else {
                $this->select_columnlist_statement = $this->makeColumnList($this->def_columns);
            }
        } else {
            $this->select_columnlist_statement = $this->makeColumnList($this->select_columnlist);
        }
    }


    protected function build_JOIN()
    {
        $this->join_statement = implode('', $this->join);
    }


    protected function build_GROUP_BY()
    {
        $expr = $this->makeColumnList($this->groupby_columnlist);
        if ($expr === '') {
            $this->groupby_statement = '';
        } else {
            $this->groupby_statement = ' GROUP BY ' . $expr;
        }
    }


    protected function build_HAVING()
    {
        $expr = implode($this->having_logic, $this->having_conditions);
        if ($expr === '') {
            $this->having_statement = '';
        } else {
            $this->having_statement = ' HAVING ' . $expr;
        }
    }


    protected function build_ORDER_BY()
    {
        if ($this->select_orderby_columns === '') {
            $this->select_orderby_statement = '';
        } else {
            $this->select_orderby_statement = ' ORDER BY ' . $this->select_orderby_columns;
        }
    }
}
