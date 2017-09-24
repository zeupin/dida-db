<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Builder;

/**
 * UpdateTrait
 */
trait UpdateTrait
{
    /* UPDATE */
    protected $update_set = [];
    protected $update_set_statement = '';
    protected $update_set_parameters = [];


    /* UPDATE statement template */
    protected $UPDATE_STMT = [
        0       => 'UPDATE ',
        'table' => '',
        1       => ' SET ',
        'set'   => '',
        'join'  => '',
        'where' => '',
    ];
    protected $UPDATE_PARAMS = [
        'set'   => [],
        'join'  => [],
        'where' => [],
    ];


    public function update()
    {
        $this->buildChanged();

        $this->verb = 'UPDATE';

        return $this;
    }


    public function set($column, $new_value)
    {
        $this->buildChanged();

        $this->update_set[$column] = [Builder::SET_VALUE, $column, $new_value];

        return $this;
    }


    public function setExpr($column, $expr, $parameters = [])
    {
        $this->buildChanged();

        $this->update_set[$column] = [Builder::SET_EXPRESSION, $column, $expr, $parameters];

        return $this;
    }


    /**
     * Set column from other table.
     */
    public function setFromTable($columnA, $tableB, $columnB, $colA, $colB, $checkExistsInWhere = true)
    {
        $this->buildChanged();

        $s = $this->splitNameAlias($tableB);
        $tableB = $s['name'];
        $aliasB = $s['alias'];

        $tableBFullname_quoted = $this->makeTableFullname($tableB, $aliasB);

        $refA_quoted = $this->makeTableRef($this->table, $this->table_alias);
        $refB_quoted = $this->makeTableRef($tableB, $aliasB);

        $columnB_quoted = $this->makeColumn($columnB);
        $colA_quoted = $this->makeColumn($colA);
        $colB_quoted = $this->makeColumn($colB);

        $tpl = [
            '(SELECT ',
            'tableB.columnB' => "$refB_quoted.$columnB_quoted",
            ' FROM ',
            'tableB'         => $tableBFullname_quoted,
            ' WHERE ',
            'tableA.colA'    => "$refA_quoted.$colA_quoted",
            ' = ',
            'tableB.colB'    => "$refB_quoted.$colB_quoted",
            ')',
        ];
        $statement = implode('', $tpl);

        $this->update_set[$columnA] = [Builder::SET_EXPRESSION, $columnA, $statement, []];

        if ($checkExistsInWhere) {
            $this->where(["EXISTS $statement", 'RAW', []]);
        }

        return $this;
    }


    /**
     * @param string $column
     * @param mixed $value
     */
    public function inc($column, $value = 1)
    {
        $this->buildChanged();

        $this->verb = 'UPDATE';

        $column_quoted = $this->makeColumnFullname($column);
        $plus = ($value < 0) ? '' : '+';

        $this->update_set[$column] = [Builder::SET_EXPRESSION, $column, "$column_quoted{$plus}$value"];

        return $this;
    }


    protected function build_UPDATE()
    {
        $this->build_WHERE();
        $this->build_UPDATE_SET();

        // build statement
        $statement = [
            'table' => $this->quoteTableName($this->table),
            'set'   => $this->update_set_statement,
            'join'  => '',
            'where' => $this->where_statement,
        ];
        $statement = array_merge($this->UPDATE_STMT, $statement);
        $this->sql = implode('', $statement);

        // build parameters
        $parameters = [
            'set'   => $this->update_set_parameters,
            'where' => $this->where_parameters,
        ];
        $parameters = array_merge($this->UPDATE_PARAMS, $parameters);
        $this->sql_parameters = $this->combineParameterArray($parameters);
    }


    protected function build_UPDATE_SET()
    {
        $statement = [];
        $parameters = [];

        foreach ($this->update_set as $item) {
            list($type, $column) = $item;
            $column_quoted = $this->makeColumn($column);

            switch ($type) {
                case Builder::SET_VALUE:
                    list($type, $column, $new_value) = $item;
                    if ($this->preparemode) {
                        $statement[] = $column_quoted . ' = ?';
                        $parameters[] = $new_value;
                    } else {
                        $new_value = $this->quoteColumnValue($column, $new_value);
                        $statement[] = $column_quoted . ' = ' . $new_value;
                        $parameters = [];
                    }
                    break;

                case Builder::SET_EXPRESSION:
                    switch (count($item)) {
                        case 3:
                            list($type, $column, $expr) = $item;
                            $param = [];
                            break;
                        case 4:
                            list($type, $column, $expr, $param) = $item;
                            break;
                        default:
                            throw new Exception('Invalid parameters number');
                    }
                    if ($this->preparemode) {
                        $statement[] = $column_quoted . ' = ' . $expr;
                        $parameters = array_merge($parameters, $param);
                    } else {
                        $statement[] = $column_quoted . ' = ' . $expr;
                        $parameters = [];
                    }
                    break;
            }
        }

        $this->update_set_statement = implode(', ', $statement);
        $this->update_set_parameters = $parameters;
    }
}
