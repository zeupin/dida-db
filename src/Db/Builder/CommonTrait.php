<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Builder;

/**
 * Common Trait
 */
trait CommonTrait
{
    /**
     * @var \Dida\Db\Db
     */
    protected $db = null;

    /**
     * @var int
     */
    protected $pdo_default_fetch_mode = null;


    protected function bracket($string)
    {
        return ($string === '') ? '' : "($string)";
    }


    protected function makeTable($table)
    {
        $table = trim($table);
        if ($table === '') {
            return '';
        }

        $table = $this->fsql($table);

        if ($this->isName($table)) {
            return $this->quoteTableName($table);
        } else {
            return $table;
        }
    }


    protected function makeTableAlias($alias)
    {
        if ($alias && is_string($alias)) {
            return $this->quoteTableName($alias);
        } else {
            return '';
        }
    }


    protected function makeTableFullname($table, $alias = null)
    {
        $table_quoted = $this->makeTable($table);
        $alias_quoted = $this->makeTableAlias($alias);

        if ($alias_quoted) {
            return "$table_quoted $alias_quoted";
        } else {
            return $table_quoted;
        }
    }


    protected function makeTableList(array $tablelist)
    {
        $array = [];
        foreach ($tablelist as $alias => $table) {
            if ($alias && is_string($alias)) {
                $array[] = $this->makeTableFullname($table, $alias);
            } else {
                $array[] = $this->makeTable($table);
            }
        }
        return implode(', ', $array);
    }


    /**
     * Determines to return alias or tablename
     */
    protected function makeTableRef($table, $alias = null)
    {
        if ($alias && is_string($alias)) {
            return $this->makeTable($alias);
        } else {
            return $this->makeTable($table);
        }
    }


    protected function makeColumn($column)
    {
        $column = trim($column);
        if ($column === '') {
            return '';
        }

        $column = $this->fsql($column);

        //case "column"
        if ($this->isName($column)) {
            return $this->quoteColumnName($column);
        }

        // case "table.column"
        if ($this->isNameWithDot($column)) {
            $array = explode('.', $column);
            return $this->quoteTableName($array[0]) . '.' . $this->quoteColumnName($array[1]);
        }

        // case else
        return $column;
    }


    protected function makeColumnAlias($alias)
    {
        if ($alias && is_string($alias)) {
            return $this->quoteColumnName($alias);
        } else {
            return '';
        }
    }


    protected function makeColumnFullname($column, $alias = null)
    {
        $column_quoted = $this->makeColumn($column);
        $alias_quoted = $this->makeColumnAlias($alias);

        if ($alias_quoted) {
            return "$column_quoted AS $alias_quoted";
        } else {
            return $column_quoted;
        }
    }


    /**
     * @param array $columns ['alias'=>'column',]
     */
    protected function makeColumnList(array $columns)
    {
        $array = [];
        foreach ($columns as $alias => $column) {
            if (is_string($alias)) {
                $array[] = $this->makeColumnFullname($column, $alias);
            } else {
                $array[] = $this->makeColumnFullname($column);
            }
        }

        return implode(', ', $array);
    }


    /**
     * Converts a formal SQL to a normal SQL.
     */
    protected function fsql($fsql)
    {
        $search = [];
        $replace = [];

        // prefix
        if ($this->formal_prefix) {
            $search[] = $this->formal_prefix;
            $replace[] = $this->prefix;
        }

        // execute
        return str_replace($search, $replace, $fsql);
    }


    protected function tableNormalize($table)
    {
        $s = trim($table);
        $temp = $this->splitNameAlias($s);

        return $this->makeTableFullname($temp['name'], $temp['alias']);
    }


    protected function columnNormalize($column)
    {
        $s = trim($column);
        $array = $this->splitNameAlias($s);

        return $this->makeColumnFullname($array['name'], $array['alias']);
    }


    /**
     * Converts a table/column name string to an array of a fixed format.
     */
    protected function splitNameAlias($string)
    {
        $result = preg_split('/\s+(AS|as|As)\s+/', $string, 2);
        $name = $result[0];
        $alias = (isset($result[1])) ? $result[1] : null;

        return [
            'name'  => $name,
            'alias' => $alias,
        ];
    }


    /**
     * Tests the specified $name is a valid table/column name.
     *
     * @param string $name
     * @return int 1 for yes, 0 for no
     */
    protected function isName($name)
    {
        if (!is_string($name) || ($name === '')) {
            return false;
        }
        return preg_match('/^[_A-Za-z]{1}\w*$/', $name);
    }


    /**
     * Tests the specified $name is a name splitted by a dot, like "tb_user.address"
     *
     * @param string $name
     * @return int 1 for yes, 0 for no
     */
    protected function isNameWithDot($name)
    {
        return preg_match('/^[_A-Za-z]{1}\w*\.[_A-Za-z]{1}\w*$/', $name);
    }


    /**
     * Converts simple name columns to full name columns.
     *
     * @param string $table
     * @param array $columns
     * @return array
     */
    protected function convertSimpleColumnsToFullnames($table, array $columns)
    {
        $new = [];
        foreach ($columns as $column) {
            $new[] = "$table.$column";
        }
        return $new;
    }


    protected function combineConditionParts($parts, $logic = 'AND')
    {
        $statement = array_column($parts, 'statement');
        $parameters = array_column($parts, 'parameters');

        $statement_cnt = count($statement);
        $statement = implode(" $logic ", $statement);
        if ($statement_cnt > 1) {
            $statement = "($statement)";
        }

        $parameters = $this->combineParameterArray($parameters);

        return [
            'statement'  => $statement,
            'parameters' => $parameters,
        ];
    }


    protected function combineParameterArray(array $parameters)
    {
        $ret = [];
        foreach ($parameters as $array) {
            $ret = array_merge($ret, array_values($array));
        }
        return $ret;
    }


    protected function quoteColumnValue($column, $value)
    {
        if (!array_key_exists($column, $this->def_basetype)) {
            throw new Exception("Invalid column name `$column`");
        }

        switch ($this->def_basetype[$column]) {
            case 'string':
                return $this->quoteString($value);
            case 'time':
                return $this->quoteTime($value);
            case 'numeric':
                return $value;
            default:
                return $value;
        }
    }
}
