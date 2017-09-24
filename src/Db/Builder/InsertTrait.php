<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Builder;

/**
 * InsertTrait
 */
trait InsertTrait
{
    /* INSERT */
    protected $insert_columns = [];
    protected $insert_record = [];
    protected $insert_statement = '';
    protected $insert_parameters = [];



    /* INSERT statement template */
    protected $INSERT_STMT = [
        0         => 'INSERT INTO ',
        'table'   => '',
        'columns' => '',
        1         => ' VALUES ',
        'values'  => '',
    ];
    protected $INSERT_PARAMS = [
        'values' => '',
    ];


    public function insert(array $record)
    {
        $this->buildChanged();

        $this->verb = 'INSERT';
        $this->insert_record = $record;

        return $this;
    }


    protected function build_INSERT()
    {
        $record = $this->insert_record;

        $columns = array_keys($record);
        $columns_statement = '(' . $this->makeColumnList($columns) . ')';

        $values = [];
        if ($this->preparemode) {
            $values_statement = '(' . implode(', ', array_fill(0, count($record), '?')) . ')';
            $values_parameters = array_values($record);
        } else {
            foreach ($record as $column => $value) {
                $values[$column] = $this->quoteColumnValue($column, $value);
            }
            $values_statement = '(' . implode(', ', $values) . ')';
            $values_parameters = [];
        }

        $statement = [
            'table'   => $this->quoteTableName($this->table),
            "columns" => $columns_statement,
            'values'  => $values_statement,
        ];
        $statement = array_merge($this->INSERT_STMT, $statement);

        $this->sql = implode('', $statement);
        $this->sql_parameters = $values_parameters;
    }
}
