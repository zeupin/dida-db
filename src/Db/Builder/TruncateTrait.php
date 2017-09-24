<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db\Builder;

/**
 * DeleteTrait
 */
trait TruncateTrait
{


    public function truncate()
    {
        $this->buildChanged();

        $this->verb = 'TRUNCATE';

        return $this;
    }


    protected function build_TRUNCATE()
    {
        $this->sql = 'TRUNCATE TABLE ' . $this->quoteTableName($this->table);
        $this->sql_parameters = [];
    }
}
