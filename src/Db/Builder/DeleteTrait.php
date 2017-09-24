<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Builder;

/**
 * DeleteTrait
 */
trait DeleteTrait
{
    /* DELETE statement template */
    protected $DELETE_STMT = [
        0       => 'DELETE FROM ',
        'table' => '',
        'join'  => '',
        'where' => '',
    ];
    protected $DELETE_PARAMS = [
        'join'  => [],
        'where' => [],
    ];


    public function delete()
    {
        $this->buildChanged();

        $this->verb = 'DELETE';

        return $this;
    }


    protected function build_DELETE()
    {
        $this->build_WHERE();

        $statement = [
            'table' => $this->quoteTableName($this->table),
            'where' => $this->where_statement,
        ];
        $statement = array_merge($this->DELETE_STMT, $statement);
        $this->sql = implode('', $statement);

        $parameters = [
            'where' => $this->where_parameters,
        ];
        $parameters = array_merge($this->DELETE_PARAMS, $parameters);
        $this->sql_parameters = $this->combineParameterArray($parameters);
    }
}
