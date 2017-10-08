<?php
/**
 * Dida Framework --Powered by Zeupin LLC
 * http://dida.zeupin.com
 */

namespace Dida\Db;

/**
 * Demand Interface
 */
interface DemandInterface
{
    /**
     * Class construct.
     *
     * @param \Dida\Db\Db $db
     * @param array $options
     */
    public function __construct(&$db, array $options);


    /**
     * Clears the $built flag, and prepares to build() again.
     *
     * @return $this
     */
    public function changed();


    /**
     * Resets all todolist data.
     *
     * @return $this
     */
    public function resetAll();


    /**
     * Resets the COUNT(...) relative data.
     * See count()
     *
     * @return $this
     */
    public function resetCount();


    /**
     * Changes the master table.
     *
     * @param string $name
     * @param string $alias
     * @param string $prefix
     *
     * @return $this
     */
    public function table($name, $alias = null, $prefix = null);


    /**
     * Adds a WHERE condition.
     *
     * @param mixed $condition
     * @param array $data
     *
     * @return $this
     */
    public function where($condition, array $data = []);


    /**
     * Adds many WHERE conditions.
     *
     * @param array $conditions
     * @param string $logic
     *
     * @return $this
     */
    public function whereMany(array $conditions, $logic = 'AND');


    /**
     * How to join the WHERE condition parts.
     *
     * @param string $logic AND/OR/...
     *
     * @return $this
     */
    public function whereLogic($logic);


    /**
     * Build a WHERE condition to match the given array.
     *
     * @param array $array
     * @param string $logic
     *
     * @return $this
     */
    public function whereMatch(array $array, $logic = 'AND');


    /**
     * Set column expression.
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
     * Set column value using tableB.columnB where table.colA=tableB.colB.
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
     * JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function join($tableB, $on, array $parameters = []);


    /**
     * INNER JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function innerJoin($tableB, $on, array $parameters = []);


    /**
     * LEFT JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function leftJoin($tableB, $on, array $parameters = []);


    /**
     * RIGHT JOIN clause
     *
     * @param string $tableB
     * @param string $on
     * @param array $parameters
     *
     * @return $this
     */
    public function rightJoin($tableB, $on, array $parameters = []);


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


    /**
     * GROUP BY clause.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function groupBy(array $columns);


    /**
     * Adds a having condition.
     *
     * @param type $condition
     * @param type $parameters
     *
     * @return $this
     */
    public function having($condition, $parameters = []);


    /**
     * Adds many having conditions.
     *
     * @param array $conditions
     * @param type $logic
     *
     * @return $this
     */
    public function havingMany(array $conditions, $logic = 'AND');


    /**
     * How to join having clause parts.
     *
     * @param string $logic AND/OR/XOR/...
     *
     * @return $this
     */
    public function havingLogic($logic);


    /**
     * DISTINCT clause.
     *
     * @param boolean $distinct
     *
     * @return $this
     */
    public function distinct($distinct = true);


    /**
     * ORDER BY clause.
     *
     * @param array|string $columns
     *
     * @return $this
     */
    public function orderBy($columns);


    /**
     * SELECT COUNT(...)
     *
     * @param array $columns
     * @param string $alias
     *
     * @return $this
     */
    public function count(array $columns = null, $alias = null);


    /**
     * LIMIT clause.
     *
     * @param int|string $limit
     *
     * @return $this
     */
    public function limit($limit);


    /**
     * SELECT
     *
     * @return $this
     */
    public function select(array $arrayColumnAsAlias = []);


    /**
     * DELETE
     *
     * @return $this
     */
    public function delete();


    /**
     * INSERT
     *
     * @return $this
     */
    public function insert(array $record);


    /**
     * UPDATE
     *
     * @return $this
     */
    public function update();


    /**
     * TRUNCATE
     *
     * @return $this
     */
    public function truncate();


    /**
     * Builds the statement.
     *
     * @return $this
     */
    public function build();


    /**
     * Executes the SQL statement built and returns a Result object.
     *
     * @param string $sql
     * @param array $sql_parameters
     *
     * @return Result
     */
    public function execute();
}
