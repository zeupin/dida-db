<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * DUL Interface
 */
interface DULInterface
{
    /**
     * Set column value.
     *
     * @param string|array $column
     * @param mixed|null $value
     */
    public function setValue($column, $value = null);


    /**
     * Set column expression.
     *
     * @param string $column
     * @param mixed $expr
     * @param array $parameters
     *
     * @return $this
     */
    public function setExpr($column, $expr, array $parameters = []);


    /**
     * Set column value using a SELECT subquery.
     *
     * @param string $column
     * @param string $tableB
     * @param string $columnB
     * @param string $colA
     * @param string $colB
     * @param boolean $checkExistsInWhere
     *
     * @return $this
     */
    public function setFromTable($column, $tableB, $columnB, $colA, $colB, $checkExistsInWhere = true);


    /**
     * Increases $column by $value
     *
     * @param string $column
     * @param mixed $value
     */
    public function increase($column, $value = 1);


    /**
     * Decreases $column by $value
     *
     * @param string $column
     * @param mixed $value
     */
    public function decrease($column, $value = 1);
}
