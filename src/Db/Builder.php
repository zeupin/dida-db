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
    protected $input  = [];
    protected $dict   = [
        'table' => '',
    ];
    protected $final  = [];
    protected $output = [
        'statement'  => '',
        'parameters' => [],
    ];


    /**
     * Builds.
     *
     * @param type $input
     */
    public function build(&$input)
    {
        $this->done = null;

        $this->input  = &$input;
        $this->output = [];

        switch ($this->input['verb']) {
            case 'SELECT':
                return $this->build_SELECT();
        }
    }


    protected function build_SELECT()
    {
        $this->part_Table();
        $this->part_SelectColumnList();

        $tpl = [
            'SELECT ',
            'select_column_list' => $this->final['select_column_list'],
            ' FROM ',
            'table'              => $this->final['table'],
        ];

        $statement  = implode('', $tpl);
        $parameters = [];

        return [
            'statement'  => $statement,
            'parameters' => $parameters,
        ];
    }


    protected function part_SelectColumnList()
    {
        if (!isset($this->input['select_column_list'])) {
            $this->input['select_column_list']       = [];
            $this->input['select_column_list_built'] = true;
            $this->final['select_column_list']       = $this->getAllColumnNames($this->dict['table']['name']);
            return;
        }

        if ($this->input['select_column_list_built']) {
            return;
        }

        $columnlist = $this->input['select_column_list'];
        if (empty($columnlist)) {
            $this->final['select_column_list']       = $this->getAllColumnNames($this->dict['table']['name']);
            $this->input['select_column_list_built'] = true;
            return;
        }
    }


    protected function getAllColumnNames($table)
    {
        return '*';
    }


    protected function part_Table()
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
        $this->dict['table']                  = [
            'name'  => $name,
            'alias' => $alias,
        ];
        $this->dict['table']['ref']           = $this->tableRef($this->dict['table']['name'], $this->dict['table']['alias']);
        $this->dict['table']['name_as_alias'] = $this->tableNameAsAlias($this->dict['table']['name'], $this->dict['table']['alias']);

        /* final */
        $this->final['table']       = $this->dict['table']['name_as_alias'];
        $this->input['table_built'] = true;
        return;
    }


    protected function combineParameterArray(array $parameters)
    {
        $ret = [];
        foreach ($parameters as $array) {
            $ret = array_merge($ret, array_values($array));
        }
        return $ret;
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
        $prefix  = $this->input['prefix'];
        $vprefix = $this->input['vprefix'];
        if ($vprefix) {
            return str_replace($vprefix, $prefix, $vsql);
        } else {
            return $vsql;
        }
    }
}
